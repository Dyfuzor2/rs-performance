# Instalacja zaleznosci npm dla MCP uzywanych w Cursor (workspace G:\gravity).
# Uruchom z PowerShell:  powershell -ExecutionPolicy Bypass -File G:\gravity\tools\install-cursor-mcp-deps.ps1

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)

Write-Host "== n8n-mcp repo (lokalny build / referencja) ==" -ForegroundColor Cyan
Push-Location (Join-Path $root "mcp-servers\n8n-mcp")
try {
    npm install
    if (-not (Test-Path "dist")) {
        npm run build
    }
} finally {
    Pop-Location
}

Write-Host "== n8n-mcp-runtime (pin wersji) ==" -ForegroundColor Cyan
Push-Location (Join-Path $root "tools\n8n-mcp-runtime")
try {
    npm install
} finally {
    Pop-Location
}

Write-Host "== telegram-mcp-runtime (Bot API stdio) ==" -ForegroundColor Cyan
Push-Location (Join-Path $root "tools\telegram-mcp-runtime")
try {
    npm install
} finally {
    Pop-Location
}

Write-Host "== gcp-gcloud-wow-runtime (read-only gcloud MCP) ==" -ForegroundColor Cyan
Push-Location (Join-Path $root "tools\gcp-gcloud-wow-runtime")
try {
    npm install
} finally {
    Pop-Location
}

Write-Host "Done. Ustaw N8N_API_URL i N8N_API_KEY w srodowisku (patrz .cursor\mcp.env.example). Telegram: TELEGRAM_*; gcloud MCP wymaga Google Cloud SDK na PATH." -ForegroundColor Green
