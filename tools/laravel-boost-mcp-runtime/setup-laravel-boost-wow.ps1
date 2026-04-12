# One-shot: verify install, open local operator deck (no secrets).
$ErrorActionPreference = "Stop"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path

$p = Start-Process -FilePath "powershell.exe" -ArgumentList @(
    "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", (Join-Path $Base "install-laravel-boost-mcp.ps1")
) -Wait -PassThru -NoNewWindow
$ok = ($p.ExitCode -eq 0)

$Wow = Join-Path $Base "wow.html"
if (Test-Path $Wow) {
    $uri = ([System.Uri]$Wow).AbsoluteUri
    Write-Host ""
    Write-Host "  Opening Laravel Boost deck: $uri" -ForegroundColor DarkGray
    Start-Process $uri
}

Write-Host ""
if ($ok) {
    Write-Host "  DONE - Reload MCP server laravel-boost in Cursor." -ForegroundColor Green
} else {
    Write-Host "  Fix PHP path / composer install, then re-run this script." -ForegroundColor Yellow
}
Write-Host ""
