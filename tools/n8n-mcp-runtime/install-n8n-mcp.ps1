#Requires -Version 5.1
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    Write-Error 'npm not on PATH. Install Node.js LTS, then re-run.'
    exit 1
}
npm install
Write-Host 'n8n-mcp-runtime ready. Configure .cursor/mcp.env then reload MCP in Cursor.'
