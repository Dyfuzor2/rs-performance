#!/usr/bin/env python3
"""
April 2026+ — **RS Research Harvester** n8n workflow (Telegram + harvest HTTP).

Fixes from production backup ``tmp/n8n-backups-20260411/research-harvester.xcwu34W87JpmV75S.before.json``:

- **``undefined`` in Telegram line:** HttpRequest output may nest fields under ``body`` or use different keys.
  Telegram text uses defensive reads: ``body || root`` and ``String(x ?? '—')``.
- **Mojibake / odd prefix:** remove non-ASCII emoji from the **expression source**; use ``parse_mode: HTML`` + ASCII labels.
- **Hardcoded secrets:** Telegram URL/chat/token and ``X-API-Token`` literal → env vars (same pattern as other WOW scripts).

Default workflow id: ``xcwu34W87JpmV75S``.

Requires: ``.cursor/mcp.env`` (``N8N_API_URL``, ``N8N_API_KEY``) or ``--vps-jwt``.

Usage::

    python scripts/n8n_apply_research_harvester_telegram_wow_apr2026.py --dry-run
    python scripts/n8n_apply_research_harvester_telegram_wow_apr2026.py --vps-jwt
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

DEFAULT_WF_ID = "xcwu34W87JpmV75S"

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

# Optional chaining: n8n 1.x+ expressions (HttpRequest may nest JSON under ``body``).
TELEGRAM_REPORT_JSONBODY = (
    "={{ JSON.stringify({ "
    "chat_id: String($env.RS_TELEGRAM_CHAT_ID || $env.TELEGRAM_CHAT_ID || ''), "
    "parse_mode: 'HTML', "
    "disable_web_page_preview: true, "
    "text: '<b>RS / Research harvester</b>\\n' + "
    "'<b>Articles:</b> ' + String($json.body?.harvested ?? $json.harvested ?? '—') + "
    "' (<b>new:</b> ' + String($json.body?.new ?? $json.new ?? '—') + ')\\n' + "
    "'<b>Avg relevance:</b> ' + String($json.body?.avg_relevance ?? $json.avg_relevance ?? '—') + '\\n' + "
    "'<b>Sources OK:</b> ' + String($json.body?.sources_ok ?? $json.sources_ok ?? '—') "
    "}) }}"
)

HARVEST_URL_EXPR = (
    "={{ String($env.RS_RESEARCH_HARVEST_URL || 'https://auto.rs3d.pl/research/harvest') }}"
)

HARVEST_TOKEN_HEADER = "={{ String($env.RS_EDITORIAL_API_TOKEN || $env.RS_X_API_TOKEN || $env.RS_API_TOKEN || $env.N8N_API_TOKEN || '') }}"


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


def _patch_workflow(wf: dict) -> list[str]:
    notes: list[str] = []
    for n in wf.get("nodes") or []:
        name = n.get("name")
        params = n.setdefault("parameters", {})
        if name == "Telegram Report" and n.get("type") == "n8n-nodes-base.httpRequest":
            params["method"] = "POST"
            params["url"] = TELEGRAM_URL_EXPR
            params["sendHeaders"] = True
            params["headerParameters"] = {
                "parameters": [{"name": "Content-Type", "value": "application/json; charset=utf-8"}]
            }
            params["sendBody"] = True
            params["specifyBody"] = "json"
            params["jsonBody"] = TELEGRAM_REPORT_JSONBODY
            params.pop("bodyParameters", None)
            notes.append("Telegram Report: JSON + HTML + env URL + null-safe body/root fields")
        if name == "Harvest RSS & Trends" and n.get("type") == "n8n-nodes-base.httpRequest":
            params["url"] = HARVEST_URL_EXPR
            hdrs = params.setdefault("headerParameters", {}).setdefault("parameters", [])
            for h in hdrs:
                if h.get("name") == "X-API-Token":
                    h["value"] = HARVEST_TOKEN_HEADER
                    notes.append("Harvest RSS & Trends: X-API-Token from env; URL from RS_RESEARCH_HARVEST_URL")
                    break
            else:
                hdrs.append({"name": "X-API-Token", "value": HARVEST_TOKEN_HEADER})
                notes.append("Harvest RSS & Trends: added X-API-Token header from env")
    return notes


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--dry-run", action="store_true")
    ap.add_argument("--vps-jwt", action="store_true")
    ap.add_argument("--base-url", default="")
    ap.add_argument("--workflow-id", default=DEFAULT_WF_ID)
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
        print("No matching nodes patched.", file=sys.stderr)
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
