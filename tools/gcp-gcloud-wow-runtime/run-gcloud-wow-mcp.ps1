#Requires -Version 5.1
<#
.SYNOPSIS
  RS Gravity — gcloud read-only status MCP (stdio).

.DESCRIPTION
  No secrets file required. Requires Google Cloud SDK on PATH (install-gcloud-wow.ps1).
#>
param()
$ErrorActionPreference = 'Stop'
$RuntimeRoot = $PSScriptRoot
$entry = Join-Path $RuntimeRoot 'gcloud-status-mcp.mjs'
if (-not (Test-Path -LiteralPath (Join-Path $RuntimeRoot 'node_modules\@modelcontextprotocol\sdk\package.json'))) {
    [Console]::Error.WriteLine('gcloud WOW MCP: run install-gcloud-wow-runtime-deps.ps1 once (npm install).')
    exit 1
}
& node $entry
exit $LASTEXITCODE
