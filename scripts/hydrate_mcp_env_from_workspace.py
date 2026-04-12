"""Merge secrets from local `cursor.md` (+ optional HANDOFF) into `.cursor/mcp.env`.

April 2026+ operator flow: keep one canonical local pack (`cursor.md`, gitignored via
project policy) and sync operator keys into `mcp.env` for `gravity_cursor_env`.

Does not print secret values. Run: python scripts/hydrate_mcp_env_from_workspace.py
"""

from __future__ import annotations

import re
import sys
from pathlib import Path


def repo_root() -> Path:
    return Path(__file__).resolve().parents[1]


def parse_backtick_bullets(text: str) -> dict[str, str]:
    """`- Label: `value`` lines."""
    out: dict[str, str] = {}
    for m in re.finditer(r"^-\s*([^:`]+):\s*`([^`]+)`", text, re.MULTILINE):
        label = m.group(1).strip()
        out[label] = m.group(2).strip()
    return out


def parse_n8n_jwt(text: str) -> str | None:
    m = re.search(r"n8n API key:\s*\n\s*`([^`]+)`", text)
    if m:
        return m.group(1).strip()
    return None


def parse_n8n_url(text: str) -> str | None:
    for pat in (
        r"^-\s*n8n:\s*`(https?://[^`]+)`",
        r"^-\s*n8n URL:\s*`(https?://[^`]+)`",
    ):
        m = re.search(pat, text, re.MULTILINE)
        if m:
            return m.group(1).strip().rstrip("/")
    return None


def parse_facebook_page_id_handoff(text: str) -> str | None:
    m = re.search(r"Page ID\s+(\d{10,20})", text)
    return m.group(1) if m else None


def harvest_openrouter_key(root: Path) -> str | None:
    """First OpenRouter key found in root-level or storage/app JSON exports."""
    pat = re.compile(r"sk-or-v1-[a-fA-F0-9]{64}")
    dirs = [root, root / "storage" / "app"]
    for d in dirs:
        if not d.is_dir():
            continue
        for p in sorted(d.glob("*.json")):
            try:
                blob = p.read_text(encoding="utf-8", errors="ignore")
            except OSError:
                continue
            m = pat.search(blob)
            if m:
                return m.group(0)
    return None


def harvest_meta_page_token(root: Path) -> str | None:
    """Long-lived Meta user/page token (Graph) from JSON workflow dumps."""
    pat = re.compile(r"EAARO[A-Za-z0-9]{120,}")
    dirs = [root, root / "storage" / "app"]
    best: str | None = None
    for d in dirs:
        if not d.is_dir():
            continue
        for p in sorted(d.glob("*.json")):
            try:
                blob = p.read_text(encoding="utf-8", errors="ignore")
            except OSError:
                continue
            for m in pat.finditer(blob):
                cand = m.group(0)
                if best is None or len(cand) > len(best):
                    best = cand
    return best


def merge_into_mcp_env(env_path: Path, updates: dict[str, str]) -> int:
    text = env_path.read_text(encoding="utf-8") if env_path.is_file() else ""
    written = 0
    for key, val in updates.items():
        if not val:
            continue
        line = f"{key}={val}"
        pat = rf"(?m)^{re.escape(key)}=.*$"
        if re.search(pat, text):
            text = re.sub(pat, line, text)
        else:
            text = text.rstrip() + f"\n{line}\n"
        written += 1
    env_path.parent.mkdir(parents=True, exist_ok=True)
    env_path.write_text(text, encoding="utf-8")
    return written


def main() -> int:
    root = repo_root()
    cursor = root / "cursor.md"
    handoff = root / "HANDOFF.md"
    env_path = root / ".cursor" / "mcp.env"

    if not cursor.is_file():
        print("cursor.md not found — nothing to import.", file=sys.stderr)
        return 1

    raw = cursor.read_text(encoding="utf-8")
    bullets = parse_backtick_bullets(raw)

    updates: dict[str, str] = {}

    url = parse_n8n_url(raw)
    if url:
        updates["N8N_API_URL"] = url

    jwt = parse_n8n_jwt(raw)
    if jwt:
        updates["N8N_API_KEY"] = jwt

    if "DIAGNOSTA_API_TOKEN" in bullets:
        updates["RS_X_API_TOKEN"] = bullets["DIAGNOSTA_API_TOKEN"]

    if "BLOG_TELEGRAM_BOT_TOKEN" in bullets:
        updates["TELEGRAM_BOT_TOKEN"] = bullets["BLOG_TELEGRAM_BOT_TOKEN"]

    if "BLOG_TELEGRAM_CHAT_ID" in bullets:
        updates["TELEGRAM_CHAT_ID"] = bullets["BLOG_TELEGRAM_CHAT_ID"]

    for k in ("BLOG_PIPELINE_WEBHOOK_KEY", "BLOG_DRAFT_WEBHOOK_KEY"):
        if k in bullets:
            updates["RS_BLOG_PIPELINE_KEY"] = bullets[k]
            break

    if handoff.is_file():
        hid = parse_facebook_page_id_handoff(handoff.read_text(encoding="utf-8"))
        if hid:
            updates["FACEBOOK_PAGE_ID"] = hid

    or_key = harvest_openrouter_key(root)
    if or_key:
        updates["OPENROUTER_API_KEY"] = or_key

    fb = harvest_meta_page_token(root)
    if fb:
        updates["FACEBOOK_PAGE_ACCESS_TOKEN"] = fb
        updates["FACEBOOK_GRAPH_USER_ACCESS_TOKEN"] = fb

    n = merge_into_mcp_env(env_path, updates)
    print(f"Updated .cursor/mcp.env ({n} keys merged from workspace).")
    print("Verify: python -c \"from pathlib import Path; import sys; sys.path.insert(0,'scripts'); from gravity_cursor_env import require_n8n_api; require_n8n_api(); print('n8n OK')\"")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
