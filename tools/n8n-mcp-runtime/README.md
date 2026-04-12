# n8n MCP runtime (RS Gravity)

Pinned **`n8n-mcp@^2.47.5`** — no per-session `npx` resolution. Cursor talks to your real n8n over HTTPS via API key.

## One-time setup

1. `powershell -ExecutionPolicy Bypass -File install-n8n-mcp.ps1`
2. Copy `G:\gravity\.cursor\mcp.env.example` → `G:\gravity\.cursor\mcp.env` (file is gitignored).
3. Set `N8N_API_URL` (e.g. `https://auto.rs3d.pl`) and `N8N_API_KEY` from n8n **Settings → API**.
4. Optional: `powershell -File verify-n8n-api.ps1` — must print `OK`.
5. Optional: `powershell -File smoke-critical-webhooks.ps1` — manifest-driven POST smoke (`critical-webhooks.manifest.json`).
6. **Cursor:** reload window (or restart MCP). Entry `n8n-mcp` must use `run-n8n-mcp.ps1` (see `.mcp.json` / `.cursor/mcp.json`).

## Python scripts (repo root + `execution/`)

`scripts/gravity_cursor_env.py` loads `.cursor/mcp.env` once per process:

- `require_n8n_api()` — n8n REST
- `require_openrouter_api_key()`, `require_rs_x_api_token()`, `require_rs_blog_pipeline_key()`
- `require_telegram_operator()` — `(TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID)`
- `require_facebook_page_access_token()`, `require_facebook_graph_user_token()`, `require_facebook_page_id()`, `require_facebook_page_update_token()`
- `require_telegram_token_migration()` — cztery zmienne `*_REPLACE_*` dla `fix_all_telegram_tokens.py`

Pełna lista kluczy: `.cursor/mcp.env.example`.

## Operations on VPS

MCP speeds up **design / validate / discover nodes**. Production changes (import, cron, dedup) still follow **backup + SSH / `vps_exec`** per project rules.
