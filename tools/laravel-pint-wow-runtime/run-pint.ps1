# Laravel Pint (local agent runner) - same PHP resolution as laravel-boost-mcp-runtime.
# Usage: .\run-pint.ps1 [--dirty] [extra pint args...]
$ErrorActionPreference = "Stop"
$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = "G:\gravity"
. (Join-Path $Base "..\laravel-boost-mcp-runtime\Resolve-GravityPhp.ps1")

$Php = Get-GravityPhpExecutable -RuntimeDir (Join-Path $Base "..\laravel-boost-mcp-runtime")
if (-not $Php) {
    Write-Host "FAIL: PHP not found. Use GRAVITY_PHP or tools/laravel-boost-mcp-runtime/php.path" -ForegroundColor Red
    exit 1
}

$pint = Join-Path $ProjectRoot "vendor\bin\pint"
if (-not (Test-Path -LiteralPath $pint)) {
    Write-Host "FAIL: $pint missing. Run: composer install in G:\gravity" -ForegroundColor Red
    exit 1
}

Set-Location -LiteralPath $ProjectRoot
& $Php @($pint) @args
exit $LASTEXITCODE
