#!/usr/bin/env python3
"""
Merge N8N_API_URL / N8N_API_KEY / N8N_EDITOR_BASE_URL into hosting .env from a local key file.
Uses ssh_exec.py (SFTP) — no secrets in repo commits.

Usage (from G:\\gravity):
  python scripts/merge_n8n_env_hosting.py n8n.txt
  python scripts/merge_n8n_env_hosting.py n8n.txt https://auto.rs3d.pl

Arg2 (optional): public base URL of the **same** n8n instance where the JWT was created (must match or API returns 401).

Default URL: https://auto.rs3d.pl (główny n8n RS na VPS). Dla SOCmid podaj explicite: https://n8n-s2.socmid.cloud

Credential file formats (April 2026+):
  • Legacy: single line, JWT only.
  • Capsule: commented header + ``N8N_API_URL=`` / ``N8N_API_KEY=`` lines (gitignored ``n8n.txt``).
"""
from __future__ import annotations

import re
import subprocess
import sys
from pathlib import Path

REMOTE_ENV = "domains/rsperformance.online/laravel/.env"
DEFAULT_BASE = "https://auto.rs3d.pl"

# Public API JWT: three dot-separated segments (n8n-issued).
_JWT_RE = re.compile(r"\b(eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+)\b")


def _looks_like_n8n_jwt(s: str) -> bool:
    parts = s.split(".")
    if len(parts) != 3 or not parts[0].startswith("eyJ"):
        return False
    return all(len(p) > 0 for p in parts)


def extract_n8n_api_key(text: str) -> str:
    """Parse JWT from capsule (N8N_API_KEY=) or raw single-line token."""
    for line in text.splitlines():
        stripped = line.strip()
        if not stripped or stripped.startswith("#"):
            continue
        if stripped.upper().startswith("N8N_API_KEY="):
            val = stripped.split("=", 1)[1].strip().strip('"').strip("'")
            if val:
                return val
    m = _JWT_RE.search(text)
    if m:
        return m.group(1)
    return text.strip()


def extract_n8n_api_url_hint(text: str) -> str | None:
    """If capsule defines N8N_API_URL=, use as default when CLI arg2 is omitted."""
    for line in text.splitlines():
        stripped = line.strip()
        if stripped.upper().startswith("N8N_API_URL="):
            val = stripped.split("=", 1)[1].strip().strip('"').strip("'")
            if val.startswith("http"):
                return val.rstrip("/")
    return None


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
    if len(sys.argv) > 2:
        base_url = sys.argv[2].strip().rstrip("/")
    else:
        base_url = DEFAULT_BASE
    if not key_path.is_file():
        print(f"Missing key file: {key_path}", file=sys.stderr)
        return 1

    full = key_path.read_text(encoding="utf-8")
    raw = extract_n8n_api_key(full)
    if len(sys.argv) <= 2:
        hint = extract_n8n_api_url_hint(full)
        if hint:
            base_url = hint
    if not raw:
        print("Key file empty or placeholder.", file=sys.stderr)
        return 1
    if not _looks_like_n8n_jwt(raw):
        print("Could not parse n8n API JWT (expected N8N_API_KEY=... or raw token).", file=sys.stderr)
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
        f"N8N_API_URL={base_url}\n"
        f"N8N_EDITOR_BASE_URL={base_url}\n"
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
