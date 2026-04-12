"""Load `G:\\gravity\\.cursor\\mcp.env` into `os.environ` (RS Gravity, April 2026+).

Use from repo root scripts:
    from pathlib import Path
    import sys
    _root = Path(__file__).resolve().parent
    sys.path.insert(0, str(_root / "scripts"))
    from gravity_cursor_env import require_n8n_api

    N8N_URL, N8N_API_KEY = require_n8n_api()
"""

from __future__ import annotations

import os
from pathlib import Path


def repo_root() -> Path:
    """Return workspace root when this file lives in `<root>/scripts/`."""
    return Path(__file__).resolve().parents[1]


def load_cursor_env(*, overwrite: bool = False) -> int:
    """Parse KEY=VALUE lines from `.cursor/mcp.env`. Returns number of keys set."""
    path = repo_root() / ".cursor" / "mcp.env"
    if not path.is_file():
        return 0
    count = 0
    for raw in path.read_text(encoding="utf-8").splitlines():
        line = raw.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, _, val = line.partition("=")
        key, val = key.strip(), val.strip()
        if not key:
            continue
        if not overwrite and os.environ.get(key):
            continue
        os.environ[key] = val
        count += 1
    return count


def require_n8n_api() -> tuple[str, str]:
    """Return (base_url without trailing slash, api_key). Raises RuntimeError if missing."""
    load_cursor_env()
    url = (os.environ.get("N8N_API_URL") or "").strip().rstrip("/")
    key = (os.environ.get("N8N_API_KEY") or "").strip()
    if not url or not key:
        msg = (
            "Missing N8N_API_URL or N8N_API_KEY. Fill .cursor/mcp.env "
            "(see .cursor/mcp.env.example)."
        )
        raise RuntimeError(msg)
    return url, key
