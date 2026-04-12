# MCP stdio launcher: Laravel Boost (php artisan boost:mcp) from G:\gravity
$ErrorActionPreference = "Stop"
# Cursor spawns this script with a minimal PATH; merge Machine+User like a fresh login session.
$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = "G:\gravity"
. (Join-Path $Base "Resolve-GravityPhp.ps1")

$Php = Get-GravityPhpExecutable -RuntimeDir $Base
if (-not $Php) {
    [Console]::Error.WriteLine(@"
PHP not found (Cursor MCP often lacks winget PHP on PATH).
Fix one of: winget install PHP.PHP.8.5 | User env GRAVITY_PHP=full\path\to\php.exe | php.path in this folder (see php.path.example)
Docs: tools/laravel-boost-mcp-runtime/README.md
"@)
    exit 1
}

$Artisan = Join-Path $ProjectRoot "artisan"
if (-not (Test-Path -LiteralPath $Artisan)) {
    Write-Error "Missing $Artisan - open G:\gravity as the Laravel project root."
    exit 1
}

Set-Location -LiteralPath $ProjectRoot

# BoostServiceProvider::shouldRun() enables Boost only for local env OR app.debug=true.
# Many dev machines keep APP_ENV=production in .env; MCP still needs Boost registered.
$env:APP_ENV = 'local'

& $Php @("artisan", "boost:mcp")
