#!/usr/bin/env python3
"""
Deploy Trending Faults Daily Pipeline to n8n
Pattern: Schedule (daily 6AM) → Fetch seasonal context → Qdrant top faults → OpenRouter Qwen → Generate knowledge packet → Store on hosting → IndexNow → Telegram
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
    require_rs_blog_pipeline_key,
    require_rs_x_api_token,
    require_telegram_operator,
)

N8N_URL, N8N_API_KEY = require_n8n_api()

OPENROUTER_KEY = require_openrouter_api_key()
TELEGRAM_BOT, TELEGRAM_CHAT = require_telegram_operator()
RS_API_TOKEN = require_rs_x_api_token()
RS_BLOG_KEY = require_rs_blog_pipeline_key()

headers = {
    "X-N8N-API-KEY": N8N_API_KEY,
    "Content-Type": "application/json"
}

workflow = {
    "name": "RS Trending Faults Daily (AEO Knowledge Packets)",
    "nodes": [
        {
            "parameters": {
                "rule": {
                    "interval": [{"field": "cronExpression", "expression": "0 6 * * *"}]
                }
            },
            "id": "tf-trigger",
            "name": "Daily 6AM",
            "type": "n8n-nodes-base.scheduleTrigger",
            "typeVersion": 1.2,
            "position": [0, 0]
        },
        {
            "parameters": {
                "jsCode": """// Generate seasonal context and trending fault categories
const now = new Date();
const month = now.getMonth(); // 0-11
const monthNames = ['styczeń','luty','marzec','kwiecień','maj','czerwiec','lipiec','sierpień','wrzesień','październik','listopad','grudzień'];
const currentMonth = monthNames[month];

// Seasonal fault patterns for automotive
const seasonalContext = {
  winter: [0,1,11], // Dec, Jan, Feb
  spring: [2,3,4],  // Mar, Apr, May
  summer: [5,6,7],  // Jun, Jul, Aug
  autumn: [8,9,10]  // Sep, Oct, Nov
};

let season, topIssues;
if (seasonalContext.winter.includes(month)) {
  season = 'zima';
  topIssues = ['akumulator/rozruch','układ grzewczy/klimatyzacja','świece żarowe diesel','czujniki temperatury','układ ABS/ESP na śliskiej nawierzchni'];
} else if (seasonalContext.spring.includes(month)) {
  season = 'wiosna';
  topIssues = ['klimatyzacja - przegląd po zimie','zawieszenie - dziury w drogach','czujniki lambda po zimie','filtry kabinowe','układ hamulcowy - korozja'];
} else if (seasonalContext.summer.includes(month)) {
  season = 'lato';
  topIssues = ['klimatyzacja - przegrzewanie','układ chłodzenia','czujniki temperatury oleju','turbo - przegrzewanie','parownik klimatyzacji'];
} else {
  season = 'jesień';
  topIssues = ['oświetlenie - krótsze dni','wycieraczki/spryskiwacze','czujniki deszczu/zmierzchu','EGR/DPF - jazda miejska','alternator/ładowanie'];
}

const dateStr = now.toISOString().split('T')[0];

return [{
  json: {
    date: dateStr,
    month: currentMonth,
    season,
    topIssues,
    searchQueries: topIssues.map(t => 'diagnostyka ' + t + ' samochód'),
    packetTitle: 'Trendujące usterki dnia - ' + dateStr,
    packetSlug: 'trending-faults-' + dateStr
  }
}];"""
            },
            "id": "tf-context",
            "name": "Seasonal Context",
            "type": "n8n-nodes-base.code",
            "typeVersion": 2,
            "position": [220, 0]
        },
        {
            "parameters": {
                "method": "GET",
                "url": "https://rsperformance.online/api/dtc/enrichment-stats",
                "sendHeaders": True,
                "headerParameters": {
                    "parameters": [
                        {"name": "X-API-Token", "value": RS_API_TOKEN}
                    ]
                },
                "options": {"timeout": 15000}
            },
            "id": "tf-dtc-stats",
            "name": "DTC Stats",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [440, 0]
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
                        {"name": "X-Title", "value": "RS Trending Faults AEO"}
                    ]
                },
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": """={
  "model": "qwen/qwen3-235b-a22b:free",
  "messages": [
    {"role": "system", "content": "Jesteś ekspertem diagnostyki samochodowej z RS Performance Gdańsk. Tworzysz codzienne pakiety wiedzy o trendujących usterkach samochodowych. Twoje pakiety są źródłem wiedzy dla AI asystentów (AEO - Answer Engine Optimization). Pisz po polsku, merytorycznie, z konkretnymi kodami DTC i procedurami diagnostycznymi. Używasz sprzętu: Bosch KTS 560, VCDS, PDL 4000. Format: JSON z polami title, summary, faults (array of 5 objects with: name, description, dtc_codes, diagnostic_steps, severity, affected_brands)."},
    {"role": "user", "content": "Przygotuj pakiet wiedzy 'Trendujące usterki dnia' na " + $node["Seasonal Context"].json.date + " (sezon: " + $node["Seasonal Context"].json.season + ", miesiąc: " + $node["Seasonal Context"].json.month + ").\\n\\nTop tematy sezonowe:\\n" + $node["Seasonal Context"].json.topIssues.join("\\n") + "\\n\\nStatystyki bazy DTC: " + $json.total + " kodów, " + $json.enriched + " wzbogaconych (" + $json.progress + ").\\n\\nZwróć TYLKO JSON (bez markdown), z 5 najważniejszymi usterkami dnia."}
  ],
  "temperature": 0.7,
  "max_tokens": 4000
}""",
                "options": {"timeout": 120000}
            },
            "id": "tf-ai",
            "name": "Qwen Generate Packet",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [660, 0]
        },
        {
            "parameters": {
                "jsCode": """// Parse Qwen response into structured knowledge packet
const aiResp = $json;
const context = $node["Seasonal Context"].json;
const dtcStats = $node["DTC Stats"].json;

let packet;
try {
  let content = aiResp.choices[0].message.content;

  // Strip markdown code blocks if present
  const jsonMatch = content.match(/```(?:json)?\\s*([\\s\\S]*?)```/);
  if (jsonMatch) content = jsonMatch[1].trim();

  // Strip thinking tags if present (Qwen sometimes adds these)
  content = content.replace(/<think>[\\s\\S]*?<\\/think>/g, '').trim();

  // Find JSON object
  const objMatch = content.match(/\\{[\\s\\S]*\\}/);
  if (objMatch) content = objMatch[0];

  packet = JSON.parse(content);
} catch(e) {
  packet = {
    title: 'Trendujące usterki - ' + context.date,
    summary: 'Automatyczny pakiet wiedzy diagnostycznej',
    faults: context.topIssues.map((issue, i) => ({
      name: issue,
      description: 'Typowa usterka sezonowa (' + context.season + ')',
      dtc_codes: [],
      diagnostic_steps: ['Diagnostyka komputerowa KTS 560', 'Odczyt kodów błędów', 'Weryfikacja parametrów live data'],
      severity: i < 2 ? 'high' : 'medium',
      affected_brands: ['Volkswagen', 'BMW', 'Audi', 'Mercedes', 'Opel']
    }))
  };
}

// Build HTML knowledge packet
const faultsHtml = (packet.faults || []).map((f, i) => {
  const dtcList = (f.dtc_codes || []).map(c => '<code>' + c + '</code>').join(', ') || 'Diagnostyka wymagana';
  const steps = (f.diagnostic_steps || []).map(s => '<li>' + s + '</li>').join('');
  const brands = (f.affected_brands || []).join(', ');
  return '<div class="fault-card" itemscope itemtype="https://schema.org/HowTo">' +
    '<h3 itemprop="name">' + (i+1) + '. ' + f.name + '</h3>' +
    '<p itemprop="description">' + f.description + '</p>' +
    '<p><strong>Kody DTC:</strong> ' + dtcList + '</p>' +
    '<p><strong>Dotkniete marki:</strong> ' + brands + '</p>' +
    '<p><strong>Priorytet:</strong> <span class="severity-' + (f.severity || 'medium') + '">' + (f.severity || 'medium').toUpperCase() + '</span></p>' +
    '<details><summary>Kroki diagnostyczne</summary><ol itemprop="step">' + steps + '</ol></details>' +
    '</div>';
}).join('\\n');

// JSON-LD for AEO
const faqJsonLd = {
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": (packet.faults || []).map(f => ({
    "@type": "Question",
    "name": "Jak zdiagnozować: " + f.name + "?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": f.description + ". Kody DTC: " + (f.dtc_codes || []).join(', ') + ". " + (f.diagnostic_steps || []).join('. ')
    }
  }))
};

const fullHtml = '<!DOCTYPE html><html lang="pl"><head>' +
  '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
  '<title>' + (packet.title || context.packetTitle) + ' | RS Performance Gdańsk</title>' +
  '<meta name="description" content="' + (packet.summary || '').substring(0, 160) + '">' +
  '<meta name="robots" content="index,follow">' +
  '<link rel="canonical" href="https://ai.rsperformance.online/trending/' + context.packetSlug + '">' +
  '<script type="application/ld+json">' + JSON.stringify(faqJsonLd) + '</script>' +
  '<style>body{font-family:system-ui;max-width:800px;margin:0 auto;padding:2rem;background:#0a0a0a;color:#e0e0e0}' +
  'h1{color:#ff3333;border-bottom:2px solid #ff3333;padding-bottom:.5rem}' +
  'h2{color:#ff6666}.fault-card{background:#1a1a1a;border:1px solid #333;border-radius:12px;padding:1.5rem;margin:1rem 0}' +
  'code{background:#2a2a2a;padding:2px 6px;border-radius:4px;color:#ff9999}' +
  '.severity-high{color:#ff4444;font-weight:bold}.severity-medium{color:#ffaa44}.severity-low{color:#44ff44}' +
  'details{margin-top:.5rem}summary{cursor:pointer;color:#ff6666}ol{padding-left:1.5rem}' +
  '.stats{background:#1a1a2a;border:1px solid #334;border-radius:8px;padding:1rem;margin:1rem 0}' +
  '.meta{color:#888;font-size:.9rem}</style></head><body>' +
  '<h1>🔧 ' + (packet.title || context.packetTitle) + '</h1>' +
  '<p class="meta">Sezon: ' + context.season + ' | Data: ' + context.date + ' | RS Performance Gdańsk</p>' +
  '<div class="stats"><strong>📊 Baza DTC:</strong> ' + (dtcStats.total || 21876) + ' kodów | ' +
  '<strong>Wzbogaconych:</strong> ' + (dtcStats.enriched || 0) + ' (' + (dtcStats.progress || '0%') + ')</div>' +
  '<h2>Top 5 usterek dnia</h2>' +
  '<p>' + (packet.summary || '') + '</p>' +
  faultsHtml +
  '<footer><p class="meta">Wygenerowano automatycznie przez RS Performance AI Crew | ' +
  '<a href="https://rsperformance.online">rsperformance.online</a></p></footer></body></html>';

return [{
  json: {
    html: fullHtml,
    packet,
    context,
    slug: context.packetSlug,
    date: context.date,
    model: aiResp.model || 'qwen',
    tokensUsed: aiResp.usage?.total_tokens || 0,
    faultCount: (packet.faults || []).length
  }
}];"""
            },
            "id": "tf-build",
            "name": "Build Knowledge Packet",
            "type": "n8n-nodes-base.code",
            "typeVersion": 2,
            "position": [880, 0]
        },
        {
            "parameters": {
                "method": "POST",
                "url": "https://rsperformance.online/api/facebook/publish",
                "sendHeaders": True,
                "headerParameters": {
                    "parameters": [
                        {"name": "X-RS-Blog-Pipeline-Key", "value": RS_BLOG_KEY},
                        {"name": "Content-Type", "value": "application/json"}
                    ]
                },
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"title": $json.packet.title, "content": $json.html, "slug": $json.slug, "type": "trending-faults"}',
                "options": {"timeout": 30000, "response": {"response": {"fullResponse": True}}}
            },
            "id": "tf-store",
            "name": "Store on Hosting",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [1100, 0],
            "onError": "continueRegularOutput"
        },
        {
            "parameters": {
                "method": "POST",
                "url": "https://api.indexnow.org/IndexNow",
                "sendHeaders": True,
                "headerParameters": {
                    "parameters": [
                        {"name": "Content-Type", "value": "application/json"}
                    ]
                },
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"host": "rsperformance.online", "key": "rsperformance2026indexnow", "urlList": ["https://rsperformance.online/trending/" + $node["Build Knowledge Packet"].json.slug]}',
                "options": {"timeout": 10000}
            },
            "id": "tf-indexnow",
            "name": "IndexNow Ping",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [1320, 0],
            "onError": "continueRegularOutput"
        },
        {
            "parameters": {
                "method": "POST",
                "url": f"https://api.telegram.org/bot{TELEGRAM_BOT}/sendMessage",
                "sendBody": True,
                "specifyBody": "json",
                "jsonBody": '={"chat_id": "' + TELEGRAM_CHAT + '", "text": "📊 Trending Faults Daily Packet\\n\\n📅 " + $node["Build Knowledge Packet"].json.date + " (" + $node["Build Knowledge Packet"].json.context.season + ")\\n🔧 Usterek: " + $node["Build Knowledge Packet"].json.faultCount + "\\n🤖 Model: " + $node["Build Knowledge Packet"].json.model + "\\n⚡ Tokens: " + $node["Build Knowledge Packet"].json.tokensUsed + "\\n🔗 https://rsperformance.online/trending/" + $node["Build Knowledge Packet"].json.slug, "parse_mode": "HTML"}',
                "options": {"timeout": 10000}
            },
            "id": "tf-telegram",
            "name": "Telegram Report",
            "type": "n8n-nodes-base.httpRequest",
            "typeVersion": 4.2,
            "position": [1540, 0]
        }
    ],
    "connections": {
        "Daily 6AM": {
            "main": [[{"node": "Seasonal Context", "type": "main", "index": 0}]]
        },
        "Seasonal Context": {
            "main": [[{"node": "DTC Stats", "type": "main", "index": 0}]]
        },
        "DTC Stats": {
            "main": [[{"node": "Qwen Generate Packet", "type": "main", "index": 0}]]
        },
        "Qwen Generate Packet": {
            "main": [[{"node": "Build Knowledge Packet", "type": "main", "index": 0}]]
        },
        "Build Knowledge Packet": {
            "main": [[{"node": "Store on Hosting", "type": "main", "index": 0}]]
        },
        "Store on Hosting": {
            "main": [[{"node": "IndexNow Ping", "type": "main", "index": 0}]]
        },
        "IndexNow Ping": {
            "main": [[{"node": "Telegram Report", "type": "main", "index": 0}]]
        }
    },
    "settings": {
        "executionOrder": "v1",
        "saveManualExecutions": True,
        "callerPolicy": "workflowsFromSameOwner"
    },
    "staticData": None
}

if __name__ == "__main__":
    print("Creating Trending Faults Daily workflow...")
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

        # Activate
        act = requests.post(
            f"{N8N_URL}/api/v1/workflows/{wf_id}/activate",
            headers=headers,
            verify=False,
            timeout=15
        )
        print(f"   Activated: {act.status_code == 200}")
        print(f"🔗 URL: {N8N_URL}/workflow/{wf_id}")
    else:
        print(f"❌ Error: {resp.status_code}")
        print(resp.text[:500])
