#!/usr/bin/env python3
"""
April 2026+ WOW — merge production-hardening env into /srv/ops-stack/n8n-mcp/.env (VPS).

Idempotent key merge; preserves AUTH_TOKEN and N8N_API_KEY unless keys listed below.
Run on VPS: sudo python3 /tmp/vps_n8n_mcp_apply_wow_env_apr2026.py

Then: cd /srv/ops-stack/compose && docker compose pull n8n-mcp && docker compose up -d n8n-mcp
"""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ENV_FILE = Path("/srv/ops-stack/n8n-mcp/.env")
COMPOSE = Path("/srv/ops-stack/compose/docker-compose.yml")

# czlonkowski/n8n-mcp — HTTP behind Caddy; longer n8n API tolerance for heavy workflows.
MERGE = {
    "NODE_ENV": "production",
    "N8N_API_TIMEOUT": "90000",
    "N8N_API_MAX_RETRIES": "4",
    "TRUST_PROXY": "true",
    "BASE_URL": "https://n8n-mcp.rs3d.pl",
    "AUTH_RATE_LIMIT_MAX": "80",
    "MCP_LOG_LEVEL": "error",
}


def _parse_env(text: str) -> dict[str, str]:
    out: dict[str, str] = {}
    for line in text.splitlines():
        s = line.strip()
        if not s or s.startswith("#") or "=" not in s:
            continue
        k, _, v = s.partition("=")
        k, v = k.strip(), v.strip()
        if k:
            out[k] = v
    return out


def _serialize(data: dict[str, str]) -> str:
    seen: set[str] = set()
    keys: list[str] = []
    for k in MERGE:
        if k in data:
            keys.append(k)
            seen.add(k)
    for k in sorted(data):
        if k not in seen:
            keys.append(k)
            seen.add(k)
    lines = [f"# n8n-mcp — Apr 2026+ operator merge (order: WOW keys first)", *[f"{k}={data[k]}" for k in keys]]
    return "\n".join(lines) + "\n"


def main() -> int:
    if not ENV_FILE.is_file():
        print(f"Missing {ENV_FILE}", file=sys.stderr)
        return 1
    raw = ENV_FILE.read_text(encoding="utf-8")
    data = _parse_env(raw)
    before = {k: data.get(k) for k in MERGE}
    data.update(MERGE)
    changed = any(before.get(k) != data.get(k) for k in MERGE)
    ENV_FILE.write_text(_serialize(data), encoding="utf-8")
    print("OK: merged WOW env keys" + (" (updates applied)" if changed else " (already satisfied)"))

    subprocess.run(
        ["docker", "compose", "-f", str(COMPOSE), "pull", "n8n-mcp"],
        check=False,
    )
    r = subprocess.run(
        ["docker", "compose", "-f", str(COMPOSE), "up", "-d", "n8n-mcp"],
        check=False,
    )
    return r.returncode


if __name__ == "__main__":
    raise SystemExit(main())
