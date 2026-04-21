#!/usr/bin/env python3
"""Run ``n8n_fleet_wow_verify_apr2026`` against ``https://auto.rs3d.pl`` with JWT from VPS SQLite.

Fetches the latest n8n API key via ``vps_exec`` (same pattern as ops scripts on Windows),
sets ``N8N_API_URL`` / ``N8N_API_KEY``, then delegates all CLI flags to the verifier.

Examples::

    python scripts/run_n8n_fleet_verify_vps_auto.py --json
    python scripts/run_n8n_fleet_verify_vps_auto.py --definition-gate --json
    python scripts/run_n8n_fleet_verify_vps_auto.py --definition-gate --strict --json

``--definition-gate`` (April 2026+): active workflows whose *last* execution is ``error``
but whose current workflow JSON passes static repair checks are reported as ``DEFOK``
and do not fail ``--strict`` (stale execution history until the next successful run).
"""
from __future__ import annotations

import os
import subprocess
import sys
from pathlib import Path

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root))
from vps_exec import vps_exec_capture  # noqa: E402

_CMD = (
    "sudo python3 -c \"import sqlite3,sys; "
    "c=sqlite3.connect('/srv/ops-stack/n8n/storage/database.sqlite'); "
    "r=c.execute('SELECT apiKey FROM user_api_keys ORDER BY updatedAt DESC LIMIT 1').fetchone(); "
    "(sys.exit(1) if (not r or not str(r[0]).strip()) else None); "
    "print(str(r[0]).strip(), end='')\""
)


def main() -> int:
    out, err, code = vps_exec_capture(_CMD, timeout=60)
    if code != 0:
        print(err or out or f"exit {code}", file=sys.stderr)
        return 1
    jwt = (out or "").strip()
    env = os.environ.copy()
    env["N8N_API_URL"] = "https://auto.rs3d.pl"
    env["N8N_API_KEY"] = jwt
    env.pop("N8N_API_KEY_SQLITE_PATH", None)
    extra = sys.argv[1:]
    return subprocess.call(
        [
            sys.executable,
            str(_root / "scripts" / "n8n_fleet_wow_verify_apr2026.py"),
            "--base-url",
            "https://auto.rs3d.pl",
            "--expect-host",
            "auto.rs3d.pl",
            *extra,
        ],
        cwd=str(_root),
        env=env,
    )


if __name__ == "__main__":
    raise SystemExit(main())
