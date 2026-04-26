#!/usr/bin/env python3
"""Fix current production n8n runtime faults found in the April 2026+ fleet smoke.

Targeted fixes:
- RS AI Bot Invitation Hub: use RS_* Telegram env names available in the n8n container.
- RS AI Agent SEO-AEO: remove the obsolete /diagnostyka page that now returns 404.
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

INVITATION_HUB_ID = "F6uosr6xSCJZM4fO"
SEO_AEO_ID = "yE7tLieYNJa4FDW3"

TELEGRAM_URL_EXPR = (
    "={{ 'https://api.telegram.org/bot' + "
    "String($env.RS_TELEGRAM_BOT_TOKEN || $env.TELEGRAM_BOT_TOKEN || '') + '/sendMessage' }}"
)


def _jwt_vps() -> str:
    from vps_exec import vps_exec_capture  # noqa: E402

    cmd = (
        "sudo python3 -c \"import sqlite3; "
        "c=sqlite3.connect('/srv/ops-stack/n8n/storage/database.sqlite'); "
        "r=c.execute('SELECT apiKey FROM user_api_keys ORDER BY updatedAt DESC LIMIT 1').fetchone(); "
        "print(str(r[0]).strip() if r and r[0] else '', end='')\""
    )
    out, err, code = vps_exec_capture(cmd, timeout=60)
    if code != 0:
        raise RuntimeError(err or out or f"vps jwt exit {code}")
    token = (out or "").strip()
    if not token:
        raise RuntimeError("empty n8n API key from VPS")
    return token


def _headers(token: str) -> dict[str, str]:
    return {"X-N8N-API-KEY": token, "Content-Type": "application/json"}


def _workflow_payload(wf: dict) -> dict:
    return {
        "name": wf["name"],
        "nodes": wf["nodes"],
        "connections": wf["connections"],
        "settings": wf.get("settings", {}),
        "staticData": wf.get("staticData"),
    }


def _backup(backup_dir: Path, wf: dict) -> None:
    backup_dir.mkdir(parents=True, exist_ok=True)
    path = backup_dir / f"{wf['id']}_{wf['name'].replace(' ', '_')}.json"
    path.write_text(json.dumps(wf, ensure_ascii=False, indent=2), encoding="utf-8")


def _patch_invitation_hub(wf: dict) -> list[str]:
    notes: list[str] = []
    for node in wf.get("nodes") or []:
        if node.get("name") != "Telegram Report":
            continue
        params = node.setdefault("parameters", {})
        params["url"] = TELEGRAM_URL_EXPR
        params["sendHeaders"] = True
        params["specifyBody"] = "json"
        headers = params.setdefault("headerParameters", {}).setdefault("parameters", [])
        headers[:] = [{"name": "Content-Type", "value": "application/json; charset=utf-8"}]
        body = str(params.get("jsonBody") or "")
        if "RS_TELEGRAM_CHAT_ID" not in body:
            params["jsonBody"] = (
                "={{ JSON.stringify({ "
                "chat_id: String($env.RS_TELEGRAM_CHAT_ID || $env.TELEGRAM_CHAT_ID || '6534705697'), "
                "text: $json.report, "
                "parse_mode: 'Markdown', "
                "disable_web_page_preview: true "
                "}) }}"
            )
        notes.append("Invitation Hub Telegram Report: RS_* env fallback + UTF-8 JSON headers")
    return notes


def _patch_seo_aeo(wf: dict) -> list[str]:
    notes: list[str] = []
    obsolete_urls = [
        "  'https://rsperformance.online/diagnostyka',\n",
        "  'https://rsperformance.online/kontakt',\n",
    ]
    for node in wf.get("nodes") or []:
        params = node.setdefault("parameters", {})
        if node.get("name") == "Define Pages":
            js_code = str(params.get("jsCode") or "")
            removed = [url for url in obsolete_urls if url in js_code]
            for url in removed:
                js_code = js_code.replace(url, "")
            if removed:
                params["jsCode"] = js_code
                removed_labels = ", ".join(url.strip().strip("',") for url in removed)
                notes.append(f"SEO-AEO Define Pages: removed obsolete 404 URL(s): {removed_labels}")
        if node.get("name") == "Telegram SEO Report":
            params["url"] = TELEGRAM_URL_EXPR
            params["sendHeaders"] = True
            headers = params.setdefault("headerParameters", {}).setdefault("parameters", [])
            headers[:] = [{"name": "Content-Type", "value": "application/json; charset=utf-8"}]
            notes.append("SEO-AEO Telegram SEO Report: RS_* env fallback + UTF-8 JSON headers")
    return notes


def _fetch(base: str, headers: dict[str, str], workflow_id: str) -> dict:
    response = requests.get(f"{base}/api/v1/workflows/{workflow_id}", headers=headers, timeout=90)
    if not response.ok:
        raise RuntimeError(f"GET {workflow_id} HTTP {response.status_code}: {response.text[:800]}")
    return response.json()


def _put(base: str, headers: dict[str, str], wf: dict) -> None:
    response = requests.put(
        f"{base}/api/v1/workflows/{wf['id']}",
        headers=headers,
        json=_workflow_payload(wf),
        timeout=180,
    )
    if not response.ok:
        raise RuntimeError(f"PUT {wf['id']} HTTP {response.status_code}: {response.text[:800]}")


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--vps-jwt", action="store_true", help="Read n8n API key from VPS SQLite")
    parser.add_argument("--base-url", default="", help="Override n8n base URL")
    args = parser.parse_args()

    if args.vps_jwt:
        base = (args.base_url or os.environ.get("N8N_API_URL") or "https://auto.rs3d.pl").rstrip("/")
        token = _jwt_vps()
    else:
        base, token = require_n8n_api()
        if args.base_url:
            base = args.base_url.rstrip("/")

    headers = _headers(token)
    workflows = [
        (INVITATION_HUB_ID, _patch_invitation_hub),
        (SEO_AEO_ID, _patch_seo_aeo),
    ]
    backup_dir = _root / "scripts" / f".tmp_n8n_runtime_fix_backup_{time.strftime('%Y%m%d_%H%M%S')}"

    all_notes: list[str] = []
    patched: list[dict] = []
    for workflow_id, patcher in workflows:
        wf = _fetch(base, headers, workflow_id)
        notes = patcher(wf)
        if notes:
            _backup(backup_dir, wf)
            patched.append(wf)
            all_notes.extend(f"{wf['name']}: {note}" for note in notes)

    print("Patches:")
    for note in all_notes or ["none"]:
        print(f"  - {note}")
    if args.dry_run:
        print("Dry-run: no PUT")
        return 0

    for wf in patched:
        _put(base, headers, wf)
        print(f"OK PUT {wf['id']} {wf['name']}")
    print(f"backup={backup_dir}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
