#!/usr/bin/env python3
"""
RS n8n + Telegram WOW digest (April 2026+).

Per skills: rs-n8n-wow-2026 + telegram-bot-skills — one rich operator message:
- health matrix (same URLs as RS AI Agent Monitor)
- active workflow fleet: last execution status per workflow (SQLite)
- invitation webhook probe

Run on VPS only. Does not print bot tokens. Parses Telegram target from Monitor workflow JSON.

Usage:
  python3 scripts/vps_n8n_telegram_wow_digest.py [--quiet]
  N8N_SQLITE=/path/to/database.sqlite python3 scripts/vps_n8n_telegram_wow_digest.py

  --quiet: only stderr on failure (for cron); success is silent except Telegram message.
"""
from __future__ import annotations

import html
import json
import os
import re
import sqlite3
import sys
import urllib.error
import urllib.request

DEFAULT_DB = "/srv/ops-stack/n8n/storage/database.sqlite"

# Unicode labels (avoid editor encoding issues)
OK = "\u2705"
FAIL = "\u274c"
WARN = "\u26a0\ufe0f"
ROCKET = "\U0001f680"

MONITOR_CHECKS = [
    ("Site", "https://rsperformance.online"),
    ("AI gw", "https://ai.rsperformance.online/healthz"),
    ("MCP", "https://mcp.rs3d.pl/healthz"),
    ("n8n", "https://auto.rs3d.pl/healthz"),
    ("llms", "https://rsperformance.online/llms.txt"),
]


def resolve_db() -> str:
    env = os.environ.get("N8N_SQLITE", "").strip()
    for p in (env, DEFAULT_DB, "/home/node/.n8n/database.sqlite"):
        if p and os.path.isfile(p):
            return p
    return DEFAULT_DB


def http_code(url: str, timeout: int = 20) -> int:
    req = urllib.request.Request(url, method="GET")
    try:
        with urllib.request.urlopen(req, timeout=timeout) as r:
            return r.status
    except urllib.error.HTTPError as e:
        return e.code
    except urllib.error.URLError:
        return -1


def telegram_from_monitor(db: str) -> tuple[str, str] | None:
    con = sqlite3.connect(db)
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


def fleet_last_status(db: str) -> list[tuple[str, str, str | None]]:
    """Return (workflow_id, name, last_status or None if never ran)."""
    con = sqlite3.connect(db)
    con.row_factory = sqlite3.Row
    sql = """
        SELECT w.id AS wid, w.name AS wname,
               (SELECT e.status FROM execution_entity e
                WHERE e.workflowId = w.id ORDER BY e.id DESC LIMIT 1) AS st
        FROM workflow_entity w
        WHERE w.active = 1
        ORDER BY w.name COLLATE NOCASE
    """
    try:
        rows = con.execute(sql).fetchall()
    except sqlite3.OperationalError:
        sql_plain = """
            SELECT w.id AS wid, w.name AS wname,
                   (SELECT e.status FROM execution_entity e
                    WHERE e.workflowId = w.id ORDER BY e.id DESC LIMIT 1) AS st
            FROM workflow_entity w
            WHERE w.active = 1
            ORDER BY w.name
        """
        rows = con.execute(sql_plain).fetchall()
    con.close()
    return [(r["wid"], r["wname"], r["st"]) for r in rows]


def post_telegram(url: str, chat_id: str, text: str) -> tuple[int, str]:
    payload = json.dumps(
        {
            "chat_id": chat_id,
            "text": text,
            "disable_web_page_preview": True,
            "parse_mode": "HTML",
        }
    ).encode()
    req = urllib.request.Request(
        url,
        data=payload,
        method="POST",
        headers={"Content-Type": "application/json"},
    )
    try:
        with urllib.request.urlopen(req, timeout=35) as r:
            return r.status, "ok"
    except urllib.error.HTTPError as e:
        return e.code, e.read()[:200].decode(errors="replace")


def main() -> int:
    quiet = "--quiet" in sys.argv
    db = resolve_db()
    if not os.path.isfile(db):
        print(f"ERROR: SQLite not found: {db}", file=sys.stderr)
        return 2

    lines_health: list[str] = []
    fails = 0
    for label, url in MONITOR_CHECKS:
        c = http_code(url)
        ok = c == 200 or (200 <= c < 300)
        if not ok:
            fails += 1
        icon = OK if ok else FAIL
        lines_health.append(f"{icon} <b>{html.escape(label)}</b> HTTP {c}")

    wh_c = -1
    try:
        data = json.dumps({}).encode()
        req = urllib.request.Request(
            "https://auto.rs3d.pl/webhook/bot-invitation-test",
            data=data,
            method="POST",
            headers={"Content-Type": "application/json"},
        )
        with urllib.request.urlopen(req, timeout=45) as r:
            wh_c = r.status
    except urllib.error.HTTPError as e:
        wh_c = e.code
    except urllib.error.URLError:
        wh_c = -1
    wh_ok = 200 <= wh_c < 300
    if not wh_ok:
        fails += 1
    wh_icon = OK if wh_ok else FAIL

    fleet = fleet_last_status(db)
    lines_fleet: list[str] = []
    for wid, wname, st in fleet:
        if st is None:
            icon, st_disp = WARN, "no runs yet"
        elif st == "success":
            icon, st_disp = OK, st
        elif st == "error":
            icon, st_disp = FAIL, st
        else:
            icon, st_disp = WARN, st
        esc_name = html.escape(wname)
        short = esc_name[:42] + "\u2026" if len(wname) > 43 else esc_name
        wid_short = html.escape(wid[:8] + "\u2026")
        lines_fleet.append(
            f"{icon} {short} <code>{wid_short}</code> \u2192 {html.escape(str(st_disp))}"
        )

    tg = telegram_from_monitor(db)
    if not tg:
        print("No Telegram node parsed from Monitor workflow; printed digest only:\n")
        print("\n".join(lines_health))
        print(f"\nWebhook invitation: HTTP {wh_c}")
        print("\n".join(lines_fleet))
        return 1

    body = (
        f"{ROCKET} <b>RS n8n WOW digest</b> (auto.rs3d.pl)\n\n"
        "<b>Health</b>\n"
        + "\n".join(lines_health)
        + f"\n\n{wh_icon} <b>Invitation webhook</b> HTTP {wh_c}\n\n"
        "<b>Active workflows (last run)</b>\n"
        + "\n".join(lines_fleet)
        + f"\n\n<i>{len(fleet)} active \u00b7 SQLite digest</i>"
    )
    if len(body) > 4000:
        body = body[:3990] + "\u2026</i>"

    send_url, chat_id = tg
    code, _ = post_telegram(send_url, chat_id, body)
    if not quiet:
        safe = re.sub(r"bot[^/]+/", "bot***/", send_url)
        print(f"Telegram HTTP {code} via {safe[:55]}...")
        print(f"Fleet rows: {len(fleet)}, health fails: {fails}, webhook: {wh_c}")
    elif code != 200 or fails:
        print(
            f"WOW digest: Telegram HTTP {code}, fails={fails}, webhook={wh_c}",
            file=sys.stderr,
        )
    return 0 if code == 200 and fails == 0 else 1


if __name__ == "__main__":
    raise SystemExit(main())
