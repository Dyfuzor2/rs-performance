#!/usr/bin/env python3
"""
April 2026+ — RS **DTC Enrichment Engine** Telegram polish (n8n).

Fixes visible in operator chat:
- **Mojibake** (``čóí``, ``ÔÇŽ``): legacy ``bodyParameters`` + broken UTF-8 emoji literals in expressions
  → single JSON body, ``parse_mode: HTML``, ASCII-only labels in the expression source.
- **Hardcoded bot token / chat_id** in HttpRequest nodes → ``RS_TELEGRAM_*`` / ``TELEGRAM_*`` env vars.
- **Missing parse_mode** on status lines → explicit HTML.

Workflow id defaults to **RS DTC Enrichment Engine** backup id ``9oAbPosvf0h860Xg``; override with ``--workflow-id``.

Requires: ``.cursor/mcp.env`` (``N8N_API_URL``, ``N8N_API_KEY``) or ``--vps-jwt`` (same pattern as
``n8n_apply_indexnow_drip_telegram_wow_apr2026.py``).

Usage::

    python scripts/n8n_apply_dtc_enrichment_telegram_wow_apr2026.py --dry-run
    python scripts/n8n_apply_dtc_enrichment_telegram_wow_apr2026.py
    python scripts/n8n_apply_dtc_enrichment_telegram_wow_apr2026.py --workflow-id <id>
"""

from __future__ import annotations

import argparse
import json
import os
import sys
import time
from pathlib import Path
from typing import Any

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root))
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_n8n_api  # noqa: E402

DEFAULT_WF_ID = "9oAbPosvf0h860Xg"

_JWT_CMD = (
    "sudo python3 -c \"import sqlite3,sys; "
    "c=sqlite3.connect('/srv/ops-stack/n8n/storage/database.sqlite'); "
    "r=c.execute('SELECT apiKey FROM user_api_keys ORDER BY updatedAt DESC LIMIT 1').fetchone(); "
    "print(str(r[0]).strip() if r and r[0] else '', end='')\""
)

TELEGRAM_URL_EXPR = (
    "={{ 'https://api.telegram.org/bot' + "
    "String($env.RS_TELEGRAM_BOT_TOKEN || $env.TELEGRAM_BOT_TOKEN || '') + '/sendMessage' }}"
)

TELEGRAM_SUCCESS_JSONBODY = (
    "={{ JSON.stringify({ "
    "chat_id: String($env.RS_TELEGRAM_CHAT_ID || $env.TELEGRAM_CHAT_ID || ''), "
    "parse_mode: 'HTML', "
    "disable_web_page_preview: true, "
    "text: '<b>RS / DTC / enrichment</b>\\n' + "
    "'<b>Progress:</b> ' + String($json.updated ?? 0) + ' / ' + String(($json.stats || {}).total ?? '?') + "
    "' (' + String(($json.stats || {}).progress ?? '—') + ')\\n' + "
    "'<b>Model:</b> ' + String($('Parse & Validate').first().json.model || '—') + '\\n' + "
    "'<b>Batch:</b> ' + String($('Parse & Validate').first().json.count ?? 0) + '\\n' + "
    "'<b>Remaining:</b> ' + String(($json.stats || {}).remaining ?? '—') "
    "}) }}"
)

TELEGRAM_ERROR_JSONBODY = (
    "={{ JSON.stringify({ "
    "chat_id: String($env.RS_TELEGRAM_CHAT_ID || $env.TELEGRAM_CHAT_ID || ''), "
    "parse_mode: 'HTML', "
    "disable_web_page_preview: true, "
    "text: '<b>RS / DTC / ERROR</b>\\n' + String($json.message || $json.error || 'unknown') "
    "}) }}"
)

TELEGRAM_ALL_DONE_JSONBODY = (
    "={{ JSON.stringify({ "
    "chat_id: String($env.RS_TELEGRAM_CHAT_ID || $env.TELEGRAM_CHAT_ID || ''), "
    "parse_mode: 'HTML', "
    "disable_web_page_preview: true, "
    "text: '<b>RS / DTC / complete</b>\\n' + "
    "'All <b>' + String(($json.stats || {}).total ?? '—') + '</b> codes enriched.' "
    "}) }}"
)


def _jwt_vps() -> str:
    from vps_exec import vps_exec_capture  # noqa: E402

    out, err, code = vps_exec_capture(_JWT_CMD, timeout=60)
    if code != 0:
        raise RuntimeError(err or out or f"vps jwt exit {code}")
    t = (out or "").strip()
    if not t:
        raise RuntimeError("empty jwt from VPS")
    return t


def _headers(base: str, jwt: str) -> dict[str, str]:
    return {"X-N8N-API-KEY": jwt, "Content-Type": "application/json"}


def _backup(dir_path: Path, wid: str, wf: dict) -> None:
    dir_path.mkdir(parents=True, exist_ok=True)
    (dir_path / f"{wid}.json").write_text(json.dumps(wf, ensure_ascii=False, indent=2), encoding="utf-8")


def _put(base: str, headers: dict[str, str], wf: dict) -> None:
    wid = wf["id"]
    payload: dict[str, Any] = {
        "name": wf["name"],
        "nodes": wf["nodes"],
        "connections": wf["connections"],
        "settings": wf.get("settings", {}),
        "staticData": wf.get("staticData"),
    }
    r = requests.put(f"{base}/api/v1/workflows/{wid}", headers=headers, json=payload, timeout=180)
    if not r.ok:
        raise RuntimeError(f"PUT {wid} HTTP {r.status_code}: {r.text[:800]}")


def _migrate_telegram_http_node(node: dict) -> bool:
    """Convert Telegram HttpRequest from bodyParameters to JSON jsonBody + env URL."""
    name = node.get("name") or ""
    if name not in ("Telegram Success", "Telegram Error", "Telegram All Done"):
        return False
    if node.get("type") != "n8n-nodes-base.httpRequest":
        return False
    p = node.setdefault("parameters", {})
    if name == "Telegram Success":
        p["jsonBody"] = TELEGRAM_SUCCESS_JSONBODY
    elif name == "Telegram Error":
        p["jsonBody"] = TELEGRAM_ERROR_JSONBODY
    else:
        p["jsonBody"] = TELEGRAM_ALL_DONE_JSONBODY

    p["method"] = "POST"
    p["url"] = TELEGRAM_URL_EXPR
    p["sendHeaders"] = True
    p["headerParameters"] = {
        "parameters": [{"name": "Content-Type", "value": "application/json; charset=utf-8"}]
    }
    p["sendBody"] = True
    p["specifyBody"] = "json"
    p.pop("bodyParameters", None)
    p.pop("contentType", None)
    p.setdefault("options", {})
    return True


def _patch_workflow(wf: dict) -> list[str]:
    notes: list[str] = []
    for n in wf.get("nodes") or []:
        if _migrate_telegram_http_node(n):
            notes.append(f"{n.get('name')}: JSON body + HTML + env URL/chat (removed bodyParameters)")
    return notes


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--dry-run", action="store_true")
    ap.add_argument("--vps-jwt", action="store_true")
    ap.add_argument("--base-url", default="")
    ap.add_argument("--workflow-id", default=DEFAULT_WF_ID, help="n8n workflow id (default RS DTC Enrichment Engine)")
    args = ap.parse_args()

    if args.vps_jwt:
        base = (args.base_url or os.environ.get("N8N_API_URL") or "https://auto.rs3d.pl").rstrip("/")
        jwt = _jwt_vps()
    else:
        base, jwt = require_n8n_api()
        if args.base_url:
            base = args.base_url.rstrip("/")

    wid = args.workflow_id.strip()
    headers = _headers(base, jwt)
    r = requests.get(f"{base}/api/v1/workflows/{wid}", headers=headers, timeout=90)
    if not r.ok:
        print(f"GET workflow failed HTTP {r.status_code}: {r.text[:500]}", file=sys.stderr)
        return 1
    wf = r.json()
    notes = _patch_workflow(wf)
    if not notes:
        print("No Telegram HttpRequest nodes patched (names/types mismatch?).", file=sys.stderr)
        return 1
    print("Patches:")
    for line in notes:
        print(f"  - {line}")

    if args.dry_run:
        print("Dry-run: no PUT")
        return 0

    ts = time.strftime("%Y%m%d_%H%M%S")
    backup_dir = _root / "scripts" / f".tmp_n8n_wf_backup_{ts}"
    _backup(backup_dir, wid, wf)
    _put(base, headers, wf)
    print(f"OK PUT {wid} backup={backup_dir}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
