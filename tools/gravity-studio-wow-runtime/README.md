# Gravity Studio WOW (single entry, April 2026+)

Runs **one** health pass across:

- **PHP WOW gate** — `Ensure-GravityPhpIni.ps1` (WinGet `php.ini` + mbstring) unless `GRAVITY_SKIP_PHP_INI=1`
- **GitHub MCP** (binary + token check)
- **Laravel Boost MCP** (`boost:mcp` / `APP_ENV=local` in child scripts)
- **Qdrant local** (Docker) — only if Docker Desktop is running
- **RAG readyz** — `verify-rag-ready.ps1` after Qdrant smoke passes (`/readyz` + collections)

Opens **one** hub page (`wow.html`, generated from `wow.template.html`) with live status chips, **clipboard quick actions** (Studio, serve, launch, MCP list, Reload checklist), toast UI, staggered card motion, **Speculation Rules** prefetch sibling decks, light/dark `color-scheme`, and **JSON-LD** `WebApplication` (AEO-friendly).

**Clipboard przy `file://`:** przyciski używają **Clipboard API**; przy błędzie hub otwiera pola read-only (Ctrl+C). **`serve-wow-hub.ps1 -Open`** serwuje `http://127.0.0.1:18765/wow.html` i może sam otworzyć kartę.

**Agent / CI:** `setup-gravity-studio-wow.ps1 -NoBrowser` — writes `wow.html` and JSON, does not open the default browser.

## Run

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File G:\gravity\tools\gravity-studio-wow-runtime\setup-gravity-studio-wow.ps1

# Bez otwierania przeglądarki:
powershell -NoProfile -ExecutionPolicy Bypass -File G:\gravity\tools\gravity-studio-wow-runtime\setup-gravity-studio-wow.ps1 -NoBrowser

# Hub przez localhost (Clipboard + WOW UI + auto-przeglądarka):
powershell -NoProfile -ExecutionPolicy Bypass -File G:\gravity\tools\gravity-studio-wow-runtime\serve-wow-hub.ps1 -Port 18765 -Open

# Jednym strzałem: setup (-NoBrowser) + serve (-Open):
powershell -NoProfile -ExecutionPolicy Bypass -File G:\gravity\tools\gravity-studio-wow-runtime\launch-gravity-studio-wow.ps1
```

Then in **Cursor**: reload MCP and enable `github`, `laravel-boost`, `qdrant-*-local` as needed.

**Portable paths:** `setup-gravity-studio-wow.ps1` resolves `tools/` and writes commands into `wow.html` from the repo layout (no hardcoded drive letter).

## Files

| File                           | Role                                            |
| ------------------------------ | ----------------------------------------------- |
| `setup-gravity-studio-wow.ps1` | Orchestrator                                    |
| `wow.template.html`            | Source layout (committed)                       |
| `wow.html`                     | Generated (gitignored)                          |
| `last-run-status.json`         | Generated snapshot (gitignored)                 |
| `serve-wow-hub.ps1`            | `-Port`, `-Open`; PHP `-S` or Python; optional Ensure-GravityPhpIni before PHP |
| `launch-gravity-studio-wow.ps1` | `setup -NoBrowser` then `serve -Open` (full WOW) |
