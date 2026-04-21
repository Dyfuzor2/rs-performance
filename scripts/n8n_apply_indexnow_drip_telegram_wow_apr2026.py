#!/usr/bin/env python3
"""
April 2026+ — RS Telegram WOW polish for workflow **DTC IndexNow Drip** (VPS n8n).

Fixes operator-visible issues:
- **Double Telegram batch reports**: IndexNow and Bing both fed ``Telegram Report`` → chain ``IndexNow Submit`` → ``Bing IndexNow`` → ``Telegram Report``.
- **Multiple COMPLETE bursts**: ``Select Next Batch`` without ``runOnceForAllItems`` + merge append can fan out; add dedupe + ``runOnceForAllItems``.
- **Mojibake / odd prefixes**: force ``Content-Type: application/json; charset=utf-8``, ``parse_mode: HTML``, ASCII-heavy labels, ``disable_web_page_preview``.
- **Secrets**: drop hardcoded bot token URLs — use ``RS_TELEGRAM_BOT_TOKEN`` / ``TELEGRAM_BOT_TOKEN`` + ``RS_TELEGRAM_CHAT_ID`` / ``TELEGRAM_CHAT_ID`` (same pattern as Search Ops harvester).

Requires: ``.cursor/mcp.env`` with ``N8N_API_URL`` + ``N8N_API_KEY`` **or** ``vps_exec`` JWT path (see ``--vps-jwt``).

Usage::

    python scripts/n8n_apply_indexnow_drip_telegram_wow_apr2026.py --dry-run
    python scripts/n8n_apply_indexnow_drip_telegram_wow_apr2026.py
    python scripts/n8n_apply_indexnow_drip_telegram_wow_apr2026.py --vps-jwt
    python scripts/n8n_apply_indexnow_drip_telegram_wow_apr2026.py --workflow-id <n8n_workflow_id>
"""

from __future__ import annotations

import argparse
import json
import os
import sys
import time
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root))
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_n8n_api  # noqa: E402

DEFAULT_WF_ID = "v2p1MYtzCVmxUxyU"

_JWT_CMD = (
    "sudo python3 -c \"import sqlite3,sys; "
    "c=sqlite3.connect('/srv/ops-stack/n8n/storage/database.sqlite'); "
    "r=c.execute('SELECT apiKey FROM user_api_keys ORDER BY updatedAt DESC LIMIT 1').fetchone(); "
    "print(str(r[0]).strip() if r and r[0] else '', end='')\""
)

SELECT_NEXT_BATCH_JS = r"""const rows = $input.all();
const allUrls = [];
for (const row of rows) {
  const u = row.json && row.json.url;
  if (typeof u === 'string' && u.length > 0) {
    allUrls.push(u);
  }
}
const staticData = $getWorkflowStaticData('global');
const submittedSet = new Set(staticData.submitted || []);
const batchSize = 500;
const pending = allUrls.filter((url) => !submittedSet.has(url));

if (pending.length === 0) {
  const total = submittedSet.size;
  const n = allUrls.length;
  const sig = `${total}|${n}`;
  const prevSig = String(staticData.dripCompleteSig || '');
  const prevAt = Number(staticData.dripCompleteAt || 0);
  if (prevSig === sig && Date.now() - prevAt < 20000) {
    return [];
  }
  staticData.dripCompleteSig = sig;
  staticData.dripCompleteAt = Date.now();
  return [{
    json: {
      status: 'complete',
      totalSubmitted: total,
      remaining: 0,
      batch: [],
      batchSize: 0,
      totalUrls: n,
    },
  }];
}

const batch = pending.slice(0, batchSize);
batch.forEach((url) => submittedSet.add(url));
staticData.submitted = Array.from(submittedSet);

return [{
  json: {
    status: 'submitting',
    batchSize: batch.length,
    totalSubmitted: submittedSet.size,
    totalUrls: allUrls.length,
    remaining: pending.length - batch.length,
    batch,
  },
}];"""

TELEGRAM_URL_EXPR = (
    "={{ 'https://api.telegram.org/bot' + "
    "String($env.RS_TELEGRAM_BOT_TOKEN || $env.TELEGRAM_BOT_TOKEN || '') + '/sendMessage' }}"
)

TELEGRAM_REPORT_JSONBODY = (
    "={{ JSON.stringify({ "
    "chat_id: String($env.RS_TELEGRAM_CHAT_ID || $env.TELEGRAM_CHAT_ID || ''), "
    "parse_mode: 'HTML', "
    "disable_web_page_preview: true, "
    "text: '<b>RS / DTC / IndexNow</b>\\n' + "
    "'<b>Status:</b> ' + String($node['Select Next Batch'].json.status || '') + '\\n' + "
    "'<b>Batch:</b> ' + String($node['Select Next Batch'].json.batchSize || 0) + ' URL\\n' + "
    "'<b>Wyslane:</b> ' + String($node['Select Next Batch'].json.totalSubmitted || 0) + '/' + "
    "String($node['Select Next Batch'].json.totalUrls || '?') + '\\n' + "
    "'<b>Pozostalo:</b> ' + String($node['Select Next Batch'].json.remaining ?? 0) + '\\n' + "
    "'<b>IndexNow HTTP:</b> ' + String($node['IndexNow Submit'].json.statusCode ?? 'OK') + '\\n' + "
    "'<b>Bing HTTP:</b> ' + String($node['Bing IndexNow'].json.statusCode ?? 'OK') "
    "}) }}"
)

TELEGRAM_COMPLETE_JSONBODY = (
    "={{ JSON.stringify({ "
    "chat_id: String($env.RS_TELEGRAM_CHAT_ID || $env.TELEGRAM_CHAT_ID || ''), "
    "parse_mode: 'HTML', "
    "disable_web_page_preview: true, "
    "text: '<b>RS / DTC / IndexNow</b> — <b>KONIEC KOLEJKI</b>\\n' + "
    "'Lacznie w historii statycznej: <b>' + String($json.totalSubmitted ?? 0) + '</b> URL\\n' + "
    "'W tym uruchomieniu nie ma juz URL do wyslania.' "
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
    payload = {
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
    conns = wf.setdefault("connections", {})

    conns["IndexNow Submit"] = {"main": [[{"node": "Bing IndexNow", "type": "main", "index": 0}]]}
    conns["Bing IndexNow"] = {"main": [[{"node": "Telegram Report", "type": "main", "index": 0}]]}

    for n in wf.get("nodes") or []:
        name = n.get("name")
        params = n.setdefault("parameters", {})
        if name == "Select Next Batch" and n.get("type") == "n8n-nodes-base.code":
            params["mode"] = "runOnceForAllItems"
            params["jsCode"] = SELECT_NEXT_BATCH_JS
            notes.append("Select Next Batch: runOnceForAllItems + dedupe complete + batch")
        if name in ("Telegram Report", "Telegram Complete") and n.get("type") == "n8n-nodes-base.httpRequest":
            params["url"] = TELEGRAM_URL_EXPR
            params["sendHeaders"] = True
            params.setdefault("headerParameters", {}).setdefault("parameters", [])
            hdrs = params["headerParameters"]["parameters"]
            hdrs.clear()
            hdrs.append({"name": "Content-Type", "value": "application/json; charset=utf-8"})
            if name == "Telegram Report":
                params["jsonBody"] = TELEGRAM_REPORT_JSONBODY
            else:
                params["jsonBody"] = TELEGRAM_COMPLETE_JSONBODY
            notes.append(f"{name}: env bot URL + UTF-8 JSON + HTML body")

    return notes


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--dry-run", action="store_true", help="Print planned changes, no PUT")
    ap.add_argument(
        "--vps-jwt",
        action="store_true",
        help="Read n8n API JWT from VPS SQLite via vps_exec (Windows ops default)",
    )
    ap.add_argument("--base-url", default="", help="Override N8N_API_URL (default from env)")
    ap.add_argument(
        "--workflow-id",
        default=DEFAULT_WF_ID,
        help="n8n workflow id (default DTC IndexNow Drip from fleet docs)",
    )
    args = ap.parse_args()

    if args.vps_jwt:
        base = (args.base_url or os.environ.get("N8N_API_URL") or "https://auto.rs3d.pl").rstrip("/")
        jwt = _jwt_vps()
    else:
        base, jwt = require_n8n_api()
        if args.base_url:
            base = args.base_url.rstrip("/")

    headers = _headers(base, jwt)
    wid = args.workflow_id.strip()
    r = requests.get(f"{base}/api/v1/workflows/{wid}", headers=headers, timeout=90)
    if not r.ok:
        print(f"GET workflow failed HTTP {r.status_code}: {r.text[:500]}", file=sys.stderr)
        return 1
    wf = r.json()
    notes = _patch_workflow(wf)
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
