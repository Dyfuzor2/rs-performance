#!/usr/bin/env python3
"""List HttpRequest nodes whose jsonBody looks like invalid JSON (April 2026+)."""
from __future__ import annotations

import re
import sys
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_n8n_api  # noqa: E402
from n8n_public_api_pagination import fetch_all_workflow_list_items  # noqa: E402


def looks_bad(body: str) -> bool:
    if not body or not isinstance(body, str):
        return False
    s = body.strip()
    if "JSON.stringify" in s:
        return False
    if s.startswith("={{") and s.endswith("}}"):
        return False
    if s.startswith("=") and not s.startswith("={{") and not s.startswith("={ "):
        return True
    if re.search(r"[\+\$]", s) and not s.startswith("={{") and "JSON.stringify" not in s:
        return True
    return False


def main() -> int:
    base, key = require_n8n_api()
    headers = {"X-N8N-API-KEY": key}
    data = fetch_all_workflow_list_items(base.rstrip("/"), headers, timeout=120.0)
    bad: list[tuple[str, str, str, str]] = []
    for m in data:
        wid = m.get("id")
        if not wid:
            continue
        w = requests.get(f"{base}/api/v1/workflows/{wid}", headers=headers, timeout=60).json()
        wname = w.get("name", "")
        for n in w.get("nodes") or []:
            if n.get("type") != "n8n-nodes-base.httpRequest":
                continue
            p = n.get("parameters") or {}
            jb = p.get("jsonBody")
            if not jb:
                continue
            spec = str(p.get("specifyBody") or p.get("contentType") or "")
            if "json" not in spec.lower() and p.get("contentType") != "json":
                continue
            if looks_bad(str(jb)):
                bad.append((wid, wname, n.get("name", ""), str(jb)[:120]))
    print(f"=== suspicious jsonBody count: {len(bad)} ===")
    for row in bad:
        tail = (row[3].encode("ascii", "replace").decode("ascii"))[:120]
        print(f"{row[0]} | {row[1][:40]} | {row[2]} | {tail}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
