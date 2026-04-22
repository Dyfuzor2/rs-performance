# Instalacja zaleznosci npm dla MCP uzywanych w Cursor (workspace G:\gravity).
# Uruchom z PowerShell:  powershell -ExecutionPolicy Bypass -File G:\gravity\tools\install-cursor-mcp-deps.ps1

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)

Write-Host "== repo root (mcp-postgres, @playwright/mcp, lockfile) ==" -ForegroundColor Cyan
Push-Location $root
try {
    npm install
} finally {
    Pop-Location
}

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

$uv = Get-Command uv -ErrorAction SilentlyContinue
if ($uv) {
    Write-Host '== mcp-servers\qdrant-mcp-official (uv lock - qdrant-vps-wow MCP) ==' -ForegroundColor Cyan
    Push-Location (Join-Path $root "mcp-servers\qdrant-mcp-official")
    try {
        & $uv.Path sync
    } finally {
        Pop-Location
    }
} else {
    Write-Host 'WARN: uv nie na PATH - pominieto uv sync dla qdrant-mcp-official (qdrant-vps-wow). winget install -e --id astral-sh.uv' -ForegroundColor Yellow
}

Write-Host 'Done. Katalog glowny: mcp-postgres + Playwright MCP (piny w package.json). N8N: N8N_API_URL + N8N_API_KEY (patrz .cursor\mcp.env.example). Telegram: TELEGRAM_*; gcloud: PATH. Qdrant MCP: uv + QDRANT_* w mcp.env.' -ForegroundColor Green
