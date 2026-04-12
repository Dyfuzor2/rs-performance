# Gravity Studio WOW (single entry, April 2026+)

Runs **one** health pass across:

- **PHP WOW gate** — `Ensure-GravityPhpIni.ps1` (WinGet `php.ini` + mbstring) unless `GRAVITY_SKIP_PHP_INI=1`
- **GitHub MCP** (binary + token check)
- **Laravel Boost MCP** (`boost:mcp` / `APP_ENV=local` in child scripts)
- **Qdrant local** (Docker) — only if Docker Desktop is running
- **RAG readyz** — `verify-rag-ready.ps1` after Qdrant smoke passes (`/readyz` + collections)

Opens **one** hub page (`wow.html`, generated from `wow.template.html`) with live status chips, **clipboard quick actions** (komenda Studio, lista MCP, checklist Reload), toast UI, staggered card motion, and **JSON-LD** `WebApplication` (AEO-friendly). Brak spamu wielu okien z sub-runtime'ów.

**Uwaga `file://`:** w niektórych przeglądarkach `navigator.clipboard` jest zablokowany dla lokalnych plików; wtedy skopiuj tekst ręcznie z wygenerowanego `last-run-status.json` / ponownie uruchom setup i użyj terminala.

**Agent / CI:** `setup-gravity-studio-wow.ps1 -NoBrowser` — writes `wow.html` and JSON, does not open the default browser.

## Run

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File G:\gravity\tools\gravity-studio-wow-runtime\setup-gravity-studio-wow.ps1

# Bez otwierania przeglądarki:
powershell -NoProfile -ExecutionPolicy Bypass -File G:\gravity\tools\gravity-studio-wow-runtime\setup-gravity-studio-wow.ps1 -NoBrowser
```

Then in **Cursor**: reload MCP and enable `github`, `laravel-boost`, `qdrant-*-local` as needed.

## Files

| File | Role |
|------|------|
| `setup-gravity-studio-wow.ps1` | Orchestrator |
| `wow.template.html` | Source layout (committed) |
| `wow.html` | Generated (gitignored) |
| `last-run-status.json` | Generated snapshot (gitignored) |
