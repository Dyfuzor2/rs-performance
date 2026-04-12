#!/usr/bin/env python3
"""April 2026+ — last execution status per workflow (n8n public API)."""

from __future__ import annotations

import sys
import urllib.parse
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_n8n_api  # noqa: E402
from n8n_public_api_pagination import fetch_all_workflow_list_items  # noqa: E402


def main() -> int:
    base, key = require_n8n_api()
    headers = {"X-N8N-API-KEY": key}
    rows = fetch_all_workflow_list_items(base.rstrip("/"), headers, timeout=120.0)
    print(f"{'act':>3} {'last':^12} {'id':24} name")
    for m in sorted(rows, key=lambda x: (not x.get("active", False), (x.get("name") or "").lower())):
        wid = m.get("id")
        if not wid:
            continue
        q = urllib.parse.urlencode({"workflowId": wid, "limit": 1})
        ex = requests.get(f"{base}/api/v1/executions?{q}", headers=headers, timeout=30).json()
        data = ex.get("data") or []
        st = (data[0].get("status") if data else None) or "never"
        act = "1" if m.get("active") else "0"
        print(f"{act:>3} {st:^12} {wid:24} {(m.get('name') or '')[:52]}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
