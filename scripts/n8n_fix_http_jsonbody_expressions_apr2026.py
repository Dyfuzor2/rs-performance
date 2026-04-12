#!/usr/bin/env python3
"""
April 2026+ — Fix HttpRequest jsonBody expressions that break n8n v4.2+ execution.

- Monitor: Notify Diagnosta — parenthesized object literal (v4.2 JSON body validation).
- Daily News: Preview Blog Pipeline — same (avoid JSON.stringify in JSON body mode).
- Daily News: =JSON.stringify(...) -> ={{ JSON.stringify(...) }}.
- Editorial: Gemini + Persist to Blog — valid ={{ }} expressions.

PUT via n8n public API (same credentials as other fleet scripts).
"""

from __future__ import annotations

import sys
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_n8n_api  # noqa: E402


def fix_daily_jsonstring(s: str) -> str:
    s = (s or "").strip()
    if not s.startswith("=JSON.stringify"):
        return s
    s = s.replace("=JSON.stringify", "={{ JSON.stringify", 1)
    if not s.rstrip().endswith("}}"):
        s = s.rstrip() + " }}"
    return s


GEMINI_JSON_BODY = (
    "={{ JSON.stringify({ model: 'gemini-2.5-flash', payload: { contents: [{ role: 'user', "
    "parts: [{ text: $json.prompt }] }], generationConfig: { temperature: 0.7, maxOutputTokens: 8192 } } }) }}"
)

PERSIST_TO_BLOG_JSON_BODY = (
    "={{ JSON.stringify({ topic_pack: { title: $json.draft.title, source: $json.best_article.source, "
    "source_url: $json.best_article.url }, draft: $json.draft, used_models: ['gemini-2.5-pro'], "
    "pipeline_run_id: $json.pipeline_run_id }) }}"
)


def put_workflow(base: str, headers: dict, wf: dict) -> bool:
    wid = wf["id"]
    payload = {
        "name": wf["name"],
        "nodes": wf["nodes"],
        "connections": wf["connections"],
        "settings": wf.get("settings") or {},
        "staticData": wf.get("staticData"),
    }
    r = requests.put(f"{base}/api/v1/workflows/{wid}", headers=headers, json=payload, timeout=120)
    if not r.ok:
        print(f"PUT {wid} failed: {r.status_code} {r.text[:600]}")
        return False
    print(f"PUT OK: {wf.get('name')} ({wid})")
    return True


MONITOR_DIAGNOSTA_BODY = (
    "={{ ({ text: $json.text, source: $json.source, category: $json.category }) }}"
)

# n8n HttpRequest v4.2: "JSON" body mode validates the field as JSON before expressions run;
# JSON.stringify(...) in a string often triggers "not valid JSON". Use a parenthesized object literal.
PREVIEW_BLOG_PIPELINE_JSON_BODY = (
    "={{ ({ topic: $json.topic_for_pipeline, premium_review: true, create_draft: false, "
    "quality_gate_enforced: true, editorial_mode: \"daily_news\", editorial_notes: $json.editorial_notes, "
    "news_date: $json.warsaw_date, source_urls: $json.source_urls }) }}"
)


def patch_monitor(wf: dict) -> int:
    n = 0
    for node in wf.get("nodes") or []:
        if node.get("name") != "Notify Diagnosta":
            continue
        p = node.setdefault("parameters", {})
        cur = (p.get("jsonBody") or "").strip()
        if cur != MONITOR_DIAGNOSTA_BODY:
            p["jsonBody"] = MONITOR_DIAGNOSTA_BODY
            n += 1
            print("  Notify Diagnosta: object-literal jsonBody (v4.2-safe)")
    return n


def patch_daily(wf: dict) -> int:
    n = 0
    for node in wf.get("nodes") or []:
        name = node.get("name") or ""
        if name == "Preview Blog Pipeline" and node.get("type") == "n8n-nodes-base.httpRequest":
            p = node.setdefault("parameters", {})
            cur = (p.get("jsonBody") or "").strip()
            if cur != PREVIEW_BLOG_PIPELINE_JSON_BODY:
                p["jsonBody"] = PREVIEW_BLOG_PIPELINE_JSON_BODY
                p.setdefault("specifyBody", "json")
                print("  Preview Blog Pipeline: object-literal body (fixes v4.2 JSON validation)")
                n += 1
            continue
        if node.get("type") != "n8n-nodes-base.httpRequest":
            continue
        p = node.get("parameters") or {}
        jb = p.get("jsonBody")
        if not jb or not str(jb).startswith("=JSON.stringify"):
            continue
        new_jb = fix_daily_jsonstring(str(jb))
        if new_jb != jb:
            p["jsonBody"] = new_jb
            print(f"  {node.get('name')}: =JSON.stringify -> ={{ ... }}")
            n += 1
    return n


def patch_editorial(wf: dict) -> int:
    n = 0
    for node in wf.get("nodes") or []:
        if node.get("type") != "n8n-nodes-base.httpRequest":
            continue
        name = node.get("name") or ""
        p = node.setdefault("parameters", {})
        if name == "Gemini 2.5 Pro Write":
            p["jsonBody"] = GEMINI_JSON_BODY
            print("  Gemini 2.5 Pro Write: fixed jsonBody")
            n += 1
        elif name == "Persist to Blog":
            p["jsonBody"] = PERSIST_TO_BLOG_JSON_BODY
            print("  Persist to Blog: fixed jsonBody")
            n += 1
    return n


def main() -> int:
    base, key = require_n8n_api()
    headers = {"X-N8N-API-KEY": key, "Content-Type": "application/json"}

    jobs = [
        ("W1xRg73xFDUXYrRI", "RS AI Agent Monitor", patch_monitor),
        ("zo4sIHAUZZseEGkL", "RS Daily Automotive News Drafts", patch_daily),
        ("vktlhlLUVolWBxRs", "RS Editorial Board", patch_editorial),
    ]

    for wid, _label, patcher in jobs:
        r = requests.get(f"{base}/api/v1/workflows/{wid}", headers=headers, timeout=60)
        if not r.ok:
            print(f"GET {wid}: {r.status_code}")
            continue
        wf = r.json()
        count = patcher(wf)
        if count:
            put_workflow(base, headers, wf)
        else:
            print(f"  ({wid}) no changes")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
