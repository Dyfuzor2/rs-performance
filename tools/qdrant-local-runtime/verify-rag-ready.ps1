# Quick RAG plane check: Docker Qdrant REST + optional collection list (no secrets).
$ErrorActionPreference = "Stop"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
$envFile = Join-Path $Base ".env"
$port = "6333"
if (Test-Path -LiteralPath $envFile) {
    Get-Content -LiteralPath $envFile | ForEach-Object {
        if ($_ -match '^\s*QDRANT_LOCAL_HTTP=(\d+)\s*$') { $script:port = $Matches[1] }
    }
}
$baseUrl = "http://127.0.0.1:$port"

Write-Host "[rag-local] GET $baseUrl/readyz" -ForegroundColor DarkGray
try {
    $r = Invoke-WebRequest -Uri "$baseUrl/readyz" -UseBasicParsing -TimeoutSec 10
    Write-Host "[rag-local] OK - Qdrant ready (HTTP $($r.StatusCode))" -ForegroundColor Green
} catch {
    Write-Host "[rag-local] FAIL - Start Docker + tools/qdrant-local-runtime/start-qdrant.ps1" -ForegroundColor Red
    exit 1
}

try {
    $c = Invoke-WebRequest -Uri "$baseUrl/collections" -UseBasicParsing -TimeoutSec 10
    Write-Host "[rag-local] Collections API OK (length $($c.Content.Length) bytes)" -ForegroundColor DarkGray
} catch {
    Write-Host "[rag-local] WARN - could not list collections" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "MCP: enable qdrant-rs-knowledge-local, qdrant-rs-dynamic-local, qdrant-rs-answer-routing-local in .mcp.json then Reload MCP." -ForegroundColor Cyan
Write-Host ""
