#!/usr/bin/env python3
"""GET /api/v1/workflows on https://auto.rs3d.pl using JWT from VPS SQLite."""
from __future__ import annotations

import sys
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root))
sys.path.insert(0, str(_root / "scripts"))
from n8n_public_api_pagination import fetch_all_workflow_list_items  # noqa: E402
from vps_exec import vps_exec_capture  # noqa: E402

CMD = (
    "sudo python3 -c \"import sqlite3,sys; "
    "c=sqlite3.connect('/srv/ops-stack/n8n/storage/database.sqlite'); "
    "r=c.execute('SELECT apiKey FROM user_api_keys ORDER BY updatedAt DESC LIMIT 1').fetchone(); "
    "(sys.exit(1) if (not r or not str(r[0]).strip()) else None); "
    "print(str(r[0]).strip(), end='')\""
)


def main() -> int:
    key, err, code = vps_exec_capture(CMD, timeout=60)
    if code != 0:
        print(err or key or f"exit {code}", file=sys.stderr)
        return 1
    jwt = (key or "").strip()
    h = {"X-N8N-API-KEY": jwt}
    try:
        rows = fetch_all_workflow_list_items("https://auto.rs3d.pl", h, timeout=60.0)
    except requests.RequestException as e:
        print(str(e), file=sys.stderr)
        return 1
    print("HTTP", 200)
    print("total_workflows", len(rows))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
