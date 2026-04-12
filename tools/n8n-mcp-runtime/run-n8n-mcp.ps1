#Requires -Version 5.1
<#
.SYNOPSIS
  RS Gravity — pinned n8n-mcp stdio launcher for Cursor (April 2026+ operator pattern).

.DESCRIPTION
  1) Loads G:\gravity\.cursor\mcp.env if present (gitignored).
  2) Requires N8N_API_URL + N8N_API_KEY for live workflow tools.
  3) Runs node_modules n8n-mcp (no npx drift).

  Copy .cursor/mcp.env.example -> .cursor/mcp.env and fill the key.
#>
param()
$ErrorActionPreference = 'Stop'

$RuntimeRoot = $PSScriptRoot
$RepoRoot = (Resolve-Path (Join-Path $RuntimeRoot '..\..')).Path
$EnvFile = Join-Path $RepoRoot '.cursor\mcp.env'

if (Test-Path -LiteralPath $EnvFile) {
    Get-Content -LiteralPath $EnvFile | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq '' -or $line.StartsWith('#')) {
            return
        }
        $eq = $line.IndexOf('=')
        if ($eq -lt 1) {
            return
        }
        $key = $line.Substring(0, $eq).Trim()
        $val = $line.Substring($eq + 1).Trim()
        if ($key -ne '') {
            [Environment]::SetEnvironmentVariable($key, $val, 'Process')
        }
    }
}

$apiUrl = [Environment]::GetEnvironmentVariable('N8N_API_URL', 'Process')
$apiKey = [Environment]::GetEnvironmentVariable('N8N_API_KEY', 'Process')
if ([string]::IsNullOrWhiteSpace($apiUrl) -or [string]::IsNullOrWhiteSpace($apiKey)) {
    [Console]::Error.WriteLine(
        'n8n MCP: missing N8N_API_URL or N8N_API_KEY. Copy .cursor/mcp.env.example -> .cursor/mcp.env, set values, reload MCP. See tools/n8n-mcp-runtime/README.md'
    )
    exit 1
}

$env:MCP_MODE = if ($env:MCP_MODE) { $env:MCP_MODE } else { 'stdio' }
if (-not $env:LOG_LEVEL) {
    $env:LOG_LEVEL = 'error'
}
if (-not $env:DISABLE_CONSOLE_OUTPUT) {
    $env:DISABLE_CONSOLE_OUTPUT = 'true'
}

$n8nCmd = Join-Path $RuntimeRoot 'node_modules\.bin\n8n-mcp.cmd'
if (-not (Test-Path -LiteralPath $n8nCmd)) {
    [Console]::Error.WriteLine('n8n MCP: run tools/n8n-mcp-runtime/install-n8n-mcp.ps1 once (npm install).')
    exit 1
}

& $n8nCmd
exit $LASTEXITCODE
