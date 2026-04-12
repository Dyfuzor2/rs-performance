#!/usr/bin/env python3
"""
Pull latest n8n Public API JWT from VPS SQLite (same DB as Docker n8n), merge into hosting .env for https://auto.rs3d.pl.

Does not print the JWT. Uses vps_exec_capture + merge_n8n_env_hosting.

Usage (from repo root):
  python scripts/hosting_merge_n8n_from_vps_sqlite.py
"""

from __future__ import annotations

import re
import subprocess
import sys
from pathlib import Path

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root))
from vps_exec import vps_exec_capture  # noqa: E402

def main() -> int:
    # One line for remote bash + python -c (SQLite = same JWT n8n-mcp uses for localhost:5678).
    cmd = (
        "sudo python3 -c \"import sqlite3,sys; "
        "c=sqlite3.connect('/srv/ops-stack/n8n/storage/database.sqlite'); "
        "r=c.execute('SELECT apiKey FROM user_api_keys ORDER BY updatedAt DESC LIMIT 1').fetchone(); "
        "(sys.exit(1) if (not r or not str(r[0]).strip()) else None); "
        "print(str(r[0]).strip(), end='')\""
    )
    out, err, code = vps_exec_capture(cmd, timeout=60)
    if code != 0:
        print(err or out or f"exit {code}", file=sys.stderr)
        return 1
    jwt = (out or "").strip()
    if not re.match(r"^eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$", jwt):
        print("VPS did not return a valid n8n JWT shape.", file=sys.stderr)
        return 1

    capsule = _root / ".tmp_n8n_vps_sqlite_capsule.txt"
    capsule.write_text(
        "# Ephemeral — from VPS user_api_keys; do not commit.\n"
        f"N8N_API_URL=https://auto.rs3d.pl\n"
        f"N8N_API_KEY={jwt}\n",
        encoding="utf-8",
    )
    try:
        r = subprocess.run(
            [
                sys.executable,
                str(_root / "scripts" / "merge_n8n_env_hosting.py"),
                str(capsule),
                "https://auto.rs3d.pl",
            ],
            cwd=str(_root),
            check=False,
        )
        if r.returncode != 0:
            return r.returncode
    finally:
        capsule.unlink(missing_ok=True)

    subprocess.run(
        [
            sys.executable,
            str(_root / "ssh_exec.py"),
            "cd ~/domains/rsperformance.online/laravel && php85 artisan config:clear && php85 artisan config:cache",
        ],
        cwd=str(_root),
        check=True,
    )
    print("OK: hosting .env merged for https://auto.rs3d.pl + config cached.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
