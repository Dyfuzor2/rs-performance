#!/usr/bin/env python3
"""
April 2026+ — patch HttpRequest jsonBody fields that use legacy `={...}` (invalid JSON
for HttpRequest v4.2+) with `={{ JSON.stringify({...}) }}` patterns.

Targeted workflows: Trinity DTC, Trending Faults, Content, SEO-AEO, Blog on Demand (Telegram).

Requires: .cursor/mcp.env (N8N_API_URL, N8N_API_KEY)
"""

from __future__ import annotations

import re
import sys
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_n8n_api  # noqa: E402

# workflow_id -> node_name -> new jsonBody (n8n expression)
PATCHES: dict[str, dict[str, str]] = {
    "3szHjQMqRDz7isnw": {
        "Store Enrichments": "={{ JSON.stringify({ enriched: $json.enriched }) }}",
        "Telegram Success": (
            "={{ JSON.stringify({ chat_id: '6534705697', text: "
            "'🔥 Trinity DTC Crew Report\\n\\n✅ Stored: ' + $json.updated + '/' + "
            "$node['Parse AI Response'].json.expectedCount + ' codes\\n📈 Progress: ' + "
            "$json.stats.progress + '\\n⏳ Remaining: ' + $json.stats.remaining + "
            "'\\n🤖 Model: ' + $node['Parse AI Response'].json.model + '\\n⚡ Tokens: ' + "
            "$node['Parse AI Response'].json.tokensUsed, parse_mode: 'HTML' }) }}"
        ),
        "Telegram Error": (
            "={{ JSON.stringify({ chat_id: '6534705697', text: "
            "'⚠️ Trinity DTC: Brak kodów do wzbogacenia lub błąd AI\\n\\nError: ' + "
            "($json.error || 'No enriched codes'), parse_mode: 'HTML' }) }}"
        ),
        "Telegram All Done": (
            "={{ JSON.stringify({ chat_id: '6534705697', text: "
            "'🏁 Trinity DTC: Wszystkie kody wzbogacone! Database complete.\\nTotal: ' + "
            "$json.stats.total, parse_mode: 'HTML' }) }}"
        ),
    },
    "crLLcdEAfxYEiNA9": {
        "Store on Hosting": (
            "={{ JSON.stringify({ title: $json.packet.title, content: $json.html, "
            "slug: $json.slug, type: 'trending-faults' }) }}"
        ),
        "IndexNow Ping": (
            "={{ JSON.stringify({ host: 'rsperformance.online', "
            "key: 'rsperformance2026indexnow', urlList: ["
            "'https://rsperformance.online/trending/' + $node['Build Knowledge Packet'].json.slug] }) }}"
        ),
        "Telegram Report": (
            "={{ JSON.stringify({ chat_id: '6534705697', text: "
            "'📊 Trending Faults Daily Packet\\n\\n📅 ' + $node['Build Knowledge Packet'].json.date + "
            "' (' + $node['Build Knowledge Packet'].json.context.season + ')\\n🔧 Usterek: ' + "
            "$node['Build Knowledge Packet'].json.faultCount + '\\n🤖 Model: ' + "
            "$node['Build Knowledge Packet'].json.model + '\\n🔢 Tokens: ' + "
            "$node['Build Knowledge Packet'].json.tokensUsed + '\\n🔗 https://rsperformance.online/trending/' + "
            "$node['Build Knowledge Packet'].json.slug, parse_mode: 'HTML' }) }}"
        ),
    },
    "9Kvb4S6vmp4MJJyq": {
        "Blog Pipeline": (
            "={{ JSON.stringify({ topic: $json.topic, create_draft: true, premium_review: false, "
            "quality_gate_enforced: true }) }}"
        ),
        "Telegram Report": (
            "={{ JSON.stringify({ chat_id: '6534705697', text: $json.text, parse_mode: 'Markdown' }) }}"
        ),
    },
    "yE7tLieYNJa4FDW3": {
        "Telegram SEO Report": (
            "={{ JSON.stringify({ chat_id: '6534705697', text: $json.text, parse_mode: 'Markdown' }) }}"
        ),
        "Store SEO Issues": (
            "={{ JSON.stringify({ text: 'SEO-AEO AUDIT: ' + $json.text.substring(0, 500), "
            "source: 'agent-seo-aeo', category: 'seo-audit' }) }}"
        ),
    },
    "VPTe4OfoyBuGgddS": {
        "Generate Topic": (
            "={{ JSON.stringify({ model: 'nvidia/llama-3.3-nemotron-super-49b-v1:free', "
            "messages: [{ role: 'system', content: "
            "'Jestes ekspertem motoryzacyjnym. Generujesz 1 temat na artykul blogowy.' }, "
            "{ role: 'user', content: "
            "'Zaproponuj 1 temat na artykul blogowy o diagnostyce samochodowej. Zwroc TYLKO temat.' }], "
            "temperature: 0.9, max_tokens: 200 }) }}"
        ),
    },
}


def _escape_js_single(s: str) -> str:
    return s.replace("\\", "\\\\").replace("'", "\\'")


def patch_blog_telegram_workflow(base: str, headers: dict, wf_id: str) -> bool:
    """RS Blog on Demand: fix HttpRequest jsonBody; Facebook token read from existing node (no hardcoding)."""
    r = requests.get(f"{base}/api/v1/workflows/{wf_id}", headers=headers, timeout=60)
    if not r.ok:
        print(f"GET {wf_id} failed {r.status_code}")
        return False
    wf = r.json()
    token: str | None = None
    for node in wf.get("nodes") or []:
        if node.get("name") == "Facebook Photo":
            old = str((node.get("parameters") or {}).get("jsonBody") or "")
            m = re.search(r'access_token":\s*"([^"]+)"', old)
            if not m:
                m = re.search(r'access_token\\":\s*\\"([^"\\]+)', old)
            if m:
                token = m.group(1)
            break

    changed = 0
    for node in wf.get("nodes") or []:
        if node.get("type") != "n8n-nodes-base.httpRequest":
            continue
        name = node.get("name") or ""
        p = node.setdefault("parameters", {})
        if name == "TG Acknowledge":
            p["jsonBody"] = (
                "={{ JSON.stringify({ chat_id: $json.chatId, text: "
                "'Generuje wpis na blog...\\nTemat: ' + ($json.topic || 'auto-generowany') }) }}"
            )
            changed += 1
        elif name == "Blog Pipeline":
            p["jsonBody"] = (
                "={{ JSON.stringify({ topic: $json.topic, create_draft: true, "
                "premium_review: false, quality_gate_enforced: true }) }}"
            )
            changed += 1
        elif name == "TG Result":
            p["jsonBody"] = "={{ JSON.stringify({ chat_id: $json.chatId, text: $json.text }) }}"
            changed += 1
        elif name == "Facebook Photo" and token:
            tok = _escape_js_single(token)
            p["jsonBody"] = (
                "={{ JSON.stringify({ url: $json.imageUrl, caption: $json.title + '\\n\\n' + $json.blogUrl + "
                "'\\n\\n#RSPerformance #DiagnostykaGdansk', access_token: '"
                + tok
                + "' }) }}"
            )
            changed += 1

    if changed == 0:
        print(f"{wf_id}: blog patch — no nodes updated")
        return True
    print(f"  blog workflow {wf_id}: patched {changed} node(s)")
    return put_workflow(base, headers, wf)


def put_workflow(base: str, headers: dict, wf: dict) -> bool:
    wid = wf["id"]
    payload = {
        "name": wf["name"],
        "nodes": wf["nodes"],
        "connections": wf["connections"],
        "settings": wf.get("settings", {}),
        "staticData": wf.get("staticData"),
    }
    r = requests.put(f"{base}/api/v1/workflows/{wid}", headers=headers, json=payload, timeout=120)
    if not r.ok:
        print(f"PUT {wid} failed: {r.status_code} {r.text[:600]}")
        return False
    print(f"PUT OK: {wf['name']}")
    return True


def main() -> int:
    base, key = require_n8n_api()
    headers = {"X-N8N-API-KEY": key, "Content-Type": "application/json"}
    ok = True
    for wid, nodes in PATCHES.items():
        r = requests.get(f"{base}/api/v1/workflows/{wid}", headers=headers, timeout=60)
        if not r.ok:
            print(f"GET {wid} failed {r.status_code}")
            ok = False
            continue
        wf = r.json()
        changed = 0
        for node in wf.get("nodes") or []:
            name = node.get("name") or ""
            if name not in nodes:
                continue
            if node.get("type") != "n8n-nodes-base.httpRequest":
                continue
            new_body = nodes[name]
            node.setdefault("parameters", {})["jsonBody"] = new_body
            changed += 1
            print(f"  patched: {name}")
        if changed == 0:
            print(f"{wid}: no matching nodes (skipped)")
            continue
        ok = put_workflow(base, headers, wf) and ok

    for blog_id in ("BiCeE7CqPPfVsad0", "VPTe4OfoyBuGgddS"):
        ok = patch_blog_telegram_workflow(base, headers, blog_id) and ok

    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
