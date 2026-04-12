#!/usr/bin/env python3
"""
Deploy Trinity DTC Commentator Crew to n8n
Pattern: Schedule → Fetch Batch → Code (prepare prompts) → HTTP Request (OpenRouter AI) → Code (parse) → HTTP Request (store) → Telegram Report
Uses free OpenRouter models: Trinity-Mini (nousresearch/hermes-3-llama-3.2-3b:free)
"""

from pathlib import Path
import json
import sys

import requests
import urllib3

urllib3.disable_warnings()

_root = Path(__file__).resolve().parent
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import (  # noqa: E402
    require_n8n_api,
    require_openrouter_api_key,
    require_rs_x_api_token,
    require_telegram_operator,
)

N8N_URL, N8N_API_KEY = require_n8n_api()

OPENROUTER_KEY = require_openrouter_api_key()
TELEGRAM_BOT, TELEGRAM_CHAT = require_telegram_operator()
RS_API_TOKEN = require_rs_x_api_token()

headers = {
    "X-N8N-API-KEY": N8N_API_KEY,
    "Content-Type": "application/json"
}

workflow = {
    "name": "RS Trinity DTC Commentator Crew",
    "nodes": [
        {
            "parameters": {
                "rule": {
                    "interval": [{"field": "hours", "hoursInterval": 1}]
                }
            },
            "id": "trinity-trigger",
            "name": "Every Hour",
            "type": "n8n-nodes-base.scheduleTrigger",
            "typeVersion": 1.2,
            "position": [0, 0]
        },
        {
            "parameters": {
                "method": "GET",
                "url": "https://rsperformance.online/api/dtc/batch-for-enrichment",
                "sendQuery": True,
                "queryParameters": {
                    "parameters": [
                        {"name": "batch_size", "value": "50"}
                    ]
                },
                "sendHeaders": True,
                "headerParameters": {
                    "parameters": [
                        {"name": "X-API-Token", "value": RS_API_TOKEN}
                    ]
                },
                "options": {"timeout": 30000}
            },
            "id": "trinity-fetch",
            "name": "Fetch DTC Batch",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [220, 0]
        },
        {
            "parameters": {
                "conditions": {
                    "options": {"version": 2},
                    "combinator": "and",
                    "conditions": [
                        {
                            "id": "check-codes",
                            "leftValue": "={{ $json.count }}",
                            "rightValue": 0,
                            "operator": {
                                "type": "number",
                                "operation": "gt"
                            }
                        }
                    ]
                }
            },
            "id": "trinity-if",
            "name": "Has Codes?",
            "type": "n8n-nodes-base.if",
            "typeVersion": 2.2,
            "position": [440, 0]
        },
        {
            "parameters": {
                "jsCode": """// Prepare batch prompt for OpenRouter Trinity-Mini
const codes = $json.codes;
const stats = $json.stats;

// Build a single mega-prompt with all codes
const codeList = codes.map((c, i) =>
  `${i+1}. ${c.code} (${c.manufacturer}) - ${c.description}`
).join('\\n');

const systemPrompt = `Jesteś "Heniu" - sarkastycznym, doświadczonym mechanikiem z Gdańska z 25-letnim stażem.
Pracujesz w RS Performance - warsztacie specjalizującym się w diagnostyce komputerowej.
Masz sprzęt: Bosch KTS 560, VCDS, PDL 4000, CDIF 3.

Twój styl:
- Sarkastyczny ale merytoryczny
- Używasz polskiego slangu warsztatowego
- Często nawiązujesz do typowych błędów "garażowych mechaników"
- Dajesz konkretne wskazówki diagnostyczne
- Wspominasz o swoim sprzęcie gdy jest relevant
- Krótko (2-4 zdania) ale treściwie
- Czasem dodajesz "Pro tip" dla innych mechaników`;

const userPrompt = `Napisz krótki, sarkastyczny komentarz ekspercki dla KAŻDEGO z poniższych kodów DTC.
Każdy komentarz ma być unikalny, merytoryczny i w Twoim stylu.

FORMAT ODPOWIEDZI (STRICT JSON):
[
  {"code": "PXXXX", "manufacturer": "XXX", "trinity_comment": "Twój komentarz"},
  ...
]

Kody do skomentowania:
${codeList}

WAŻNE: Zwróć TYLKO JSON array, bez żadnego tekstu przed ani po. Dokładnie ${codes.length} elementów.`;

return [{
  json: {
    systemPrompt,
    userPrompt,
    codesCount: codes.length,
    stats,
    codes // pass through for fallback
  }
}];"""
            },
            "id": "trinity-prepare",
            "name": "Prepare AI Prompt",
            "type": "n8n-nodes-base.code",
            "typeVersion": 2,
            "position": [660, -100]
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
                        {"name": "HTTP-Referer", "value": "https://rsperformance.online"},
                        {"name": "X-Title", "value": "RS Trinity DTC Crew"}
                    ]
                },
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={\n  "model": "nousresearch/hermes-3-llama-3.2-3b:free",\n  "messages": [\n    {"role": "system", "content": $json.systemPrompt},\n    {"role": "user", "content": $json.userPrompt}\n  ],\n  "temperature": 0.8,\n  "max_tokens": 8000,\n  "provider": {"order": ["Together", "DeepInfra", "Fireworks"]}\n}',
                "options": {"timeout": 120000}
            },
            "id": "trinity-ai",
            "name": "OpenRouter Trinity-Mini",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [880, -100]
        },
        {
            "parameters": {
                "jsCode": """// Parse AI response and prepare for storage
const aiResponse = $json;
const prevData = $node["Prepare AI Prompt"].json;

let enriched = [];

try {
  const content = aiResponse.choices[0].message.content;

  // Try to extract JSON from response
  let jsonStr = content;

  // Handle markdown code blocks
  const jsonMatch = content.match(/```(?:json)?\\s*([\\s\\S]*?)```/);
  if (jsonMatch) {
    jsonStr = jsonMatch[1].trim();
  }

  // Handle leading/trailing text
  const arrayMatch = jsonStr.match(/\\[([\\s\\S]*?)\\]/);
  if (arrayMatch) {
    jsonStr = arrayMatch[0];
  }

  enriched = JSON.parse(jsonStr);

  // Validate structure
  enriched = enriched.filter(item =>
    item.code && item.trinity_comment && item.trinity_comment.length > 10
  );

} catch (e) {
  // Fallback: if AI response is malformed, log error
  return [{
    json: {
      error: 'AI response parse failed: ' + e.message,
      rawResponse: aiResponse.choices?.[0]?.message?.content?.substring(0, 500),
      enriched: [],
      stats: prevData.stats,
      model: aiResponse.model || 'unknown',
      tokensUsed: aiResponse.usage?.total_tokens || 0
    }
  }];
}

return [{
  json: {
    enriched,
    enrichedCount: enriched.length,
    expectedCount: prevData.codesCount,
    stats: prevData.stats,
    model: aiResponse.model || 'unknown',
    tokensUsed: aiResponse.usage?.total_tokens || 0
  }
}];"""
            },
            "id": "trinity-parse",
            "name": "Parse AI Response",
            "type": "n8n-nodes-base.code",
            "typeVersion": 2,
            "position": [1100, -100]
        },
        {
            "parameters": {
                "conditions": {
                    "options": {"version": 2},
                    "combinator": "and",
                    "conditions": [
                        {
                            "id": "check-enriched",
                            "leftValue": "={{ $json.enrichedCount }}",
                            "rightValue": 0,
                            "operator": {
                                "type": "number",
                                "operation": "gt"
                            }
                        }
                    ]
                }
            },
            "id": "trinity-if-enriched",
            "name": "Has Enriched?",
            "type": "n8n-nodes-base.if",
            "typeVersion": 2.2,
            "position": [1320, -100]
        },
        {
            "parameters": {
                "method": "POST",
                "url": "https://rsperformance.online/api/dtc/store-enrichment",
                "sendHeaders": True,
                "headerParameters": {
                    "parameters": [
                        {"name": "X-API-Token", "value": RS_API_TOKEN},
                        {"name": "Content-Type", "value": "application/json"}
                    ]
                },
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"enriched": $json.enriched}',
                "options": {"timeout": 30000}
            },
            "id": "trinity-store",
            "name": "Store Enrichments",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [1540, -200]
        },
        {
            "parameters": {
                "method": "POST",
                "url": f"https://api.telegram.org/bot{TELEGRAM_BOT}/sendMessage",
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"chat_id": "' + TELEGRAM_CHAT + '", "text": "🔧 Trinity DTC Crew Report\\n\\n✅ Stored: " + $json.updated + "/" + $node["Parse AI Response"].json.expectedCount + " codes\\n📊 Progress: " + $json.stats.progress + "\\n🧮 Remaining: " + $json.stats.remaining + "\\n🤖 Model: " + $node["Parse AI Response"].json.model + "\\n⚡ Tokens: " + $node["Parse AI Response"].json.tokensUsed, "parse_mode": "HTML"}',
                "options": {"timeout": 10000}
            },
            "id": "trinity-telegram-ok",
            "name": "Telegram Success",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [1760, -200]
        },
        {
            "parameters": {
                "method": "POST",
                "url": f"https://api.telegram.org/bot{TELEGRAM_BOT}/sendMessage",
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"chat_id": "' + TELEGRAM_CHAT + '", "text": "⚠️ Trinity DTC: Brak kodów do wzbogacenia lub błąd AI\\n\\nError: " + ($json.error || "No enriched codes"), "parse_mode": "HTML"}',
                "options": {"timeout": 10000}
            },
            "id": "trinity-telegram-fail",
            "name": "Telegram Error",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [1540, 0]
        },
        {
            "parameters": {
                "method": "POST",
                "url": f"https://api.telegram.org/bot{TELEGRAM_BOT}/sendMessage",
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"chat_id": "' + TELEGRAM_CHAT + '", "text": "🏁 Trinity DTC: Wszystkie kody wzbogacone! Database complete.\\nTotal: " + $json.stats.total, "parse_mode": "HTML"}',
                "options": {"timeout": 10000}
            },
            "id": "trinity-telegram-done",
            "name": "Telegram All Done",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [660, 100]
        }
    ],
    "connections": {
        "Every Hour": {
            "main": [[{"node": "Fetch DTC Batch", "type": "main", "index": 0}]]
        },
        "Fetch DTC Batch": {
            "main": [[{"node": "Has Codes?", "type": "main", "index": 0}]]
        },
        "Has Codes?": {
            "main": [
                [{"node": "Prepare AI Prompt", "type": "main", "index": 0}],
                [{"node": "Telegram All Done", "type": "main", "index": 0}]
            ]
        },
        "Prepare AI Prompt": {
            "main": [[{"node": "OpenRouter Trinity-Mini", "type": "main", "index": 0}]]
        },
        "OpenRouter Trinity-Mini": {
            "main": [[{"node": "Parse AI Response", "type": "main", "index": 0}]]
        },
        "Parse AI Response": {
            "main": [[{"node": "Has Enriched?", "type": "main", "index": 0}]]
        },
        "Has Enriched?": {
            "main": [
                [{"node": "Store Enrichments", "type": "main", "index": 0}],
                [{"node": "Telegram Error", "type": "main", "index": 0}]
            ]
        },
        "Store Enrichments": {
            "main": [[{"node": "Telegram Success", "type": "main", "index": 0}]]
        }
    },
    "settings": {
        "executionOrder": "v1",
        "saveManualExecutions": True,
        "callerPolicy": "workflowsFromSameOwner",
        "errorWorkflow": ""
    },
    "staticData": None
}

# Deploy to n8n
print("Creating Trinity DTC Commentator workflow...")
resp = requests.post(
    f"{N8N_URL}/api/v1/workflows",
    headers=headers,
    json=workflow,
    verify=False,
    timeout=30
)

if resp.status_code in (200, 201):
    data = resp.json()
    wf_id = data.get("id", "unknown")
    print(f"✅ Workflow created: {wf_id}")
    print(f"   Name: {data.get('name')}")
    print(f"   Nodes: {len(data.get('nodes', []))}")

    # Activate it
    print("Activating workflow...")
    activate_resp = requests.patch(
        f"{N8N_URL}/api/v1/workflows/{wf_id}",
        headers=headers,
        json={"active": True},
        verify=False,
        timeout=15
    )
    if activate_resp.status_code == 200:
        print(f"✅ Workflow activated!")
    else:
        print(f"⚠️ Activation: {activate_resp.status_code} - {activate_resp.text[:200]}")

    # Trigger first execution manually
    print("Triggering first execution...")
    # We can't trigger schedule directly, but we can test via manual execution
    # For now, the hourly schedule will pick it up
    print(f"🔗 URL: {N8N_URL}/workflow/{wf_id}")

else:
    print(f"❌ Error: {resp.status_code}")
    print(resp.text[:500])
