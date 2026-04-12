#Requires -Version 5.1
<#
.SYNOPSIS
  RS Gravity — Telegram Bot API MCP stdio launcher (April 2026+).

.DESCRIPTION
  Loads G:\gravity\.cursor\mcp.env, requires TELEGRAM_BOT_TOKEN, runs pinned node server.
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

$tok = [Environment]::GetEnvironmentVariable('TELEGRAM_BOT_TOKEN', 'Process')
if ([string]::IsNullOrWhiteSpace($tok)) {
    [Console]::Error.WriteLine(
        'telegram MCP: missing TELEGRAM_BOT_TOKEN. Copy .cursor/mcp.env.example -> .cursor/mcp.env. See tools/telegram-mcp-runtime/README.md'
    )
    exit 1
}

$entry = Join-Path $RuntimeRoot 'telegram-mcp.mjs'
if (-not (Test-Path -LiteralPath (Join-Path $RuntimeRoot 'node_modules\@modelcontextprotocol\sdk\package.json'))) {
    [Console]::Error.WriteLine('telegram MCP: run tools/telegram-mcp-runtime/install-telegram-mcp.ps1 once (npm install).')
    exit 1
}

& node $entry
exit $LASTEXITCODE
