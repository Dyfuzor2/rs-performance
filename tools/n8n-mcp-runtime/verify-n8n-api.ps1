#Requires -Version 5.1
<#
.SYNOPSIS
  Quick RS n8n API reachability check (same creds as MCP).
#>
param()
$ErrorActionPreference = 'Stop'

$RuntimeRoot = $PSScriptRoot
$RepoRoot = (Resolve-Path (Join-Path $RuntimeRoot '..\..')).Path
$EnvFile = Join-Path $RepoRoot '.cursor\mcp.env'

if (Test-Path -LiteralPath $EnvFile) {
    Get-Content -LiteralPath $EnvFile | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq '' -or $line.StartsWith('#')) { return }
        $eq = $line.IndexOf('=')
        if ($eq -lt 1) { return }
        $k = $line.Substring(0, $eq).Trim()
        $v = $line.Substring($eq + 1).Trim()
        if ($k -ne '') { Set-Item -Path "Env:$k" -Value $v }
    }
}

if (-not $env:N8N_API_URL -or -not $env:N8N_API_KEY) {
    Write-Error 'Set N8N_API_URL and N8N_API_KEY in .cursor/mcp.env'
    exit 1
}

$base = $env:N8N_API_URL.TrimEnd('/')
$uri = "$base/api/v1/workflows?limit=1"
$headers = @{
    'X-N8N-API-KEY' = $env:N8N_API_KEY
    'Accept'        = 'application/json'
}

try {
    $r = Invoke-RestMethod -Uri $uri -Headers $headers -Method Get -TimeoutSec 30
    Write-Host "OK: n8n API at $base (workflows probe returned data)."
    exit 0
} catch {
    Write-Error "n8n API probe failed: $($_.Exception.Message)"
    exit 1
}
