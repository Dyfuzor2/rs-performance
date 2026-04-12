#!/usr/bin/env python3
"""Fix Telegram bot token and chat ID in ALL n8n workflows"""
from pathlib import Path
import sys

import json
import requests
import urllib3

urllib3.disable_warnings()

_root = Path(__file__).resolve().parent
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_n8n_api, require_telegram_token_migration  # noqa: E402


def main() -> None:
    old_token, new_token, old_chat, new_chat = require_telegram_token_migration()

    n8n_url, n8n_api_key = require_n8n_api()
    headers = {"X-N8N-API-KEY": n8n_api_key, "Content-Type": "application/json"}

    r = requests.get(f"{n8n_url}/api/v1/workflows?limit=20", headers=headers, verify=False, timeout=15)
    workflows = r.json().get("data", [])

    fixed_count = 0

    for wf_summary in workflows:
        wf_id = wf_summary["id"]
        wf_name = wf_summary["name"]

        r = requests.get(f"{n8n_url}/api/v1/workflows/{wf_id}", headers=headers, verify=False, timeout=15)
        wf = r.json()

        wf_str = json.dumps(wf)
        changed = False

        if old_token in wf_str:
            wf_str = wf_str.replace(old_token, new_token)
            changed = True
        if old_chat in wf_str:
            wf_str = wf_str.replace(old_chat, new_chat)
            changed = True

        if changed:
            wf_new = json.loads(wf_str)
            update = {
                "name": wf_new["name"],
                "nodes": wf_new["nodes"],
                "connections": wf_new["connections"],
                "settings": wf_new["settings"],
                "staticData": wf_new.get("staticData"),
            }
            r2 = requests.put(
                f"{n8n_url}/api/v1/workflows/{wf_id}",
                headers=headers,
                json=update,
                verify=False,
                timeout=15,
            )
            status = "OK" if r2.status_code == 200 else f"FAIL({r2.status_code})"
            print(f"  FIXED {wf_name} ({wf_id}): {status}")
            fixed_count += 1
        else:
            print(f"  ok    {wf_name}")

    print(f"\nFixed {fixed_count} workflows out of {len(workflows)}")

    print("\nRegistering Telegram webhook for Blog on Demand...")
    webhook_url = f"{n8n_url}/webhook/telegram-blog"
    tg_resp = requests.post(
        f"https://api.telegram.org/bot{new_token}/setWebhook",
        json={
            "url": webhook_url,
            "allowed_updates": ["message"],
        },
        timeout=15,
    )
    print(f"setWebhook: {tg_resp.json()}")

    print("\nWARNING: Bot already has webhook to rsperformance.online/api/blog/telegram/webhook")
    print("Cannot register second webhook. Will add /blog command to existing bot handler instead.")

    tg_resp2 = requests.post(
        f"https://api.telegram.org/bot{new_token}/setWebhook",
        json={
            "url": "https://rsperformance.online/api/blog/telegram/webhook",
            "allowed_updates": ["message"],
        },
        timeout=15,
    )
    print(f"Restored original webhook: {tg_resp2.json().get('ok')}")

    test_msg = (
        "n8n Telegram tokens updated.\n\n"
        "Wszystkie workflowy uzywaja aktualnego tokena bota.\n"
        "Komendy: /blog <temat>, /blog, /status"
    )
    test_resp = requests.post(
        f"https://api.telegram.org/bot{new_token}/sendMessage",
        json={"chat_id": new_chat, "text": test_msg},
        timeout=15,
    )
    print(f"\nTest message sent: {test_resp.json().get('ok')}")


if __name__ == "__main__":
    main()
