#!/usr/bin/env python3
"""GET /api/v1/workflows on https://auto.rs3d.pl using JWT from VPS SQLite."""
from __future__ import annotations

import sys
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root))
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
    r = requests.get(
        "https://auto.rs3d.pl/api/v1/workflows",
        headers={"X-N8N-API-KEY": jwt},
        params={"limit": 3},
        timeout=30,
    )
    print("HTTP", r.status_code)
    if not r.ok:
        print(r.text[:500], file=sys.stderr)
        return 1
    data = r.json()
    rows = data.get("data") if isinstance(data, dict) else data
    n = len(rows) if isinstance(rows, list) else 0
    print("sample_workflows", n)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
