#!/usr/bin/env python3
"""
RS n8n + Telegram wow-smoke (April 2026+): mirror RS AI Agent Monitor URLs + invitation webhook.
Run on VPS only. Does not print bot tokens.
"""
from __future__ import annotations

import json
import re
import sqlite3
import sys
import urllib.error
import urllib.request

DB = "/srv/ops-stack/n8n/storage/database.sqlite"

MONITOR_CHECKS = [
    ("Website", "https://rsperformance.online", 200),
    ("AI Gateway", "https://ai.rsperformance.online/healthz", 200),
    ("MCP Server", "https://mcp.rs3d.pl/healthz", 200),
    ("n8n", "https://auto.rs3d.pl/healthz", 200),
    ("Umami", "https://analytics.rs3d.pl", 200),
    ("Uptime Kuma", "https://status.rs3d.pl", 200),
    ("Freshness", "https://ai.rsperformance.online/.well-known/freshness.json", 200),
    ("llms.txt", "https://rsperformance.online/llms.txt", 200),
    ("agent.json", "https://rsperformance.online/.well-known/agent.json", 200),
    ("ai-resources", "https://rsperformance.online/.well-known/ai-resources.json", 200),
]


def curl_status(url: str, timeout: int = 25) -> tuple[int, str]:
    req = urllib.request.Request(url, method="GET")
    try:
        with urllib.request.urlopen(req, timeout=timeout) as r:
            return r.status, "ok"
    except urllib.error.HTTPError as e:
        return e.code, "http_error"
    except urllib.error.URLError as e:
        return -1, str(e.reason)[:80]


def post_json(url: str, body: dict, timeout: int = 60) -> tuple[int, str]:
    data = json.dumps(body).encode()
    req = urllib.request.Request(
        url,
        data=data,
        method="POST",
        headers={"Content-Type": "application/json"},
    )
    try:
        with urllib.request.urlopen(req, timeout=timeout) as r:
            return r.status, r.read()[:300].decode(errors="replace")
    except urllib.error.HTTPError as e:
        return e.code, e.read()[:300].decode(errors="replace")
    except urllib.error.URLError as e:
        return -1, str(e.reason)[:120]


def telegram_from_monitor_workflow() -> tuple[str, str] | None:
    """Return (send_message_url, chat_id) parsed from RS AI Agent Monitor workflow."""
    con = sqlite3.connect(DB)
    row = con.execute(
        "SELECT nodes FROM workflow_entity WHERE id = ?", ("W1xRg73xFDUXYrRI",)
    ).fetchone()
    con.close()
    if not row:
        return None
    nodes = json.loads(row[0])
    for n in nodes:
        if n.get("type") != "n8n-nodes-base.httpRequest":
            continue
        p = n.get("parameters") or {}
        url = (p.get("url") or "").strip()
        if "api.telegram.org" not in url or url.startswith("="):
            continue
        body = p.get("jsonBody") or ""
        m_chat = re.search(r"chat_id:\s*['\"]([0-9-]+)['\"]", body)
        if not m_chat:
            m_chat = re.search(r"chat_id:\s*([0-9-]+)", body)
        chat = m_chat.group(1) if m_chat else ""
        if chat:
            return url, chat
    return None


def main() -> None:
    print("=== RS WOW SMOKE — monitor matrix (same URLs as n8n Define Health Checks) ===")
    bad = []
    for name, url, want in MONITOR_CHECKS:
        code, note = curl_status(url)
        ok = code == want
        if want == 200 and 200 <= code < 300:
            ok = True
        flag = "OK" if ok else "FAIL"
        print(f"  [{flag}] {name}: HTTP {code} ({note})")
        if not ok:
            bad.append(name)

    print("\n=== Webhook: bot-invitation-test (canonical F6uos invitation hub) ===")
    wh_code, wh_body = post_json(
        "https://auto.rs3d.pl/webhook/bot-invitation-test", {}
    )
    print(f"  HTTP {wh_code} body[:200]={wh_body[:200]!r}")

    print("\n=== Telegram: one operator ping (parsed from Monitor workflow, token redacted) ===")
    tg = telegram_from_monitor_workflow()
    if not tg:
        print("  SKIP: could not parse Telegram node")
    else:
        send_url, chat_id = tg
        text = (
            "RS WOW SMOKE\n"
            f"Monitor matrix: {len(bad)} failures ({', '.join(bad) or 'none'}).\n"
            f"Invitation webhook: HTTP {wh_code}.\n"
            "Daily News: schedule-only (CLI/API cannot fire schedule); next slots 08:15 / 13:15 / 18:15 Europe/Warsaw."
        )
        safe_url = re.sub(r"bot[^/]+/", "bot***/", send_url)
        payload = json.dumps(
            {"chat_id": chat_id, "text": text, "disable_web_page_preview": True}
        ).encode()
        req = urllib.request.Request(
            send_url,
            data=payload,
            method="POST",
            headers={"Content-Type": "application/json"},
        )
        try:
            with urllib.request.urlopen(req, timeout=30) as r:
                print(f"  Telegram OK HTTP {r.status} (url pattern {safe_url[:60]}...)")
        except urllib.error.HTTPError as e:
            print(f"  Telegram FAIL HTTP {e.code}")

    print("\n=== Recent executions (workflowId, status) ===")
    con = sqlite3.connect(DB)
    for r in con.execute(
        "SELECT id, status, workflowId FROM execution_entity ORDER BY id DESC LIMIT 6"
    ):
        print(f"  {r[0]} {r[1]} {r[2]}")
    con.close()

    sys.exit(1 if bad or wh_code >= 400 else 0)


if __name__ == "__main__":
    main()
