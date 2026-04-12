# Start local Qdrant (Docker Compose) - RS Gravity vector RAG plane
$ErrorActionPreference = "Stop"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location -LiteralPath $Base

$docker = Get-Command docker -ErrorAction SilentlyContinue
if (-not $docker) {
    Write-Host "FAIL: Docker not found. Install Docker Desktop for Windows and ensure 'docker' is on PATH." -ForegroundColor Red
    exit 1
}

Write-Host "[qdrant-local] Starting Qdrant in $Base ..." -ForegroundColor Cyan
& docker compose --project-name gravity-qdrant-local up -d
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}
Write-Host "[qdrant-local] OK - container gravity-qdrant-local (see docker compose ps)." -ForegroundColor Green
