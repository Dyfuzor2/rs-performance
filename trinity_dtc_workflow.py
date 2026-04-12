"""Create and deploy Trinity DTC Commentator workflow to n8n"""
from pathlib import Path
import sys

import requests, json, uuid

_root = Path(__file__).resolve().parent
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import (  # noqa: E402
    require_n8n_api,
    require_openrouter_api_key,
    require_rs_x_api_token,
    require_telegram_operator,
)

base, N8N_KEY = require_n8n_api()
OR_KEY = require_openrouter_api_key()
TELEGRAM_BOT, TELEGRAM_CHAT = require_telegram_operator()
DIAG_TOKEN = require_rs_x_api_token()

headers = {"X-N8N-API-KEY": N8N_KEY}

build_prompt_code = r'''const batch = $input.first().json;
const codes = batch.codes || batch.data || [];

if (!codes.length) {
  return [{ json: { done: true, message: "No more codes to process" } }];
}

const codeList = codes.map(c =>
  `${c.code} (${c.manufacturer || "GENERIC"}): ${c.description}`
).join("\n");

const prompt = `Jestes Trinity - sarkastycznym ekspertem diagnostyki samochodowej z 25-letnim doswiadczeniem w RS Performance Gdansk. Dla KAZDEGO kodu DTC ponizej napisz KROTKI (1-2 zdania) sarkastyczny komentarz ekspercki po polsku. Komentarz ma byc:
- Techniczny ale z humorem warsztatowym
- Zawierac praktyczna porade
- Brzmiacy jak doswiadczony mechanik ktory widzial juz wszystko

Format odpowiedzi - JSON array:
[{"code": "PXXXX", "trinity_comment": "komentarz"}]

Kody do skomentowania:
${codeList}`;

return [{ json: { prompt, codes, count: codes.length } }];'''

parse_merge_code = r'''const response = $input.first().json;
const content = response?.choices?.[0]?.message?.content || "";
const originalCodes = $node["Build Trinity Prompt"].json.codes;

let comments = [];
try {
  const match = content.match(/\[\s*\{.*\}\s*\]/s);
  if (match) {
    comments = JSON.parse(match[0]);
  }
} catch(e) {
  comments = [];
}

const enriched = originalCodes.map(orig => {
  const comment = comments.find(c => c.code === orig.code);
  return {
    code: orig.code,
    manufacturer: orig.manufacturer || "GENERIC",
    description: orig.description,
    trinity_comment: comment?.trinity_comment || null,
    enriched_at: new Date().toISOString()
  };
});

const withComments = enriched.filter(e => e.trinity_comment);

return [{
  json: {
    enriched,
    stats: {
      total: enriched.length,
      commented: withComments.length,
      success_rate: Math.round(withComments.length / enriched.length * 100) + "%"
    }
  }
}];'''

wf = {
    "name": "RS DTC Trinity Commentator",
    "nodes": [
        {
            "parameters": {
                "rule": {"interval": [{"field": "hours", "hoursInterval": 1}]}
            },
            "type": "n8n-nodes-base.scheduleTrigger",
            "typeVersion": 1.2,
            "position": [0, 0],
            "id": str(uuid.uuid4()),
            "name": "Every Hour"
        },
        {
            "parameters": {
                "method": "POST",
                "url": "https://rsperformance.online/api/dtc/batch-for-enrichment",
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '{"batch_size": 50}',
                "options": {"timeout": 15000},
                "onError": "continueRegularOutput"
            },
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [220, 0],
            "id": str(uuid.uuid4()),
            "name": "Fetch DTC Batch"
        },
        {
            "parameters": {"code": build_prompt_code},
            "type": "n8n-nodes-base.code",
            "typeVersion": 2,
            "position": [440, 0],
            "id": str(uuid.uuid4()),
            "name": "Build Trinity Prompt"
        },
        {
            "parameters": {
                "method": "POST",
                "url": "https://openrouter.ai/api/v1/chat/completions",
                "sendHeaders": True,
                "headerParameters": {
                    "parameters": [
                        {"name": "Authorization", "value": f"Bearer {OR_KEY}"},
                        {"name": "Content-Type", "value": "application/json"},
                        {"name": "HTTP-Referer", "value": "https://rsperformance.online"},
                        {"name": "X-Title", "value": "RS Performance DTC Enrichment"}
                    ]
                },
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"model": "arcee-ai/trinity-mini:free", "messages": [{"role": "user", "content": $json.prompt}], "temperature": 0.8, "max_tokens": 4000}',
                "options": {"timeout": 120000},
                "onError": "continueRegularOutput"
            },
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [660, 0],
            "id": str(uuid.uuid4()),
            "name": "Trinity-Mini Generate"
        },
        {
            "parameters": {"code": parse_merge_code},
            "type": "n8n-nodes-base.code",
            "typeVersion": 2,
            "position": [880, 0],
            "id": str(uuid.uuid4()),
            "name": "Parse & Merge"
        },
        {
            "parameters": {
                "method": "POST",
                "url": f"https://mcp.rs3d.pl/internal/diagnostic-store",
                "sendHeaders": True,
                "headerParameters": {
                    "parameters": [
                        {"name": "X-API-Token", "value": DIAG_TOKEN},
                        {"name": "Content-Type", "value": "application/json"}
                    ]
                },
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"text": "DTC ENRICHMENT BATCH: " + $json.stats.total + " codes, " + $json.stats.commented + " enriched (" + $json.stats.success_rate + ")", "source": "trinity-commentator", "category": "dtc-enrichment"}',
                "options": {"timeout": 30000},
                "onError": "continueRegularOutput"
            },
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [1100, 0],
            "id": str(uuid.uuid4()),
            "name": "Store in Qdrant"
        },
        {
            "parameters": {
                "method": "POST",
                "url": f"https://api.telegram.org/bot{TELEGRAM_BOT}/sendMessage",
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"chat_id": "' + TELEGRAM_CHAT + '", "text": "\\ud83d\\udd27 *Trinity DTC Batch*\\n" + $json.stats.total + " codes, " + $json.stats.commented + " enriched (" + $json.stats.success_rate + ")\\n" + new Date().toLocaleString("pl-PL", {timeZone: "Europe/Warsaw"}), "parse_mode": "Markdown"}',
                "options": {"timeout": 10000},
                "onError": "continueRegularOutput"
            },
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [1100, 200],
            "id": str(uuid.uuid4()),
            "name": "Telegram Report"
        }
    ],
    "connections": {
        "Every Hour": {"main": [[{"node": "Fetch DTC Batch", "type": "main", "index": 0}]]},
        "Fetch DTC Batch": {"main": [[{"node": "Build Trinity Prompt", "type": "main", "index": 0}]]},
        "Build Trinity Prompt": {"main": [[{"node": "Trinity-Mini Generate", "type": "main", "index": 0}]]},
        "Trinity-Mini Generate": {"main": [[{"node": "Parse & Merge", "type": "main", "index": 0}]]},
        "Parse & Merge": {"main": [[
            {"node": "Store in Qdrant", "type": "main", "index": 0},
            {"node": "Telegram Report", "type": "main", "index": 0}
        ]]}
    },
    "settings": {"executionOrder": "v1"}
}

r = requests.post(f"{base}/api/v1/workflows", headers=headers, json=wf)
print(f"Create Trinity workflow: {r.status_code}")
if r.status_code in [200, 201]:
    data = r.json()
    wf_id = data.get("id")
    print(f"ID: {wf_id}")
    print(f"Nodes: {[n['name'] for n in data.get('nodes', [])]}")

    # Activate
    r2 = requests.patch(f"{base}/api/v1/workflows/{wf_id}", headers=headers, json={"active": True})
    print(f"Activate: {r2.status_code}")
else:
    print(r.text[:500])
