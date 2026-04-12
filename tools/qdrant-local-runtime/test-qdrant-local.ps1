# HTTP readiness against local Qdrant REST (no secrets).
$ErrorActionPreference = "Stop"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
$envFile = Join-Path $Base ".env"
if (Test-Path -LiteralPath $envFile) {
    Get-Content -LiteralPath $envFile | ForEach-Object {
        if ($_ -match '^\s*QDRANT_LOCAL_HTTP=(\d+)\s*$') {
            $script:Port = $Matches[1]
        }
    }
}
if (-not $script:Port) { $script:Port = "6333" }

function Test-QdrantEndpoint {
    param([string]$Path)
    $u = "http://127.0.0.1:$($script:Port)$Path"
    try {
        $r = Invoke-WebRequest -Uri $u -UseBasicParsing -TimeoutSec 15
        return ($r.StatusCode -ge 200 -and $r.StatusCode -lt 300)
    } catch {
        return $false
    }
}

$paths = @("/readyz", "/healthz", "/")
foreach ($p in $paths) {
    $Url = "http://127.0.0.1:$($script:Port)$p"
    Write-Host "[qdrant-local] GET $Url" -ForegroundColor DarkGray
    if (Test-QdrantEndpoint -Path $p) {
        Write-Host "[qdrant-local] OK - Qdrant responded on $p" -ForegroundColor Green
        exit 0
    }
}

Write-Host "FAIL: Qdrant not reachable on port $($script:Port)." -ForegroundColor Red
Write-Host "Hint: run start-qdrant.ps1; first image pull can take minutes." -ForegroundColor Yellow
exit 1
