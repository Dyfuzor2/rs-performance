# Gravity Studio WOW (single entry, April 2026+)

Runs **one** health pass across:

- **GitHub MCP** (binary + token check)
- **Laravel Boost MCP** (`boost:mcp` / `APP_ENV=local` in child scripts)
- **Qdrant local** (Docker) — only if Docker Desktop is running

Opens **one** hub page (`wow.html`, generated from `wow.template.html`) with live status chips — no spam of multiple browser windows from sub-runtimes.

## Run

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File G:\gravity\tools\gravity-studio-wow-runtime\setup-gravity-studio-wow.ps1
```

Then in **Cursor**: reload MCP and enable `github`, `laravel-boost`, `qdrant-*-local` as needed.

## Files

| File | Role |
|------|------|
| `setup-gravity-studio-wow.ps1` | Orchestrator |
| `wow.template.html` | Source layout (committed) |
| `wow.html` | Generated (gitignored) |
| `last-run-status.json` | Generated snapshot (gitignored) |
