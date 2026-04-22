#Requires -Version 5.1
<#
.SYNOPSIS
  RS Gravity — oficjalny Qdrant MCP (mcp-server-qdrant) wskazujacy na instancje na VPS, kwiecien 2026+.

.DESCRIPTION
  1) Laduje G:\gravity\.cursor\mcp.env
  2) Wymaga QDRANT_MCP_ENABLED=1 oraz QDRANT_URL (i opcjonalnie QDRANT_API_KEY, COLLECTION_NAME)
  3) Uruchamia: `uv run mcp-server-qdrant` w `mcp-servers\qdrant-mcp-official` (piny w pyproject)

  Produkcyjny Qdrant na VPS: Docker (compose) — z Windows tylko przez **tunel SSH** (analogicznie do Postgresa), np.:
    ssh -N -L 16333:127.0.0.1:6333 rsops@HOST
  Potem: QDRANT_URL=http://127.0.0.1:16333

  Zrodlo: https://github.com/qdrant/mcp-server-qdrant (lokalne lustro: mcp-servers/qdrant-mcp-official)
#>
$ErrorActionPreference = 'Stop'

$RuntimeRoot = $PSScriptRoot
$RepoRoot = (Resolve-Path (Join-Path $RuntimeRoot '..\..')).Path
$EnvFile = Join-Path $RepoRoot '.cursor\mcp.env'
$Official = Join-Path $RepoRoot 'mcp-servers\qdrant-mcp-official'

if (Test-Path -LiteralPath $EnvFile) {
    Get-Content -LiteralPath $EnvFile | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq '' -or $line.StartsWith('#')) { return }
        $eq = $line.IndexOf('=')
        if ($eq -lt 1) { return }
        $key = $line.Substring(0, $eq).Trim()
        $val = $line.Substring($eq + 1).Trim()
        if ($key -ne '') {
            [Environment]::SetEnvironmentVariable($key, $val, 'Process')
        }
    }
}

$enabled = [Environment]::GetEnvironmentVariable('QDRANT_MCP_ENABLED', 'Process')
if ($enabled -ne '1') {
    [Console]::Error.WriteLine(@'
qdrant-vps-wow: ustaw QDRANT_MCP_ENABLED=1 w .cursor/mcp.env + QDRANT_URL (tunel SSH do Qdrant na VPS).
Patrz RELAY.md (MySQL -> Qdrant), .cursor/mcp.env.example
'@)
    exit 1
}

$url = [Environment]::GetEnvironmentVariable('QDRANT_URL', 'Process')
if ([string]::IsNullOrWhiteSpace($url)) {
    [Console]::Error.WriteLine('qdrant-vps-wow: brak QDRANT_URL (URL instancji Qdrant, np. http://127.0.0.1:16333).')
    exit 1
}

if (-not (Test-Path -LiteralPath (Join-Path $Official 'pyproject.toml'))) {
    [Console]::Error.WriteLine("qdrant-vps-wow: brak katalogu: $Official")
    exit 1
}

$uv = Get-Command uv -ErrorAction SilentlyContinue
if (-not $uv) {
    [Console]::Error.WriteLine('qdrant-vps-wow: wymagany jest `uv` (Astral) na PATH — winget: Astral.uv, potem: uv sync w mcp-servers\qdrant-mcp-official (patrz install-cursor-mcp-deps.ps1).')
    exit 1
}

Set-Location -LiteralPath $Official
& $uv.Path 'run' 'mcp-server-qdrant'
exit $LASTEXITCODE
