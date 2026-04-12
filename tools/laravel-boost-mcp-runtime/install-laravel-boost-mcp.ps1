# Verifies PHP, vendor/laravel/boost, and artisan boost:mcp (April 2026+ local MCP hygiene).
$ErrorActionPreference = "Stop"
$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = "G:\gravity"
. (Join-Path $Base "Resolve-GravityPhp.ps1")

Write-Host "[laravel-boost-mcp] Resolving PHP ..."
$Php = Get-GravityPhpExecutable -RuntimeDir $Base
if (-not $Php) {
    Write-Host "FAIL: PHP not found. See README.md (GRAVITY_PHP, php.path, or PATH)." -ForegroundColor Red
    exit 1
}
Write-Host "[laravel-boost-mcp] Using: $Php" -ForegroundColor DarkGray
if (-not $env:GRAVITY_SKIP_PHP_INI) {
    $ensure = Join-Path $Base "Ensure-GravityPhpIni.ps1"
    & $ensure
    if ($LASTEXITCODE -ne 0) {
        Write-Host "FAIL: Ensure-GravityPhpIni.ps1" -ForegroundColor Red
        exit 1
    }
}
$ver = & $Php @("-v") 2>&1 | Out-String
Write-Host $ver

$boostPath = Join-Path $ProjectRoot "vendor\laravel\boost"
if (-not (Test-Path -LiteralPath $boostPath)) {
    Write-Host "FAIL: vendor/laravel/boost missing. Run: cd G:\gravity && composer install" -ForegroundColor Red
    exit 1
}

Write-Host "[laravel-boost-mcp] Checking boost:mcp ..."
Set-Location -LiteralPath $ProjectRoot

# Match run-laravel-boost-mcp.ps1: Boost registers commands only when local or debug.
$env:APP_ENV = 'local'

$list = & $Php @("artisan", "list", "--raw", "--no-ansi") 2>&1 | Out-String
if ($LASTEXITCODE -ne 0) {
    Write-Host "FAIL: php artisan list --raw" -ForegroundColor Red
    Write-Host $list
    exit 1
}
if ($list -notmatch "boost:mcp") {
    Write-Host "FAIL: boost:mcp not registered (laravel/boost missing or not installed)." -ForegroundColor Red
    exit 1
}
Write-Host "[laravel-boost-mcp] OK - Boost MCP artisan command is available." -ForegroundColor Green
Write-Host "[laravel-boost-mcp] Next: setup-laravel-boost-wow.ps1 or reload MCP laravel-boost in Cursor." -ForegroundColor DarkGray
exit 0
