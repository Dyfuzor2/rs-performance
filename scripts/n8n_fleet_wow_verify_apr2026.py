#!/usr/bin/env python3
"""
April 2026+ WOW — n8n fleet: last execution status, Telegram wiring sanity, agent/LLM inventory.

- Uses n8n public API (same as production).
- Telegram Bot API getMe (token from .cursor/mcp.env only; never prints token).
- Compares operator TELEGRAM_CHAT_ID to chat_id literals found in workflow JSON (warn on mismatch).

Usage: python scripts/n8n_fleet_wow_verify_apr2026.py
"""

from __future__ import annotations

import json
import os
import re
import sys
import urllib.parse
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import load_cursor_env, require_n8n_api  # noqa: E402

_CHAT_ID_RE = re.compile(r"chat_id['\"]?\s*[:=]\s*['\"]?([0-9-]+)")


def _telegram_urls_in_workflow(nodes: list) -> list[str]:
    out: list[str] = []
    for n in nodes:
        p = n.get("parameters") or {}
        url = str(p.get("url") or "")
        if "api.telegram.org" in url and "bot" in url:
            out.append(re.sub(r"bot[^/]+/", "bot***/", url)[:100])
    return out


def _chat_ids_in_nodes(nodes: list) -> set[str]:
    ids: set[str] = set()
    for n in nodes:
        blob = json.dumps(n.get("parameters") or {}, ensure_ascii=False)
        for m in _CHAT_ID_RE.finditer(blob):
            ids.add(m.group(1))
    return ids


def _agent_nodes(nodes: list) -> list[tuple[str, str]]:
    rows: list[tuple[str, str]] = []
    for n in nodes:
        t = n.get("type") or ""
        if "langchain" in t or "openAi" in t or "chainLlm" in t or "lmChat" in t:
            rows.append((n.get("name") or "?", t))
        elif t.endswith(".agent") or ".agent" in t:
            rows.append((n.get("name") or "?", t))
        elif t == "n8n-nodes-base.httpRequest":
            p = n.get("parameters") or {}
            u = str(p.get("url") or "")
            if "openrouter.ai" in u or "generativelanguage" in u or "/api/n8n/vertex" in u:
                rows.append((n.get("name") or "?", "http-LLM/vertex"))
    return rows


def _last_error_message(base: str, headers: dict, eid: str) -> str:
    r = requests.get(
        f"{base}/api/v1/executions/{eid}",
        params={"includeData": "true"},
        headers=headers,
        timeout=60,
    )
    if not r.ok:
        return f"(fetch failed {r.status_code})"
    data = (r.json().get("data") or {}).get("resultData") or {}
    err = data.get("error") if isinstance(data, dict) else None
    if isinstance(err, dict):
        return str(err.get("message") or err.get("description") or err)[:240]
    return "(no error blob)"


def main() -> int:
    load_cursor_env()
    base, key = require_n8n_api()
    headers = {"X-N8N-API-KEY": key}
    env_chat = (os.environ.get("TELEGRAM_CHAT_ID") or "").strip()

    print("=== Telegram Bot API (operator token) ===")
    try:
        token = (os.environ.get("TELEGRAM_BOT_TOKEN") or "").strip()
        if not token:
            print("  SKIP: TELEGRAM_BOT_TOKEN not set in .cursor/mcp.env")
        else:
            gr = requests.get(f"https://api.telegram.org/bot{token}/getMe", timeout=25)
            gj = gr.json() if gr.ok else {}
            ok = gj.get("ok") and gj.get("result", {}).get("is_bot")
            print(f"  getMe HTTP {gr.status_code} ok={gj.get('ok')} is_bot={gj.get('result', {}).get('is_bot')}")
            if ok:
                u = gj["result"].get("username", "?")
                print(f"  bot @{u}")
            else:
                print(f"  response: {str(gj)[:200]}")
    except Exception as e:
        print(f"  ERROR: {e!s}"[:200])

    if env_chat:
        print(f"  TELEGRAM_CHAT_ID (env): {env_chat}")
    else:
        print("  TELEGRAM_CHAT_ID (env): (not set)")

    print("\n=== n8n workflows - last run + agents + Telegram ===\n")
    metas = requests.get(f"{base}/api/v1/workflows", headers=headers, timeout=120).json()
    rows = metas.get("data") or metas

    issues: list[str] = []
    all_chat_ids: set[str] = set()

    for m in sorted(rows, key=lambda x: (not x.get("active", False), (x.get("name") or "").lower())):
        wid = m.get("id")
        if not wid:
            continue
        w = requests.get(f"{base}/api/v1/workflows/{wid}", headers=headers, timeout=60).json()
        nodes = w.get("nodes") or []
        name = (w.get("name") or "")[:50]
        active = w.get("active")

        q = urllib.parse.urlencode({"workflowId": wid, "limit": 1})
        exr = requests.get(f"{base}/api/v1/executions?{q}", headers=headers, timeout=30).json()
        exd = (exr.get("data") or [{}])[0] if (exr.get("data") or []) else {}
        st = exd.get("status") or "never"
        eid = exd.get("id")
        started = (exd.get("startedAt") or "")[:19]

        err_hint = ""
        if st == "error" and eid:
            err_hint = _last_error_message(base, headers, str(eid))

        agents = _agent_nodes(nodes)
        tg_urls = _telegram_urls_in_workflow(nodes)
        wf_chats = _chat_ids_in_nodes(nodes)
        all_chat_ids |= wf_chats

        flag = "OK"
        if active and st == "error":
            flag = "FAIL"
            issues.append(f"{wid} {name}: last execution error")
        elif active and st == "never":
            flag = "WARN"
            issues.append(f"{wid} {name}: active but never run")

        ag_s = f"{len(agents)} agent/LLM nodes" if agents else "no dedicated agent nodes"
        tg_s = f"telegram http {len(tg_urls)}" if tg_urls else "no bot URL in http nodes"
        chat_s = ",".join(sorted(wf_chats))[:40] if wf_chats else "-"

        print(
            f"[{flag:4}] act={int(bool(active))} last={st:7} {started:19} | {wid:22} | {name}"
        )
        print(f"       {ag_s}; {tg_s}; chat_ids: {chat_s}")
        if err_hint:
            print(f"       error: {err_hint}")
        if agents and len(agents) <= 8:
            for nn, tt in agents[:8]:
                print(f"         - {nn}: {tt[:60]}")
        elif agents:
            print(f"         - ({len(agents)} nodes -- truncated)")

    print("\n=== Chat ID consistency (workflows vs TELEGRAM_CHAT_ID) ===")
    if env_chat and all_chat_ids:
        for cid in sorted(all_chat_ids):
            match = "OK" if cid == env_chat else "REVIEW"
            print(f"  {cid}: {match}")
        if env_chat not in all_chat_ids:
            print(f"  WARN: env chat {env_chat} not found as literal in any workflow JSON (may use $json.chatId).")
    elif not env_chat:
        print("  (skip - no TELEGRAM_CHAT_ID in env)")

    print("\n=== Summary ===")
    if issues:
        print(f"  Flagged: {len(issues)}")
        for i in issues[:25]:
            print(f"    - {i}")
        if len(issues) > 25:
            print(f"    ... +{len(issues) - 25} more")
    else:
        print("  No active+error / active+never issues in this pass.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
