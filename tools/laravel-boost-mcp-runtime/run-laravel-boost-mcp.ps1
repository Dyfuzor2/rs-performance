# MCP stdio launcher: Laravel Boost (php artisan boost:mcp) from G:\gravity
$ErrorActionPreference = "Stop"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = "G:\gravity"
. (Join-Path $Base "Resolve-GravityPhp.ps1")

$Php = Get-GravityPhpExecutable -RuntimeDir $Base
if (-not $Php) {
    Write-Error @"
PHP not found. Install PHP 8.5+ for Windows, then either:
  - Add php.exe to PATH, or
  - Set user env GRAVITY_PHP to full path of php.exe, or
  - Copy php.path.example to php.path and put one line: full path to php.exe
See tools/laravel-boost-mcp-runtime/README.md
"@
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
