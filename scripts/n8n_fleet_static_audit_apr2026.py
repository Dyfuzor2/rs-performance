#!/usr/bin/env python3
"""
April 2026+ static audit of all n8n workflows (RS VPS).

Fetches every workflow via public API, flags common failure patterns:
- HttpRequest nodes: JSON body built as object literal without JSON.stringify (HttpRequest v4.2+)
- HttpRequest to rsperformance.online /api/ without X-RS-Blog-Pipeline-Key or X-API-Token / Bearer in URL or header expression
- Code nodes: $('...') references (manual review)

Does not mutate workflows. Run repairs separately.

Usage: python scripts/n8n_fleet_static_audit_apr2026.py
"""

from __future__ import annotations

import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_n8n_api  # noqa: E402

# Object-literal jsonBody: starts with ={{ { or ={{{ and no JSON.stringify
_BAD_JSON_BODY = re.compile(
    r"=\{\{?\s*\{",
    re.MULTILINE,
)
_HAS_STRINGIFY = re.compile(r"JSON\.stringify\s*\(", re.I)

_RS_HOST = re.compile(
    r"rsperformance\.online|rsperformance\.pl",
    re.I,
)
_RS_API = re.compile(r"/api/", re.I)
_AUTH_HINT = re.compile(
    r"X-RS-Blog-Pipeline-Key|X-API-Token|Authorization|Bearer|pipeline",
    re.I,
)


def fetch_json(url: str, headers: dict) -> dict | list:
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req, timeout=120) as r:
        return json.loads(r.read().decode())


def list_all_workflow_metas(base: str, headers: dict) -> list[dict]:
    """Paginated list (limit per n8n API docs); first page must include limit."""
    out: list[dict] = []
    cursor: str | None = None
    limit = 250
    while True:
        params: dict[str, str | int] = {"limit": limit}
        if cursor:
            params["cursor"] = cursor
        q = f"{base}/api/v1/workflows?" + urllib.parse.urlencode(params)
        data = fetch_json(q, headers)
        out.extend(data.get("data") or [])
        cursor = data.get("nextCursor")
        if not cursor:
            break
    return out


def fetch_workflow(base: str, headers: dict, wid: str) -> dict:
    return fetch_json(f"{base}/api/v1/workflows/{wid}", headers)


def scan_http_nodes(nodes: list) -> list[str]:
    issues: list[str] = []
    for n in nodes:
        if n.get("type") != "n8n-nodes-base.httpRequest":
            continue
        name = n.get("name") or "?"
        p = n.get("parameters") or {}
        url_e = str(p.get("url") or "")
        body = p.get("jsonBody")
        if body is None:
            body = ""
        body_s = str(body)
        send_body = p.get("sendBody")
        spec = str(p.get("specifyBody") or p.get("contentType") or "")

        if body_s and _BAD_JSON_BODY.search(body_s) and not _HAS_STRINGIFY.search(body_s):
            if "json" in spec.lower() or p.get("contentType") == "json":
                issues.append(f'http "{name}": jsonBody looks like object literal without JSON.stringify')

        combined = url_e + " " + body_s + " " + json.dumps(p.get("headerParameters") or {})
        if _RS_HOST.search(combined) and _RS_API.search(combined):
            if not _AUTH_HINT.search(combined):
                issues.append(f'http "{name}": calls RS /api/ but no obvious auth header in expressions (verify manually)')

    return issues


def scan_code_nodes(nodes: list) -> list[str]:
    issues: list[str] = []
    for n in nodes:
        if n.get("type") != "n8n-nodes-base.code":
            continue
        name = n.get("name") or "?"
        code = str((n.get("parameters") or {}).get("jsCode") or "")
        if "$(" in code:
            refs = re.findall(r"\$\(\s*['\"]([^'\"]+)['\"]\s*\)", code)
            if refs:
                issues.append(f'code "{name}": references nodes {refs[:6]}{"…" if len(refs) > 6 else ""} (ensure execution order)')
    return issues


def main() -> int:
    base, key = require_n8n_api()
    headers = {"X-N8N-API-KEY": key}
    metas = list_all_workflow_metas(base, headers)
    print(f"=== n8n fleet static audit — {len(metas)} workflows (API) ===\n")

    total_flags = 0
    for meta in sorted(metas, key=lambda m: (not m.get("active", False), m.get("name") or "")):
        wid = meta.get("id")
        wname = meta.get("name") or ""
        active = meta.get("active")
        status = "ACTIVE" if active else "inactive"
        try:
            wf = fetch_workflow(base, headers, wid)
        except urllib.error.HTTPError as e:
            print(f"[{status}] {wid} {wname[:50]} — FETCH FAIL {e.code}")
            total_flags += 1
            continue
        nodes = wf.get("nodes") or []
        hi = scan_http_nodes(nodes)
        ci = scan_code_nodes(nodes)
        flags = hi + ci
        if flags:
            total_flags += len(flags)
            print(f"[{status}] {wid} {wname}")
            for f in flags:
                print(f"    ! {f}")
        else:
            print(f"[{status}] {wid} {wname[:45]} — OK (no static flags)")

    print(f"\n=== summary: {total_flags} flag line(s) across workflows ===")
    if total_flags == 0:
        print("No static anti-patterns detected by this scanner.")
    return 0 if total_flags == 0 else 1


if __name__ == "__main__":
    raise SystemExit(main())
