# Laravel Boost MCP (local runtime, pinned next to GitHub MCP)

Runs `php artisan boost:mcp` from **`G:\gravity`** via a PowerShell launcher so Cursor can use the same pattern as `tools/github-mcp-runtime/` (absolute paths, health checks, wow deck).

## Requirements

- **PHP 8.5+** on Windows (see project stack), with extensions Laravel needs.
- **`G:\gravity`** must contain `artisan`, `vendor/laravel/boost` (`composer install`).

## PHP resolution (pick one)

1. **`php.exe` on PATH** (simplest).
2. **User env `GRAVITY_PHP`** = full path to `php.exe`.
3. **`php.path`** in this folder: copy `php.path.example` → `php.path`, one line = full path to `php.exe`.

## Quick path (verify + deck)

```powershell
Set-Location G:\gravity\tools\laravel-boost-mcp-runtime
powershell -ExecutionPolicy Bypass -File .\setup-laravel-boost-wow.ps1
```

## Install check only

```powershell
powershell -ExecutionPolicy Bypass -File .\install-laravel-boost-mcp.ps1
```

## Cursor

Root **`G:\gravity\.mcp.json`** uses server **`laravel-boost`** → `run-laravel-boost-mcp.ps1`. Reload MCP after PHP path changes.

## Notes

- Boost MCP speaks **stdio** to Cursor; the Laravel app code lives in **`G:\gravity`** — not on VPS for this MCP entry.
- No secrets in this folder; `.env` stays in project root (gitignored).
- **Environment:** Laravel Boost only boots when `APP_ENV=local` or `APP_DEBUG=true`. The launcher and install check set `APP_ENV=local` for that process only, so `boost:mcp` works even if your `.env` says `production` (common on a shared dev PC).
