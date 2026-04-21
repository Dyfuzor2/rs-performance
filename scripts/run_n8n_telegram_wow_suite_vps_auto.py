#!/usr/bin/env python3
"""Run ``n8n_apply_telegram_wow_suite_apr2026`` with one JWT read from VPS SQLite (production n8n).

Same **April 2026+ operator pattern** as ``run_n8n_fleet_verify_vps_auto.py``: a single ``vps_exec``
round-trip, then ``N8N_API_URL`` / ``N8N_API_KEY`` in the subprocess environment. The suite and
its three children use the Public API key from env (no per-child ``--vps-jwt`` SQLite reads).

Examples:

    python scripts/run_n8n_telegram_wow_suite_vps_auto.py --help
    python scripts/run_n8n_telegram_wow_suite_vps_auto.py --dry-run
    python scripts/run_n8n_telegram_wow_suite_vps_auto.py --json
    python scripts/run_n8n_telegram_wow_suite_vps_auto.py --continue-on-error --json

Requires: ``vps_exec`` / SSH per ``RELAY.md``; SQLite path on VPS matches other n8n ops scripts.
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

_SUITE = _root / "scripts" / "n8n_apply_telegram_wow_suite_apr2026.py"


def main() -> int:
    if any(a in ("-h", "--help") for a in sys.argv[1:]):
        print((__doc__ or "").strip(), flush=True)
        print("\n--- Suite (full flags; no VPS JWT fetch this run) ---\n", flush=True)
        return subprocess.call([sys.executable, str(_SUITE), "--help"], cwd=str(_root))

    out, err, code = vps_exec_capture(_CMD, timeout=60)
    if code != 0:
        print(err or out or f"exit {code}", file=sys.stderr, flush=True)
        return 1
    jwt = (out or "").strip()
    if not jwt:
        print("empty jwt from VPS", file=sys.stderr, flush=True)
        return 1

    env = os.environ.copy()
    env["N8N_API_URL"] = "https://auto.rs3d.pl"
    env["N8N_API_KEY"] = jwt
    env.pop("N8N_API_KEY_SQLITE_PATH", None)

    extra = [a for a in sys.argv[1:] if a not in ("--vps-jwt",)]
    return subprocess.call([sys.executable, str(_SUITE), *extra], cwd=str(_root), env=env)


if __name__ == "__main__":
    raise SystemExit(main())
