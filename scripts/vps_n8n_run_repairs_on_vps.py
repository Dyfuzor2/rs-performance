#!/usr/bin/env python3
"""
Run on RS VPS (April 2026+): apply n8n fleet repairs against live instance.

When the canonical hosting instance/domain changes, re-audit HttpRequest URLs inside
workflows (and literals in scripts like n8n_apr2026_fleet_http_jsonfix.py); n8n host
(VPS/SOCmid) may stay the same while public API base must match the new Laravel APP_URL.

Prefers env (no .cursor/mcp.env required on server):
  N8N_API_KEY_SQLITE_PATH=/srv/ops-stack/n8n/storage/database.sqlite
  N8N_API_URL=https://auto.rs3d.pl

From a one-off bundle:
  mkdir -p /tmp/rs-n8n-apr26 && cd /tmp/rs-n8n-apr26
  # upload repo `scripts/*.py` into ./scripts/
  python3 scripts/vps_n8n_run_repairs_on_vps.py

Or from Windows (uses local mcp.env if sqlite path not set):
  python scripts/vps_n8n_run_repairs_on_vps.py
"""

from __future__ import annotations

import os
import sys
from pathlib import Path

_scripts = Path(__file__).resolve().parent
sys.path.insert(0, str(_scripts))

os.environ.setdefault("N8N_API_URL", "https://auto.rs3d.pl")
os.environ.setdefault(
    "N8N_API_KEY_SQLITE_PATH",
    "/srv/ops-stack/n8n/storage/database.sqlite",
)

import n8n_apr2026_fleet_http_jsonfix  # noqa: E402
import n8n_apply_apr2026_repairs  # noqa: E402
import n8n_monitor_diagnosta_code_node  # noqa: E402
import n8n_fix_http_jsonbody_expressions_apr2026  # noqa: E402
import n8n_patch_workflows_apr2026_round2  # noqa: E402


def main() -> int:
    steps = (
        ("fleet_http_jsonfix", n8n_apr2026_fleet_http_jsonfix.main),
        ("apply_apr2026_repairs", n8n_apply_apr2026_repairs.main),
        ("monitor_code_node", n8n_monitor_diagnosta_code_node.main),
        ("fix_http_jsonbody_expressions", n8n_fix_http_jsonbody_expressions_apr2026.main),
        ("patch_round2_hub_content", n8n_patch_workflows_apr2026_round2.main),
    )
    bad = 0
    for name, fn in steps:
        print(f"--- {name} ---")
        code = int(fn())
        if code != 0:
            bad = 1
    return bad


if __name__ == "__main__":
    raise SystemExit(main())
