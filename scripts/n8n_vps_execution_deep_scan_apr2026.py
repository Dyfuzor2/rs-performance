#!/usr/bin/env python3
"""
April 2026+ — For each active workflow with last execution error, print last error message + failing node name (no response bodies).
"""
from __future__ import annotations

import json
import sys
import urllib.parse
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import resolve_n8n_api  # noqa: E402
from n8n_public_api_pagination import fetch_all_workflow_list_items  # noqa: E402


def last_error_detail(base: str, headers: dict, eid: str) -> str:
    r = requests.get(
        f"{base}/api/v1/executions/{eid}",
        params={"includeData": "true"},
        headers=headers,
        timeout=90,
    )
    if not r.ok:
        return f"(http {r.status_code})"
    data = r.json().get("data") or {}
    rd = data.get("resultData") or {}
    err = rd.get("error") if isinstance(rd, dict) else None
    if isinstance(err, dict):
        parts = [str(err.get("message") or ""), str(err.get("description") or "")]
        node = err.get("node") or {}
        if isinstance(node, dict) and node.get("name"):
            parts.append(f"node={node.get('name')}")
        return " | ".join(p for p in parts if p)[:400]
    return str(err)[:400] if err else "(no error object)"


def main() -> int:
    base, key = resolve_n8n_api()
    headers = {"X-N8N-API-KEY": key}
    rows = fetch_all_workflow_list_items(base.rstrip("/"), headers, timeout=120.0)

    print(f"n8n_base={base}\n")
    for m in sorted(rows, key=lambda x: (not x.get("active"), (x.get("name") or "").lower())):
        if not m.get("active"):
            continue
        wid = m.get("id")
        if not wid:
            continue
        name = m.get("name") or "?"
        q = urllib.parse.urlencode({"workflowId": wid, "limit": 1})
        exr = requests.get(f"{base}/api/v1/executions?{q}", headers=headers, timeout=30).json()
        exd = (exr.get("data") or [{}])[0] if (exr.get("data") or []) else {}
        st = exd.get("status") or "never"
        eid = exd.get("id")
        if st != "error" or not eid:
            continue
        detail = last_error_detail(base, headers, str(eid))
        print(f"[{wid}] {name}")
        print(f"  last_error: {detail}\n")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
