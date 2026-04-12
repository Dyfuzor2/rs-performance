# Local static server for Gravity Studio WOW hub - unlocks Clipboard API (not file://).
# April 2026+ - prefers PHP built-in server (same stack as Laravel), then Python http.server.
$ErrorActionPreference = "Stop"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
$Port = 18765
$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")

Write-Host ""
Write-Host "  Gravity WOW hub: http://127.0.0.1:${Port}/wow.html" -ForegroundColor Cyan
Write-Host "  Document root: $Base" -ForegroundColor DarkGray
Write-Host "  Ctrl+C = stop" -ForegroundColor DarkGray
Write-Host ""
Set-Location -LiteralPath $Base

$php = Get-Command php -ErrorAction SilentlyContinue
if ($php -and $php.Source) {
    & $php.Source @("-S", "127.0.0.1:${Port}", "-t", $Base)
    exit $LASTEXITCODE
}

$py = Get-Command python -ErrorAction SilentlyContinue
if ($py -and $py.Source) {
    & $py.Source @("-m", "http.server", "$Port", "--bind", "127.0.0.1")
    exit $LASTEXITCODE
}

Write-Host "FAIL: Need php or python on PATH (WinGet PHP.PHP.8.5 or Python 3)." -ForegroundColor Red
exit 1
