#Requires -Version 5.1
<#
.SYNOPSIS
  April 2026+ one-shot: merge cursor.md + JSON harvest into .cursor/mcp.env, then masked --check.

.DESCRIPTION
  Run from anywhere; resolves repo root as parent of this script directory.
  Does not print secrets. Exit code 2 from --check means some operator keys are still missing.
#>
$ErrorActionPreference = 'Stop'
$RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $RepoRoot

$py = Get-Command python -ErrorAction SilentlyContinue
if (-not $py) {
    Write-Error 'python not on PATH'
    exit 1
}

& python (Join-Path $RepoRoot 'scripts\hydrate_mcp_env_from_workspace.py')
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

& python (Join-Path $RepoRoot 'scripts\hydrate_mcp_env_from_workspace.py') --check
exit $LASTEXITCODE
