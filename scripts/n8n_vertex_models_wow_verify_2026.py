#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
April 2026+ — weryfikacja modeli AI: workflow n8n + konfig Laravel vs Vertex Model Garden.

- Public API n8n (N8N_API_URL + N8N_API_KEY w .cursor/mcp.env) lub --local-only.
- Lokalne: n8n_workflow_*.json, opcjonalnie storage/app/n8n_vertex_patched.json.
- Laravel: domyślne ID z config/blog.php i config/ops.php (vertex_model_defaults) — bo część
  workflowów woła tylko /api/blog/pipeline/* (modele po stronie serwera).

Usage:
  python scripts/n8n_vertex_models_wow_verify_2026.py
  python scripts/n8n_vertex_models_wow_verify_2026.py --local-only
  python scripts/n8n_vertex_models_wow_verify_2026.py --no-gcloud
  set GCLOUD_PATH=C:\\Program Files\\Google\\Cloud SDK\\google-cloud-sdk\\bin\\gcloud.cmd
  # Model Garden przez gcloud na VPS (klucz diagnosta w repo + upload na VPS):
  python scripts/n8n_vertex_models_wow_verify_2026.py --vps-gcloud
  set VPS_EXEC_TIMEOUT=600

Exit 1 — jeśli którykolwiek model Vertex family nie występuje w Model Garden.
Exit 2 — jeśli gcloud nieosiągalny (gdy wymagany).
"""

from __future__ import annotations

import argparse
import json
import os
import re
import shlex
import shutil
import subprocess
import sys
from collections import defaultdict
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import load_cursor_env, repo_root, require_n8n_api  # noqa: E402

_VERTEX_LIKE = re.compile(
    r"\b(?:gemini|imagen|claude)-[a-z0-9][a-z0-9.\-]*(?:@[a-z0-9.\-]+)?\b",
    re.IGNORECASE,
)
_MODEL_KEY = re.compile(r'["\']model["\']\s*:\s*["\']([^"\'\\]{1,120})["\']', re.IGNORECASE)
_SLUG = re.compile(
    r'["\']((?:openai|nvidia|google|meta|anthropic|mistralai|deepseek)/[a-z0-9_./\-:]+)["\']',
    re.I,
)
# Quoted model ids inside PHP config (defaults in env(..., '...'))
_PHP_VERTEX_ID = re.compile(r"['\"]((?:gemini|imagen|claude)-[a-z0-9][a-z0-9.\-]*)['\"]", re.I)


def _resolve_gcloud() -> str | None:
    env = (os.environ.get("GCLOUD_PATH") or "").strip()
    if env and Path(env).exists():
        return env
    w = shutil.which("gcloud")
    if w:
        return w
    # typowy Windows SDK
    for candidate in (
        os.path.expandvars(r"%ProgramFiles(x86)%\Google\Cloud SDK\google-cloud-sdk\bin\gcloud.cmd"),
        os.path.expandvars(r"%ProgramFiles%\Google\Cloud SDK\google-cloud-sdk\bin\gcloud.cmd"),
        os.path.expandvars(r"%LocalAppData%\Google\Cloud SDK\google-cloud-sdk\bin\gcloud.cmd"),
        r"D:\google-cloud-sdk\google-cloud-sdk\bin\gcloud.cmd",
    ):
        if Path(candidate).is_file():
            return candidate
    return None


def _norm_vertex_id(raw: str) -> str:
    s = raw.strip().lower()
    if "@" in s:
        s = s.split("@", 1)[0]
    return s


def _short_name_from_garden(full: str) -> str | None:
    if "/models/" not in full:
        return None
    return full.split("/models/", 1)[-1].strip()


def _gcloud_garden_json(gcloud_bin: str, project: str, model_filter: str, limit: int = 500) -> tuple[list[dict], str | None]:
    try:
        proc = subprocess.run(
            [
                gcloud_bin,
                "ai",
                "model-garden",
                "models",
                "list",
                f"--project={project}",
                f"--model-filter={model_filter}",
                f"--limit={limit}",
                "--format=json",
            ],
            capture_output=True,
            text=True,
            timeout=180,
            check=False,
            shell=False,
        )
    except FileNotFoundError:
        return [], f"gcloud not executable: {gcloud_bin}"
    except subprocess.TimeoutExpired:
        return [], f"gcloud timed out (filter={model_filter})"

    if proc.returncode != 0:
        err = (proc.stderr or proc.stdout or "").strip()[:600]
        return [], f"gcloud failed ({proc.returncode}) filter={model_filter}: {err}"

    try:
        rows = json.loads(proc.stdout or "[]")
    except json.JSONDecodeError as e:
        return [], f"gcloud JSON parse error filter={model_filter}: {e}"

    return rows if isinstance(rows, list) else [], None


def _vps_bundle_script(project: str, key_path: str) -> str:
    """Bash + Python on VPS: jedna sesja auth, wszystkie filtry Model Garden → jeden JSON."""
    return (
        "set -euo pipefail\n"
        f"KEY={shlex.quote(key_path)}\n"
        f"export PROJ={shlex.quote(project)}\n"
        'gcloud auth activate-service-account --key-file="$KEY" --quiet\n'
        'gcloud config set project "$PROJ" --quiet\n'
        "python3 <<'PY'\n"
        "import json, os, subprocess, sys\n"
        "p = os.environ['PROJ']\n"
        'filters = ("gemini", "claude", "imagen", "text-embedding", "gemini-embedding")\n'
        "out = []\n"
        "for f in filters:\n"
        "    r = subprocess.run(\n"
        "        [\n"
        '            "gcloud", "ai", "model-garden", "models", "list",\n'
        '            "--project=" + p,\n'
        '            "--model-filter=" + f,\n'
        '            "--limit=500",\n'
        '            "--format=json",\n'
        "        ],\n"
        "        capture_output=True,\n"
        "        text=True,\n"
        "    )\n"
        "    if r.returncode != 0:\n"
        '        sys.stderr.write(r.stderr or "")\n'
        "        raise SystemExit(r.returncode)\n"
        '    out.extend(json.loads(r.stdout or "[]"))\n'
        "print(json.dumps(out))\n"
        "PY\n"
    )


def _load_model_garden_catalog_vps(project: str, key_path: str) -> tuple[set[str], str | None]:
    """Model Garden przez SSH na VPS (gcloud + SA JSON na serwerze)."""
    sys.path.insert(0, str(_root))
    try:
        from vps_exec import vps_exec_capture
    except ImportError:
        return set(), "vps_exec import failed (run from repo root)"

    script = _vps_bundle_script(project, key_path)
    remote_cmd = "bash -lc " + shlex.quote(script)
    out, err, code = vps_exec_capture(remote_cmd, timeout=int(os.environ.get("VPS_EXEC_TIMEOUT", "600")))
    if code != 0:
        tail = (err or out or "").strip()[:800]
        return set(), f"vps gcloud bundle exit {code}: {tail}"

    try:
        rows = json.loads(out.strip() or "[]")
    except json.JSONDecodeError as e:
        return set(), f"vps JSON parse: {e}; stderr={err[:400]!r}"

    if not isinstance(rows, list):
        return set(), "vps: expected JSON array of models"

    catalog: set[str] = set()
    for row in rows:
        name = row.get("name") if isinstance(row, dict) else None
        if not isinstance(name, str):
            continue
        short = _short_name_from_garden(name)
        if short:
            catalog.add(_norm_vertex_id(short))
    return catalog, None


def _load_model_garden_catalog(gcloud_bin: str, project: str) -> tuple[set[str], str | None]:
    out: set[str] = set()
    filters = ("gemini", "claude", "imagen", "text-embedding", "gemini-embedding")
    errors: list[str] = []
    for flt in filters:
        rows, err = _gcloud_garden_json(gcloud_bin, project, flt)
        if err:
            errors.append(err)
            continue
        for row in rows:
            name = row.get("name")
            if not isinstance(name, str):
                continue
            short = _short_name_from_garden(name)
            if short:
                out.add(_norm_vertex_id(short))
    if not out and errors:
        return set(), errors[0]
    return out, None


def _extract_models_from_blob(text: str) -> tuple[set[str], set[str]]:
    vertex: set[str] = set()
    external: set[str] = set()

    for m in _VERTEX_LIKE.findall(text):
        vertex.add(m.strip())

    for m in _MODEL_KEY.findall(text):
        s = m.strip()
        if re.match(r"^(?:gemini|imagen|claude)-", s, re.I):
            vertex.add(s)
        elif "/" in s:
            external.add(s)

    for m in _SLUG.findall(text):
        external.add(m.strip())

    return vertex, external


def _models_from_php_file(path: Path) -> set[str]:
    if not path.is_file():
        return set()
    try:
        text = path.read_text(encoding="utf-8")
    except OSError:
        return set()
    found: set[str] = set()
    for m in _PHP_VERTEX_ID.findall(text):
        if re.match(r"^(?:gemini|imagen|claude)-", m, re.I):
            found.add(m)
    return found


def _fetch_n8n_workflows_blob(base: str, key: str) -> list[tuple[str, str, str]]:
    headers = {"X-N8N-API-KEY": key}
    r = requests.get(f"{base}/api/v1/workflows", headers=headers, timeout=120)
    r.raise_for_status()
    metas = r.json().get("data") or r.json()
    out: list[tuple[str, str, str]] = []
    for m in metas:
        wid = m.get("id")
        if not wid:
            continue
        wr = requests.get(f"{base}/api/v1/workflows/{wid}", headers=headers, timeout=60)
        if not wr.ok:
            continue
        w = wr.json()
        blob = json.dumps(w, ensure_ascii=False)
        out.append((str(wid), str(w.get("name") or "?"), blob))
    return out


def _local_workflow_blobs(root: Path) -> list[tuple[str, str, str]]:
    out: list[tuple[str, str, str]] = []
    for p in sorted(root.glob("n8n_workflow_*.json")):
        try:
            data = json.loads(p.read_text(encoding="utf-8"))
        except (OSError, json.JSONDecodeError):
            continue
        workflows = data if isinstance(data, list) else [data]
        for i, w in enumerate(workflows):
            if not isinstance(w, dict):
                continue
            wid = str(w.get("id") or p.stem + (f"#{i}" if len(workflows) > 1 else ""))
            name = str(w.get("name") or p.name)
            out.append((wid, f"{name} [{p.name}]", json.dumps(w, ensure_ascii=False)))
    return out


def _storage_export_blobs(root: Path) -> list[tuple[str, str, str]]:
    p = root / "storage" / "app" / "n8n_vertex_patched.json"
    if not p.is_file():
        return []
    try:
        data = json.loads(p.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        return []
    workflows = data if isinstance(data, list) else [data]
    out: list[tuple[str, str, str]] = []
    for w in workflows:
        if not isinstance(w, dict):
            continue
        wid = str(w.get("id") or "export")
        name = str(w.get("name") or "n8n_vertex_patched")
        out.append((wid, f"{name} [storage/app/n8n_vertex_patched.json]", json.dumps(w, ensure_ascii=False)))
    return out


def main() -> int:
    parser = argparse.ArgumentParser(description="Verify n8n + Laravel config models vs Vertex Model Garden")
    parser.add_argument("--local-only", action="store_true", help="Skip n8n API; scan local JSON only")
    parser.add_argument("--no-gcloud", action="store_true", help="List models only; skip Model Garden")
    parser.add_argument(
        "--project",
        default=os.environ.get("VERTEX_PROJECT_ID") or os.environ.get("GCP_PROJECT") or "diagnosta-489719",
        help="GCP project id",
    )
    parser.add_argument(
        "--skip-config",
        action="store_true",
        help="Do not merge config/blog.php + config/ops.php defaults",
    )
    parser.add_argument(
        "--skip-storage-export",
        action="store_true",
        help="Do not read storage/app/n8n_vertex_patched.json",
    )
    parser.add_argument(
        "--vps-gcloud",
        action="store_true",
        help="Pobierz katalog Model Garden przez gcloud na VPS (185.180.207.211), nie lokalnie",
    )
    parser.add_argument(
        "--vps-gcloud-key",
        default=os.environ.get(
            "VPS_GCLOUD_KEY",
            "/home/rsops/.config/gcp/diagnosta-489719-vertex-express.json",
        ),
        help="Ścieżka do SA JSON na VPS (vertex-express@diagnosta-489719)",
    )
    parser.add_argument(
        "--upload-vps-key-from",
        default="",
        help="Lokalny plik JSON do wgrania na --vps-gcloud-key przed weryfikacją (puste = domyślnie diagnosta-489719-*.json z root repo jeśli istnieje)",
    )
    parser.add_argument(
        "--no-upload-vps-key",
        action="store_true",
        help="Nie wgrywaj klucza lokalnego na VPS (użyj już istniejącego pliku na serwerze)",
    )
    args = parser.parse_args()
    load_cursor_env()
    root = repo_root()

    blobs: list[tuple[str, str, str]] = []
    if args.local_only:
        blobs = _local_workflow_blobs(root)
        print("=== Źródło: lokalne n8n_workflow_*.json ===\n")
    else:
        try:
            base, key = require_n8n_api()
            blobs = _fetch_n8n_workflows_blob(base, key)
            print(f"=== Źródło: n8n API ({base}) + lokalne JSON ===\n")
        except RuntimeError as e:
            print(f"WARN n8n API: {e}")
            blobs = []
        blobs.extend(_local_workflow_blobs(root))

    if not args.skip_storage_export:
        blobs.extend(_storage_export_blobs(root))

    seen: dict[str, tuple[str, str]] = {}
    for wid, name, blob in blobs:
        if wid not in seen:
            seen[wid] = (name, blob)

    per_wf_vertex: dict[str, set[str]] = defaultdict(set)
    per_wf_external: dict[str, set[str]] = defaultdict(set)
    pipeline_hits: list[str] = []

    for wid, (name, blob) in seen.items():
        v, e = _extract_models_from_blob(blob)
        per_wf_vertex[wid] |= v
        per_wf_external[wid] |= e
        if "/api/blog/pipeline" in blob or "blog/pipeline" in blob:
            pipeline_hits.append(f"{wid} {name[:60]}")

    blog: set[str] = set()
    ops: set[str] = set()
    if not args.skip_config:
        blog = _models_from_php_file(root / "config" / "blog.php")
        ops = _models_from_php_file(root / "config" / "ops.php")
        for m in blog:
            per_wf_vertex["__config_blog_php__"].add(m)
        for m in ops:
            per_wf_vertex["__config_ops_php__"].add(m)

    all_vertex: set[str] = set()
    all_external: set[str] = set()
    for wid in per_wf_vertex:
        all_vertex |= per_wf_vertex[wid]
    for wid in per_wf_external:
        all_external |= per_wf_external[wid]

    print(f"Workflowów / źródeł: {len(seen) + (2 if not args.skip_config else 0)}")
    print(f"  (workflowów n8n: {len(seen)})")
    print(f"Unikalnych ID modeli (Vertex family): {len(all_vertex)}")
    print(f"Unikalnych slugów zewnętrznych (OpenRouter-like): {len(all_external)}\n")

    if pipeline_hits:
        print("--- Workflowy z HTTP do Laravel blog pipeline (modele z config/blog.php na hostingu) ---")
        for line in pipeline_hits[:30]:
            print(f"  • {line}")
        if len(pipeline_hits) > 30:
            print(f"  ... +{len(pipeline_hits) - 30} więcej")
        print()

    if not args.skip_config and (blog or ops):
        print("--- Modele z domyślnych wartości w config/blog.php / config/ops.php ---")
        for m in sorted(blog | ops, key=str.lower):
            print(f"  • {m}")
        print()

    if all_vertex:
        print("--- Wszystkie unikalne modele Vertex family (workflow + config) ---")
        for m in sorted(all_vertex, key=str.lower):
            print(f"  • {m}")
        print()

    if all_external:
        print("--- Slugi zewnętrzne (poza Model Garden) ---")
        for m in sorted(all_external, key=str.lower):
            print(f"  • {m}")
        print()

    if args.no_gcloud:
        print("(Pominięto gcloud — użyj bez --no-gcloud aby sprawdzić Model Garden.)")
        return 0

    if args.vps_gcloud:
        upload_from = (args.upload_vps_key_from or "").strip()
        if not upload_from:
            default_key = root / "diagnosta-489719-96def3352c52.json"
            upload_from = str(default_key) if default_key.is_file() else ""

        if upload_from and not args.no_upload_vps_key:
            sys.path.insert(0, str(_root))
            from vps_exec import vps_backup_remote_path, vps_upload

            rkey = args.vps_gcloud_key
            vps_backup_remote_path(rkey, "wow_vertex_verify")
            if vps_upload(upload_from, rkey) != 0:
                print("ERROR: upload klucza na VPS nie powiódł się.")
                return 2
            from vps_exec import vps_exec_capture as _cap

            _o, _e, c = _cap(f"chmod 600 {shlex.quote(rkey)}", timeout=30)
            if c != 0:
                print(f"WARN chmod 600 na VPS: {_e[:200]}")
        elif args.no_upload_vps_key:
            print("INFO: --no-upload-vps-key — bez wgrywania; oczekiwany istniejący plik SA na VPS.\n")
        elif not upload_from:
            print(
                "INFO: Brak lokalnego diagnosta-489719-96def3352c52.json w root repo — "
                "bez uploadu; używam klucza już obecnego na VPS.\n"
            )

        print(
            "=== Model Garden przez VPS (gcloud + SA na serwerze) ===\n"
            f"  klucz: {args.vps_gcloud_key}\n"
        )

        catalog, err = _load_model_garden_catalog_vps(args.project, args.vps_gcloud_key)
    else:
        gcloud_bin = _resolve_gcloud()
        if not gcloud_bin:
            print("ERROR: Nie znaleziono gcloud (PATH lub GCLOUD_PATH).")
            print("TIP: Uruchom z --vps-gcloud aby użyć gcloud na VPS (paramiko + vps_exec).")
            return 2

        print(f"Używam lokalnego gcloud: {gcloud_bin}\n")

        catalog, err = _load_model_garden_catalog(gcloud_bin, args.project)
    if err:
        print(f"ERROR Model Garden: {err}")
        return 2

    print(f"=== Model Garden ({args.project}) — {len(catalog)} znormalizowanych id ===\n")

    missing: list[str] = []
    for raw in sorted(all_vertex, key=str.lower):
        key = _norm_vertex_id(raw)
        if key not in catalog:
            missing.append(raw)

    if missing:
        print("BŁĄD — brak w Model Garden (po normalizacji @):")
        for m in missing:
            print(f"  ✗ {m}")
        print()
    else:
        print("OK — wszystkie wykryte modele Vertex family są w Model Garden.\n")

    print("--- Weryfikacja per źródło ---")
    for wid in sorted(per_wf_vertex.keys(), key=lambda x: (x.startswith("__"), x.lower())):
        name = seen.get(wid, (wid,))[0] if not wid.startswith("__") else wid
        mv = per_wf_vertex[wid]
        if not mv:
            continue
        label = name if isinstance(name, str) else wid
        print(f"  [{wid}] {label[:70]}")
        for m in sorted(mv, key=str.lower):
            ok = _norm_vertex_id(m) in catalog
            print(f"      {'✓' if ok else '✗'} {m}")
    print()

    print(
        "Uwagi: Model Garden = dostępność w katalogu. Runtime: 429 quota, Claude 4.6 tylko global "
        "(AnthropicVertexPartnerEndpoint). Slugi OpenRouter wymagają osobnego konta OpenRouter."
    )

    return 1 if missing else 0


if __name__ == "__main__":
    raise SystemExit(main())
