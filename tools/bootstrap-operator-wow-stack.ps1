#Requires -Version 5.1
<#
.SYNOPSIS
  April 2026+ - jeden bootstrap lokalnego stosu operatora (MCP + env + gcloud).

.DESCRIPTION
  Kolejnosc:
  1) Opcjonalnie: install-cursor-mcp-deps.ps1 — npm w **korzeniu** (mcp-postgres, @playwright/mcp) + n8n-mcp + runtimy (telegram, gcloud-wow) + **uv sync** w `mcp-servers\qdrant-mcp-official` (gdy `uv` na PATH, dla `qdrant-vps-wow`)
  2) install-gcloud-wow.ps1 (winget lub no-op juz zainstalowany)
  3) Hydrate .cursor/mcp.env gdy istnieje cursor.md, zawsze --check
  4) Szybka weryfikacja folderow node_modules MCP

.PARAMETER InstallDeps
  Uruchom pelny npm install dla runtimow MCP (dluzsze, pierwszy raz lub po zmianie package.json).

.EXAMPLE
  pwsh -File G:\gravity\tools\bootstrap-operator-wow-stack.ps1
  pwsh -File G:\gravity\tools\bootstrap-operator-wow-stack.ps1 -InstallDeps
#>
param(
    [switch]$InstallDeps
)

$ErrorActionPreference = 'Stop'
$RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $RepoRoot

function Write-WowBanner {
    param([string]$Line)
    Write-Host ""
    Write-Host "  [ RS Gravity | operator WOW | 2026-04+ ]" -ForegroundColor Cyan
    Write-Host "  $Line" -ForegroundColor Gray
    Write-Host ""
}

Write-WowBanner "bootstrap start | repo: $RepoRoot"

if ($InstallDeps) {
    Write-Host "== [1/4] MCP npm runtimes (install-cursor-mcp-deps) ==" -ForegroundColor Cyan
    & (Join-Path $PSScriptRoot 'install-cursor-mcp-deps.ps1')
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
} else {
    Write-Host '== [1/4] MCP npm - SKIP (use -InstallDeps to run) ==' -ForegroundColor DarkGray
}

Write-Host "== [2/4] Google Cloud SDK (install-gcloud-wow) ==" -ForegroundColor Cyan
& (Join-Path $PSScriptRoot 'gcp-gcloud-wow-runtime\install-gcloud-wow.ps1')
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host "== [3/4] Operator env (hydrate + check) ==" -ForegroundColor Cyan
$py = Get-Command python -ErrorAction SilentlyContinue
if (-not $py) {
    Write-Error 'python not on PATH'
    exit 1
}
$cursorMd = Join-Path $RepoRoot 'cursor.md'
if (Test-Path -LiteralPath $cursorMd) {
    & (Join-Path $RepoRoot 'scripts\sync-operator-env-wow.ps1')
    $envCheck = $LASTEXITCODE
    if ($envCheck -ne 0 -and $envCheck -ne 2) { exit $envCheck }
    if ($envCheck -eq 2) {
        Write-Host '  (--check: some operator keys still missing; see output above)' -ForegroundColor Yellow
    }
} else {
    Write-Host '  (no cursor.md - skip merge; running --check only)' -ForegroundColor Yellow
    & python (Join-Path $RepoRoot 'scripts\hydrate_mcp_env_from_workspace.py') --check
    $envCheck = $LASTEXITCODE
    if ($envCheck -ne 0 -and $envCheck -ne 2) { exit $envCheck }
}

Write-Host "== [4/4] MCP runtime folders ==" -ForegroundColor Cyan
$runtimes = @(
    @{ Name = 'n8n-mcp-runtime'; Path = (Join-Path $PSScriptRoot 'n8n-mcp-runtime') },
    @{ Name = 'telegram-mcp-runtime'; Path = (Join-Path $PSScriptRoot 'telegram-mcp-runtime') },
    @{ Name = 'gcp-gcloud-wow-runtime'; Path = (Join-Path $PSScriptRoot 'gcp-gcloud-wow-runtime') }
)
foreach ($r in $runtimes) {
    $sdk = Join-Path $r.Path 'node_modules\@modelcontextprotocol\sdk\package.json'
    $ok = Test-Path -LiteralPath $sdk
    $st = if ($ok) { 'OK ' } else { 'MISS' }
    $color = if ($ok) { 'Green' } else { 'Yellow' }
    Write-Host "  [$st] $($r.Name)" -ForegroundColor $color
    if (-not $ok) {
        Write-Host "      -> run: pwsh -File $($PSScriptRoot)\install-cursor-mcp-deps.ps1" -ForegroundColor DarkYellow
    }
}

& (Join-Path $PSScriptRoot 'verify-cursor-mcp-wow.ps1')
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-WowBanner "done"
Write-Host '  Next: Cursor - Command Palette - MCP: Restart Servers (lub reload okna).' -ForegroundColor White
Write-Host '  Serwery: n8n-mcp, telegram-rs, gcloud-wow, laravel-boost, github, ...' -ForegroundColor Gray
Write-Host ""
