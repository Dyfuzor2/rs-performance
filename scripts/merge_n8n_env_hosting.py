#!/usr/bin/env python3
"""
Merge N8N_API_URL / N8N_API_KEY / N8N_EDITOR_BASE_URL into hosting .env from a local key file.
Uses ssh_exec.py (SFTP) — no secrets in repo commits.

Usage (from G:\\gravity):
  python scripts/merge_n8n_env_hosting.py n8n.txt

Default URL: https://auto.rs3d.pl (VPS n8n; adjust if your instance differs).
"""
from __future__ import annotations

import re
import subprocess
import sys
from pathlib import Path

REMOTE_ENV = "domains/rsperformance.online/laravel/.env"
DEFAULT_BASE = "https://auto.rs3d.pl"


def strip_n8n_lines(text: str) -> str:
    lines = []
    for line in text.splitlines():
        if re.match(r"^\s*(N8N_API_URL|N8N_API_KEY|N8N_EDITOR_BASE_URL)\s*=", line):
            continue
        lines.append(line)
    return "\n".join(lines).rstrip() + "\n"


def main() -> int:
    root = Path(__file__).resolve().parents[1]
    key_path = Path(sys.argv[1]) if len(sys.argv) > 1 else root / "n8n.txt"
    if not key_path.is_file():
        print(f"Missing key file: {key_path}", file=sys.stderr)
        return 1

    raw = key_path.read_text(encoding="utf-8").strip()
    if not raw or raw.startswith("#"):
        print("Key file empty or placeholder.", file=sys.stderr)
        return 1

    local_tmp = root / ".tmp_env_n8n_merge"
    ssh_exec = root / "ssh_exec.py"

    subprocess.run(
        [sys.executable, str(ssh_exec), "--download", REMOTE_ENV, str(local_tmp)],
        check=True,
        cwd=str(root),
    )

    body = strip_n8n_lines(local_tmp.read_text(encoding="utf-8"))
    block = (
        f"\n# n8n Public REST API (Filament WOW Ops Hub) — docs.n8n.io/api\n"
        f"N8N_API_URL={DEFAULT_BASE}\n"
        f"N8N_EDITOR_BASE_URL={DEFAULT_BASE}\n"
        f"N8N_API_KEY={raw}\n"
    )
    local_tmp.write_text(body + block, encoding="utf-8")

    subprocess.run(
        [sys.executable, str(ssh_exec), "--upload", str(local_tmp), REMOTE_ENV],
        check=True,
        cwd=str(root),
    )

    local_tmp.unlink(missing_ok=True)
    print("OK: merged N8N_* into hosting .env (remote). Run: php85 artisan config:clear")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
