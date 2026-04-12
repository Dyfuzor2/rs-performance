$ErrorActionPreference = "Stop"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location -LiteralPath $Base
Write-Host "[qdrant-local] Stopping ..." -ForegroundColor DarkGray
& docker compose --project-name gravity-qdrant-local down
exit $LASTEXITCODE
