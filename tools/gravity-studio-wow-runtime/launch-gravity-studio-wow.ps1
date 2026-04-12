# One-shot April 2026+ WOW: refresh hub (setup -NoBrowser) then local server with browser (Clipboard on).
param(
    [int]$Port = 18765
)
$ErrorActionPreference = "Stop"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
$setup = Join-Path $Base "setup-gravity-studio-wow.ps1"
$serve = Join-Path $Base "serve-wow-hub.ps1"

Write-Host ""
Write-Host "  === Gravity Studio WOW (full lane) ===" -ForegroundColor Cyan
Write-Host "  1/2 setup (-NoBrowser) ..." -ForegroundColor DarkGray
& $setup -NoBrowser
if ($LASTEXITCODE -ne 0) {
    Write-Host "  setup exit $LASTEXITCODE" -ForegroundColor Red
    exit $LASTEXITCODE
}

Write-Host "  2/2 localhost hub (Port $Port, -Open) ..." -ForegroundColor DarkGray
& $serve -Port $Port -Open
exit $LASTEXITCODE
