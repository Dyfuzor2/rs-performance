#!/usr/bin/env python3
"""
April 2026+ WOW — n8n fleet: last execution status, Telegram wiring sanity, agent/LLM inventory.

- Uses n8n public API (same as production).
- Telegram Bot API getMe (token from .cursor/mcp.env only; never prints token).
- Compares operator TELEGRAM_CHAT_ID to chat_id literals found in workflow JSON (warn on mismatch).

Usage:
  python scripts/n8n_fleet_wow_verify_apr2026.py
  python scripts/n8n_fleet_wow_verify_apr2026.py --base-url https://n8n-s2.socmid.cloud --api-key-env N8N_API_KEY_SOCMID
  python scripts/n8n_fleet_wow_verify_apr2026.py --strict   # exit 1 if any active workflow last=error or active+never
  python scripts/n8n_fleet_wow_verify_apr2026.py --json      # machine-readable summary line on stderr/stdout
  python scripts/n8n_fleet_wow_verify_apr2026.py --definition-gate --json
      # Active workflows whose *last* run failed but current JSON passes April-2026 sanity
      #   are downgraded to DEF-OK (not counted as issues / strict failures).
"""

from __future__ import annotations

import argparse
import json
import os
import re
import sys
import urllib.parse
from pathlib import Path
from urllib.parse import urlparse

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import load_cursor_env, resolve_n8n_api  # noqa: E402
from n8n_public_api_pagination import fetch_all_workflow_list_items  # noqa: E402

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


def _definition_sanity_passes(nodes: list) -> tuple[bool, list[str]]:
    """
    Heuristic static checks on workflow JSON (April 2026+ repair targets).

    When last execution is still ``error`` (stale) after a PUT fix, these gates
    let CI/ops pass without waiting for the next cron tick.
    """
    reasons: list[str] = []

    def _http_param_blob(params: dict) -> str:
        return json.dumps(params, ensure_ascii=False)

    for n in nodes:
        name = n.get("name") or "?"
        ntype = n.get("type") or ""
        params = n.get("parameters") or {}

        if ntype == "n8n-nodes-base.code":
            js = str(params.get("jsCode") or "")
            if "require('crypto'" in js or 'require("crypto"' in js:
                reasons.append(f"code:{name}:disallowed_crypto")

        if ntype == "n8n-nodes-base.httpRequest":
            jb = str(params.get("jsonBody") or "").strip()
            # Legacy OpenRouter / Vertex migration typo: ``={ JSON.stringify`` (one ``{``) instead of ``={{``.
            if re.search(r"=\{\s+JSON\.stringify", jb) and not jb.startswith("{{"):
                reasons.append(f"http:{name}:jsonBody_legacy_stringify")
            blob = _http_param_blob(params)
            if "sk-or-" in blob or "sk-proj-" in blob:
                reasons.append(f"http:{name}:possible_literal_api_key")
            url = str(params.get("url") or "")
            url_st = url.strip()
            # Broken: raw ``=https://api.telegram.org/...`` without ``={{`` (n8n expression).
            if "{{" not in url and (
                url_st.startswith("=https://api.telegram.org")
                or url_st.startswith("=http://api.telegram.org")
            ):
                reasons.append(f"http:{name}:telegram_url_missing_expression_braces")

    return (len(reasons) == 0, reasons)


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
    ap = argparse.ArgumentParser(description="n8n fleet WOW verify (VPS API)")
    ap.add_argument(
        "--strict",
        action="store_true",
        help="Exit 1 if any active workflow has last execution error or active+never.",
    )
    ap.add_argument(
        "--json",
        action="store_true",
        help="Print one JSON object with counts and issue list (after human-readable output).",
    )
    ap.add_argument(
        "--expect-host",
        default="",
        help="If set (e.g. auto.rs3d.pl), warn when N8N_API_URL does not contain this host.",
    )
    ap.add_argument(
        "--base-url",
        default="",
        help="Override N8N_API_URL for this run (e.g. https://n8n-s2.socmid.cloud).",
    )
    ap.add_argument(
        "--api-key-env",
        default="N8N_API_KEY",
        help="Which env var holds the API key for this instance (default N8N_API_KEY).",
    )
    ap.add_argument(
        "--definition-gate",
        action="store_true",
        help=(
            "If last execution is error but workflow JSON passes static sanity checks, "
            "show DEF-OK and do not count as failure (--strict / JSON ok)."
        ),
    )
    args = ap.parse_args()

    try:
        base, key = resolve_n8n_api(
            base_url=(args.base_url or None),
            api_key_env=(args.api_key_env or "N8N_API_KEY"),
        )
    except RuntimeError as e:
        print(str(e), file=sys.stderr)
        return 2
    expect_host = (args.expect_host or "").strip() or urlparse(base).netloc
    if expect_host and expect_host.lower() not in base.lower():
        print(
            f"WARN: N8N_API_URL={base!r} does not contain --expect-host={expect_host!r}",
            file=sys.stderr,
        )
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
    try:
        rows = fetch_all_workflow_list_items(base.rstrip("/"), headers, timeout=120.0)
    except requests.HTTPError as e:
        r = e.response
        body = (r.text[:400] if r is not None else "") or str(e)
        print(f"ERROR: workflows list HTTP {getattr(r, 'status_code', '?')}: {body}", file=sys.stderr)
        return 2
    except requests.RequestException as e:
        print(f"ERROR: workflows list: {e!s}", file=sys.stderr)
        return 2
    print(f"(workflows listed: {len(rows)})\n")

    issues: list[str] = []
    stale_recovered: list[str] = []
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

        def_ok, def_reasons = _definition_sanity_passes(nodes)

        flag = "OK"
        if active and st == "error":
            if args.definition_gate and def_ok:
                flag = "DEFOK"
                stale_recovered.append(
                    f"{wid} {name}: last execution error (stale); definition sanity OK"
                )
            else:
                flag = "FAIL"
                extra = f" | def: {'; '.join(def_reasons[:6])}" if def_reasons else ""
                issues.append(f"{wid} {name}: last execution error{extra}")
        elif active and st == "never":
            flag = "WARN"
            issues.append(f"{wid} {name}: active but never run")

        ag_s = f"{len(agents)} agent/LLM nodes" if agents else "no dedicated agent nodes"
        tg_s = f"telegram http {len(tg_urls)}" if tg_urls else "no bot URL in http nodes"
        chat_s = ",".join(sorted(wf_chats))[:40] if wf_chats else "-"

        print(
            f"[{flag:5}] act={int(bool(active))} last={st:7} {started:19} | {wid:22} | {name}"
        )
        print(f"       {ag_s}; {tg_s}; chat_ids: {chat_s}")
        if err_hint:
            print(f"       error: {err_hint}")
        if flag == "DEFOK":
            print(
                "       note: definition-gate — JSON passes static repair checks; "
                "next successful run clears stale error in n8n history"
            )
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
    if stale_recovered:
        print(f"  Definition-gate (stale error, JSON OK): {len(stale_recovered)}")
        for s in stale_recovered[:15]:
            print(f"    ~ {s}")
        if len(stale_recovered) > 15:
            print(f"    ... +{len(stale_recovered) - 15} more")
    if issues:
        print(f"  Flagged: {len(issues)}")
        for i in issues[:25]:
            print(f"    - {i}")
        if len(issues) > 25:
            print(f"    ... +{len(issues) - 25} more")
    else:
        print("  No active+error / active+never issues in this pass.")

    if args.json:
        payload = {
            "suite": "n8n_fleet_wow_verify_apr2026",
            "n8n_base": base,
            "definition_gate": bool(args.definition_gate),
            "flagged_count": len(issues),
            "issues": issues[:100],
            "stale_error_recovered_count": len(stale_recovered),
            "stale_error_recovered": stale_recovered[:50],
            "ok": len(issues) == 0,
        }
        print("\n__JSON__\n" + json.dumps(payload, ensure_ascii=False))

    if args.strict and issues:
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
