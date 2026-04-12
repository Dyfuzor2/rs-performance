#!/usr/bin/env python3
"""Refresh N8N_API_KEY in n8n-mcp .env from local n8n SQLite (VPS). Run with sudo."""

from __future__ import annotations

import sqlite3
import sys
from pathlib import Path

N8N_SQLITE = Path("/srv/ops-stack/n8n/storage/database.sqlite")
ENV_FILE = Path("/srv/ops-stack/n8n-mcp/.env")


def main() -> int:
    if not N8N_SQLITE.is_file():
        print(f"Missing {N8N_SQLITE}", file=sys.stderr)
        return 1
    if not ENV_FILE.is_file():
        print(f"Missing {ENV_FILE}", file=sys.stderr)
        return 1

    row = sqlite3.connect(str(N8N_SQLITE)).execute(
        "SELECT apiKey FROM user_api_keys ORDER BY updatedAt DESC LIMIT 1"
    ).fetchone()
    if not row or not str(row[0]).strip():
        print("No apiKey in user_api_keys", file=sys.stderr)
        return 1
    key = str(row[0]).strip()

    text = ENV_FILE.read_text()
    lines: list[str] = []
    found = False
    for ln in text.splitlines():
        if ln.startswith("N8N_API_KEY="):
            lines.append(f"N8N_API_KEY={key}")
            found = True
        else:
            lines.append(ln)
    if not found:
        print("No N8N_API_KEY= line in .env", file=sys.stderr)
        return 1

    out = "\n".join(lines)
    if text.endswith("\n"):
        out += "\n"
    ENV_FILE.write_text(out)
    print(f"OK: N8N_API_KEY refreshed ({len(key)} chars)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
