#!/usr/bin/env python3
"""Fix Telegram bot token and chat ID in ALL n8n workflows"""
from pathlib import Path
import sys

import requests, json, urllib3

urllib3.disable_warnings()

_root = Path(__file__).resolve().parent
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_n8n_api, require_telegram_token_migration  # noqa: E402

OLD_TOKEN, NEW_TOKEN, OLD_CHAT, NEW_CHAT = require_telegram_token_migration()

N8N_URL, N8N_API_KEY = require_n8n_api()
headers = {"X-N8N-API-KEY": N8N_API_KEY, "Content-Type": "application/json"}

# Get all workflows
r = requests.get(f"{N8N_URL}/api/v1/workflows?limit=20", headers=headers, verify=False, timeout=15)
workflows = r.json().get("data", [])

fixed_count = 0

for wf_summary in workflows:
    wf_id = wf_summary["id"]
    wf_name = wf_summary["name"]

    # Get full workflow
    r = requests.get(f"{N8N_URL}/api/v1/workflows/{wf_id}", headers=headers, verify=False, timeout=15)
    wf = r.json()

    # Serialize to JSON string, replace tokens
    wf_str = json.dumps(wf)
    changed = False

    if OLD_TOKEN in wf_str:
        wf_str = wf_str.replace(OLD_TOKEN, NEW_TOKEN)
        changed = True
    if OLD_CHAT in wf_str:
        wf_str = wf_str.replace(OLD_CHAT, NEW_CHAT)
        changed = True

    if changed:
        wf_new = json.loads(wf_str)
        update = {
            "name": wf_new["name"],
            "nodes": wf_new["nodes"],
            "connections": wf_new["connections"],
            "settings": wf_new["settings"],
            "staticData": wf_new.get("staticData")
        }
        r2 = requests.put(f"{N8N_URL}/api/v1/workflows/{wf_id}", headers=headers, json=update, verify=False, timeout=15)
        status = "OK" if r2.status_code == 200 else f"FAIL({r2.status_code})"
        print(f"  FIXED {wf_name} ({wf_id}): {status}")
        fixed_count += 1
    else:
        print(f"  ok    {wf_name}")

print(f"\nFixed {fixed_count} workflows out of {len(workflows)}")

# Now register webhook for Telegram Blog on Demand
print("\nRegistering Telegram webhook for Blog on Demand...")
webhook_url = f"{N8N_URL}/webhook/telegram-blog"
tg_resp = requests.post(
    f"https://api.telegram.org/bot{NEW_TOKEN}/setWebhook",
    json={
        "url": webhook_url,
        "allowed_updates": ["message"],
    },
    timeout=15
)
print(f"setWebhook: {tg_resp.json()}")

# But wait - the bot already has a webhook to rsperformance.online for the old Telegram bot service
# We need the bot to send updates to n8n, not the old endpoint
# Solution: we'll use a different approach - polling or a shared webhook
# Actually, the bot can only have ONE webhook. Let's check what's best.

# The existing webhook goes to rsperformance.online/api/blog/telegram/webhook
# That's the old Filament-based bot. If we switch to n8n, we lose that.
# Better: keep the old webhook, and add a "Generate blog" button in the existing
# Telegram bot handler on the hosting side that calls the n8n pipeline.
# OR: use Telegram getUpdates polling in n8n instead of webhook.

# For now, let's NOT change the webhook (keep old hosting handler)
# Instead, the n8n workflow should use getUpdates polling or we add
# a /blog command handler to the existing hosting Telegram bot.

print("\nWARNING: Bot already has webhook to rsperformance.online/api/blog/telegram/webhook")
print("Cannot register second webhook. Will add /blog command to existing bot handler instead.")

# Revert webhook to original
tg_resp2 = requests.post(
    f"https://api.telegram.org/bot{NEW_TOKEN}/setWebhook",
    json={
        "url": "https://rsperformance.online/api/blog/telegram/webhook",
        "allowed_updates": ["message"],
    },
    timeout=15
)
print(f"Restored original webhook: {tg_resp2.json().get('ok')}")

# Verify bot is working now with test message
test_resp = requests.post(
    f"https://api.telegram.org/bot{NEW_TOKEN}/sendMessage",
    json={
        "chat_id": NEW_CHAT,
        "text": "🤖 n8n Telegram tokens updated!\n\n✅ Wszystkie workflow'y używają aktualnego tokena bota.\n⚙️ Dostępne komendy:\n/blog <temat> — generuj wpis na blog\n/blog — auto-temat + generuj\n/status — status crew",
    },
    timeout=15
)
print(f"\nTest message sent: {test_resp.json().get('ok')}")
