# n8n MCP runtime (RS Gravity)

Pinned **`n8n-mcp@^2.47.5`** — no per-session `npx` resolution. Cursor talks to your real n8n over HTTPS via API key.

## One-time setup

1. `powershell -ExecutionPolicy Bypass -File install-n8n-mcp.ps1`
2. Copy `G:\gravity\.cursor\mcp.env.example` → `G:\gravity\.cursor\mcp.env` (file is gitignored).
3. Set `N8N_API_URL` (e.g. `https://auto.rs3d.pl`) and `N8N_API_KEY` from n8n **Settings → API**.
4. Optional: `powershell -File verify-n8n-api.ps1` — must print `OK`.
5. **Cursor:** reload window (or restart MCP). Entry `n8n-mcp` must use `run-n8n-mcp.ps1` (see `.mcp.json` / `.cursor/mcp.json`).

## Operations on VPS

MCP speeds up **design / validate / discover nodes**. Production changes (import, cron, dedup) still follow **backup + SSH / `vps_exec`** per project rules.
