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
        if not val and not overwrite:
            continue
        if not overwrite and os.environ.get(key):
            continue
        os.environ[key] = val
        count += 1
    return count


def require_env_any(*names: str) -> str:
    """First non-empty value among env keys (after load_cursor_env)."""
    load_cursor_env()
    for name in names:
        v = (os.environ.get(name) or "").strip()
        if v:
            return v
    joined = ", ".join(names)
    raise RuntimeError(
        f"Missing required environment variable (one of: {joined}). "
        "Add it to .cursor/mcp.env — see .cursor/mcp.env.example."
    )


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


def resolve_n8n_api(
    *,
    base_url: str | None = None,
    api_key_env: str = "N8N_API_KEY",
) -> tuple[str, str]:
    """Resolve n8n base URL and API key after ``load_cursor_env`` (fleet / audit scripts).

    If ``base_url`` is set, it wins over ``N8N_API_URL``. API key is read from
    ``api_key_env`` (default ``N8N_API_KEY``).
    """
    load_cursor_env()
    url = (base_url or os.environ.get("N8N_API_URL") or "").strip().rstrip("/")
    env_key = (api_key_env or "N8N_API_KEY").strip() or "N8N_API_KEY"
    key = (os.environ.get(env_key) or "").strip()
    if not url or not key:
        raise RuntimeError(
            f"Missing n8n URL or API key (env: {env_key}). "
            "Fill .cursor/mcp.env — see .cursor/mcp.env.example."
        )
    return url, key


def require_openrouter_api_key() -> str:
    return require_env_any("OPENROUTER_API_KEY")


def require_rs_x_api_token() -> str:
    """Shared X-API-Token for RS hosting / Diagnosta edge routes."""
    return require_env_any("RS_X_API_TOKEN", "RS_DIAG_API_TOKEN")


def require_telegram_bot_token() -> str:
    return require_env_any("TELEGRAM_BOT_TOKEN")


def require_telegram_chat_id() -> str:
    return require_env_any("TELEGRAM_CHAT_ID")


def require_telegram_operator() -> tuple[str, str]:
    return require_telegram_bot_token(), require_telegram_chat_id()


def require_facebook_page_access_token() -> str:
    """Long-lived user or page token for Graph (publish, metadata)."""
    return require_env_any(
        "FACEBOOK_PAGE_ACCESS_TOKEN",
        "META_PAGE_ACCESS_TOKEN",
    )


def require_facebook_page_update_token() -> str:
    """POST/PATCH Page object (emails etc.): page token preferred, else user with admin."""
    return require_env_any(
        "FACEBOOK_PAGE_ACCESS_TOKEN",
        "FACEBOOK_GRAPH_USER_ACCESS_TOKEN",
    )


def require_facebook_graph_user_token() -> str:
    """User token with `pages_show_list` / manage_pages (me/accounts flow)."""
    return require_env_any(
        "FACEBOOK_GRAPH_USER_ACCESS_TOKEN",
        "FACEBOOK_GRAPH_ACCESS_TOKEN",
    )


def require_facebook_page_id() -> str:
    return require_env_any("FACEBOOK_PAGE_ID")


def require_rs_blog_pipeline_key() -> str:
    return require_env_any("RS_BLOG_PIPELINE_KEY", "X_RS_BLOG_PIPELINE_KEY")


def require_telegram_token_migration() -> tuple[str, str, str, str]:
    """(old_bot_token, new_bot_token, old_chat_id, new_chat_id) for bulk n8n replace."""
    load_cursor_env()
    ot = (os.environ.get("TELEGRAM_BOT_TOKEN_REPLACE_FROM") or "").strip()
    nt = (os.environ.get("TELEGRAM_BOT_TOKEN_REPLACE_TO") or "").strip()
    oc = (os.environ.get("TELEGRAM_CHAT_ID_REPLACE_FROM") or "").strip()
    nc = (os.environ.get("TELEGRAM_CHAT_ID_REPLACE_TO") or "").strip()
    if not all((ot, nt, oc, nc)):
        raise RuntimeError(
            "Telegram migration requires TELEGRAM_BOT_TOKEN_REPLACE_FROM, "
            "TELEGRAM_BOT_TOKEN_REPLACE_TO, TELEGRAM_CHAT_ID_REPLACE_FROM, "
            "TELEGRAM_CHAT_ID_REPLACE_TO in .cursor/mcp.env."
        )
    return ot, nt, oc, nc
