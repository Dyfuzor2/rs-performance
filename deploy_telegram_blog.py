#!/usr/bin/env python3
"""Deploy Telegram Blog on Demand workflow to n8n"""
from pathlib import Path
import sys

import requests, json, urllib3

urllib3.disable_warnings()

_root = Path(__file__).resolve().parent
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import (  # noqa: E402
    require_facebook_page_access_token,
    require_n8n_api,
    require_openrouter_api_key,
    require_rs_blog_pipeline_key,
    require_telegram_operator,
)

TELEGRAM_BOT, TELEGRAM_CHAT = require_telegram_operator()
OPENROUTER_KEY = require_openrouter_api_key()
FB_TOKEN = require_facebook_page_access_token()
RS_BLOG_KEY = require_rs_blog_pipeline_key()

N8N_URL, N8N_API_KEY = require_n8n_api()
headers = {"X-N8N-API-KEY": N8N_API_KEY, "Content-Type": "application/json"}

# Code nodes content
parse_code = (
    'const update = $json.body || $json;\n'
    'const msg = update.message || {};\n'
    'const text = msg.text || "";\n'
    'const chatId = String(msg.chat?.id || "");\n'
    'const fromUser = msg.from?.first_name || "User";\n'
    '\n'
    'const AUTHORIZED_CHAT = "' + TELEGRAM_CHAT + '";\n'
    'if (chatId !== AUTHORIZED_CHAT) {\n'
    '  return [{ json: { skip: true, reason: "Unauthorized: " + chatId } }];\n'
    '}\n'
    '\n'
    'let topic = "";\n'
    'if (text.startsWith("/blog")) {\n'
    '  topic = text.replace(/^\\/blog\\s*/, "").trim();\n'
    '}\n'
    '\n'
    'return [{ json: { topic, autoGenerate: !topic, chatId, fromUser, skip: false } }];'
)

extract_code = (
    'const parseCmd = $node["Parse Command"].json;\n'
    'let topic;\n'
    'if (parseCmd.autoGenerate) {\n'
    '  const aiResp = $json;\n'
    '  topic = aiResp?.choices?.[0]?.message?.content?.trim() || "Diagnostyka komputerowa - kiedy jest niezbedna";\n'
    '} else {\n'
    '  topic = parseCmd.topic;\n'
    '}\n'
    'return [{ json: { topic, chatId: parseCmd.chatId, fromUser: parseCmd.fromUser } }];'
)

format_code = (
    'const r = $json;\n'
    'const result = r?.result || r;\n'
    'const post = result?.post || {};\n'
    'const qg = result?.quality_gate || {};\n'
    'const topic = result?.topic || {};\n'
    'const passed = qg?.passed;\n'
    'const score = qg?.score || "?";\n'
    'const title = topic?.title || post?.title || "Bez tytulu";\n'
    'const slug = post?.slug || "";\n'
    'const blogUrl = slug ? "https://rsperformance.online/blog/" + slug : "";\n'
    'const chatId = $node["Parse Command"].json.chatId;\n'
    '\n'
    'let imageUrl = post?.featured_image || "";\n'
    'if (imageUrl && !imageUrl.startsWith("http")) {\n'
    '  imageUrl = "https://rsperformance.online/storage/" + imageUrl;\n'
    '}\n'
    '\n'
    'const text = "Blog wygenerowany!\\n\\n"\n'
    '  + title + "\\n"\n'
    '  + "Quality Gate: " + (passed ? "PASSED" : "FAILED") + " (score: " + score + ")\\n"\n'
    '  + (blogUrl ? blogUrl + "\\n" : "")\n'
    '  + (passed ? "\\nWpis zapisany jako draft w Filament" : "\\nNie przeszedl quality gate");\n'
    '\n'
    'return [{ json: { text, chatId, title, blogUrl, imageUrl, passed, slug } }];'
)

workflow = {
    "name": "RS Blog on Demand (Telegram)",
    "nodes": [
        {
            "parameters": {
                "httpMethod": "POST",
                "path": "telegram-blog",
                "options": {}
            },
            "id": "tg-webhook",
            "name": "Telegram Webhook",
            "type": "n8n-nodes-base.webhook",
            "typeVersion": 2,
            "position": [0, 0],
            "webhookId": "telegram-blog-trigger"
        },
        {
            "parameters": {"jsCode": parse_code},
            "id": "tg-parse",
            "name": "Parse Command",
            "type": "n8n-nodes-base.code",
            "typeVersion": 2,
            "position": [220, 0]
        },
        {
            "parameters": {
                "conditions": {
                    "options": {"version": 2},
                    "combinator": "and",
                    "conditions": [{
                        "id": "not-skip",
                        "leftValue": "={{ $json.skip }}",
                        "rightValue": True,
                        "operator": {"type": "boolean", "operation": "notEqual"}
                    }]
                }
            },
            "id": "tg-if-auth",
            "name": "Authorized?",
            "type": "n8n-nodes-base.if",
            "typeVersion": 2.2,
            "position": [440, 0]
        },
        {
            "parameters": {
                "method": "POST",
                "url": f"https://api.telegram.org/bot{TELEGRAM_BOT}/sendMessage",
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"chat_id": $json.chatId, "text": "Generuje wpis na blog...\\nTemat: " + ($json.topic || "auto-generowany")}',
                "options": {"timeout": 10000}
            },
            "id": "tg-ack",
            "name": "TG Acknowledge",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [660, -100]
        },
        {
            "parameters": {
                "conditions": {
                    "options": {"version": 2},
                    "combinator": "and",
                    "conditions": [{
                        "id": "needs-topic",
                        "leftValue": '={{ $node["Parse Command"].json.autoGenerate }}',
                        "rightValue": True,
                        "operator": {"type": "boolean", "operation": "equal"}
                    }]
                }
            },
            "id": "tg-needs-topic",
            "name": "Needs Topic?",
            "type": "n8n-nodes-base.if",
            "typeVersion": 2.2,
            "position": [880, -100]
        },
        {
            "parameters": {
                "method": "POST",
                "url": "https://openrouter.ai/api/v1/chat/completions",
                "sendHeaders": True,
                "headerParameters": {
                    "parameters": [
                        {"name": "Authorization", "value": f"Bearer {OPENROUTER_KEY}"},
                        {"name": "Content-Type", "value": "application/json"},
                        {"name": "HTTP-Referer", "value": "https://rsperformance.online"}
                    ]
                },
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"model":"nvidia/llama-3.3-nemotron-super-49b-v1:free","messages":[{"role":"system","content":"Jestes ekspertem motoryzacyjnym. Generujesz 1 temat na artykul blogowy."},{"role":"user","content":"Zaproponuj 1 temat na artykul blogowy o diagnostyce samochodowej. Zwroc TYLKO temat."}],"temperature":0.9,"max_tokens":200}',
                "options": {"timeout": 30000}
            },
            "id": "tg-gen-topic",
            "name": "Generate Topic",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [1100, -200]
        },
        {
            "parameters": {"jsCode": extract_code},
            "id": "tg-extract",
            "name": "Extract Topic",
            "type": "n8n-nodes-base.code",
            "typeVersion": 2,
            "position": [1320, -100]
        },
        {
            "parameters": {
                "method": "POST",
                "url": "https://rsperformance.online/api/blog/pipeline/run",
                "sendHeaders": True,
                "headerParameters": {
                    "parameters": [
                        {"name": "X-RS-Blog-Pipeline-Key", "value": RS_BLOG_KEY},
                        {"name": "Content-Type", "value": "application/json"}
                    ]
                },
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"topic": $json.topic, "create_draft": true, "premium_review": false, "quality_gate_enforced": true}',
                "options": {"timeout": 120000}
            },
            "id": "tg-pipeline",
            "name": "Blog Pipeline",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [1540, -100]
        },
        {
            "parameters": {"jsCode": format_code},
            "id": "tg-format",
            "name": "Format Result",
            "type": "n8n-nodes-base.code",
            "typeVersion": 2,
            "position": [1760, -100]
        },
        {
            "parameters": {
                "method": "POST",
                "url": f"https://api.telegram.org/bot{TELEGRAM_BOT}/sendMessage",
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"chat_id": $json.chatId, "text": $json.text}',
                "options": {"timeout": 10000}
            },
            "id": "tg-result",
            "name": "TG Result",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [1980, -200]
        },
        {
            "parameters": {
                "conditions": {
                    "options": {"version": 2},
                    "combinator": "and",
                    "conditions": [
                        {
                            "id": "has-img",
                            "leftValue": "={{ $json.imageUrl }}",
                            "rightValue": "",
                            "operator": {"type": "string", "operation": "notEquals"}
                        },
                        {
                            "id": "qg-pass",
                            "leftValue": "={{ $json.passed }}",
                            "rightValue": True,
                            "operator": {"type": "boolean", "operation": "equal"}
                        }
                    ]
                }
            },
            "id": "tg-if-fb",
            "name": "Post to FB?",
            "type": "n8n-nodes-base.if",
            "typeVersion": 2.2,
            "position": [1980, 100]
        },
        {
            "parameters": {
                "method": "POST",
                "url": "https://graph.facebook.com/v22.0/1000038949862425/photos",
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"url": $json.imageUrl, "caption": $json.title + "\\n\\n" + $json.blogUrl + "\\n\\n#RSPerformance #DiagnostykaGdansk", "access_token": "' + FB_TOKEN + '"}',
                "options": {"timeout": 15000},
                "onError": "continueRegularOutput"
            },
            "id": "tg-fb-post",
            "name": "Facebook Photo",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [2200, 0]
        }
    ],
    "connections": {
        "Telegram Webhook": {
            "main": [[{"node": "Parse Command", "type": "main", "index": 0}]]
        },
        "Parse Command": {
            "main": [[{"node": "Authorized?", "type": "main", "index": 0}]]
        },
        "Authorized?": {
            "main": [
                [{"node": "TG Acknowledge", "type": "main", "index": 0}],
                []
            ]
        },
        "TG Acknowledge": {
            "main": [[{"node": "Needs Topic?", "type": "main", "index": 0}]]
        },
        "Needs Topic?": {
            "main": [
                [{"node": "Generate Topic", "type": "main", "index": 0}],
                [{"node": "Extract Topic", "type": "main", "index": 0}]
            ]
        },
        "Generate Topic": {
            "main": [[{"node": "Extract Topic", "type": "main", "index": 0}]]
        },
        "Extract Topic": {
            "main": [[{"node": "Blog Pipeline", "type": "main", "index": 0}]]
        },
        "Blog Pipeline": {
            "main": [[{"node": "Format Result", "type": "main", "index": 0}]]
        },
        "Format Result": {
            "main": [[
                {"node": "TG Result", "type": "main", "index": 0},
                {"node": "Post to FB?", "type": "main", "index": 0}
            ]]
        },
        "Post to FB?": {
            "main": [
                [{"node": "Facebook Photo", "type": "main", "index": 0}],
                []
            ]
        }
    },
    "settings": {
        "executionOrder": "v1",
        "saveManualExecutions": True,
        "callerPolicy": "workflowsFromSameOwner"
    },
    "staticData": None
}

print("Creating Telegram Blog on Demand workflow...")
resp = requests.post(
    f"{N8N_URL}/api/v1/workflows",
    headers=headers,
    json=workflow,
    verify=False,
    timeout=30
)

if resp.status_code in (200, 201):
    data = resp.json()
    wf_id = data.get("id")
    print(f"Created: {wf_id} ({len(data.get('nodes', []))} nodes)")

    act = requests.post(
        f"{N8N_URL}/api/v1/workflows/{wf_id}/activate",
        headers=headers,
        verify=False,
        timeout=15
    )
    print(f"Activated: {act.status_code == 200}")

    webhook_url = f"{N8N_URL}/webhook/telegram-blog"
    print(f"\nWebhook URL: {webhook_url}")

    # Register webhook with Telegram Bot API
    print("\nRegistering Telegram webhook...")
    tg_resp = requests.post(
        f"https://api.telegram.org/bot{TELEGRAM_BOT}/setWebhook",
        json={
            "url": webhook_url,
            "allowed_updates": ["message"],
        },
        timeout=15
    )
    tg_data = tg_resp.json()
    print(f"Telegram setWebhook: {tg_data.get('ok')} - {tg_data.get('description')}")

else:
    print(f"Error: {resp.status_code}")
    print(resp.text[:500])
