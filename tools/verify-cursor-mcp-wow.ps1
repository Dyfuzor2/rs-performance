#Requires -Version 5.1
<#
.SYNOPSIS
  April 2026+ — szybka weryfikacja: `.cursor/mcp.json` + binaria / npm runtime'y MCP (WOW stack).

.DESCRIPTION
  Read-only. Nie instaluje nic (oprócz opcjonalnie sugestii w stderr). Exit 0 = wszystko OK; 1 = błąd / brak krytycznych elementów.
  Oczekiwane klucze: pelna lista w $expectedKeys w skrypcie (m.in. qdrant-vps-wow, postgres-vps-wow).

.EXAMPLE
  powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File G:\gravity\tools\verify-cursor-mcp-wow.ps1
  composer mcp-verify
  npm run mcp-verify
  G:\gravity\tools\mcp-verify.cmd
#>
param(
    [switch]$Json
)

$ErrorActionPreference = 'Stop'
$RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$McpJson = Join-Path $RepoRoot '.cursor\mcp.json'
$expectedKeys = @(
    'laravel-boost', 'filesystem-gravity', 'fetch', 'n8n-mcp', 'telegram-rs', 'gcloud-wow', 'github', 'playwright-wow', 'postgres-vps-wow', 'qdrant-vps-wow'
)

$issues = [System.Collections.Generic.List[string]]::new()
$warns  = [System.Collections.Generic.List[string]]::new()

function Add-Issue { param($m) $script:issues.Add($m) }
function Add-Warn  { param($m) $script:warns.Add($m) }

if (-not (Test-Path -LiteralPath $McpJson)) {
    Add-Issue "Brak pliku: $McpJson"
} else {
    $raw = Get-Content -LiteralPath $McpJson -Raw -Encoding UTF8
    try {
        $j = $raw | ConvertFrom-Json
    } catch {
        Add-Issue "mcp.json nie jest poprawnym JSON: $_"
        $j = $null
    }
    if ($j -and $j.mcpServers) {
        $have = @($j.mcpServers.PSObject.Properties | ForEach-Object { $_.Name })
        foreach ($k in $expectedKeys) {
            if ($have -notcontains $k) {
                Add-Issue "mcpServers brak klucza: $k"
            }
        }
    } else {
        if ($j) { Add-Issue 'mcp.json: brak sekcji mcpServers' }
    }
}

# Laravel Boost (PHP + artisan)
$boostPs1 = Join-Path $RepoRoot 'tools\laravel-boost-mcp-runtime\run-laravel-boost-mcp.ps1'
if (-not (Test-Path -LiteralPath $boostPs1)) { Add-Issue "Brak: $boostPs1" }

# Postgres VPS MCP launcher
$pgPs1 = Join-Path $RepoRoot 'tools\postgres-vps-mcp-runtime\run-postgres-mcp-wow.ps1'
if (-not (Test-Path -LiteralPath $pgPs1)) { Add-Issue "Brak: $pgPs1" }

# Qdrant VPS MCP launcher (oficjalny mcp-server-qdrant + uv)
$qdPs1 = Join-Path $RepoRoot 'tools\qdrant-vps-mcp-runtime\run-qdrant-mcp-wow.ps1'
$qdOfficial = Join-Path $RepoRoot 'mcp-servers\qdrant-mcp-official\pyproject.toml'
if (-not (Test-Path -LiteralPath $qdPs1)) { Add-Issue "Brak: $qdPs1" }
if (-not (Test-Path -LiteralPath $qdOfficial)) { Add-Issue "Brak: $qdOfficial" }
$uv = Get-Command uv -ErrorAction SilentlyContinue
if (-not $uv) { Add-Warn 'uv (Astral) nie na PATH — qdrant-vps-wow: winget install Astral.uv, potem uv sync w mcp-servers\qdrant-mcp-official' }

# GitHub MCP (oficjalny binary)
$ghExe = Join-Path $RepoRoot 'tools\github-mcp-runtime\bin\github-mcp-server.exe'
if (-not (Test-Path -LiteralPath $ghExe)) {
    Add-Issue "Brak: $ghExe - uruchom: tools\github-mcp-runtime\install-github-mcp.ps1"
}

# npx (@modelcontextprotocol server-fetch, server-filesystem)
$npx = Get-Command npx -ErrorAction SilentlyContinue
if (-not $npx) { Add-Warn 'npx nie na PATH - fetch/filesystem MCP nie wystartuja' }

# npm runtimes (MCP SDK)
$runtimes = @(
    @{ Name = 'n8n-mcp-runtime';        SdkRel = 'node_modules\@modelcontextprotocol\sdk\package.json' },
    @{ Name = 'telegram-mcp-runtime'; SdkRel = 'node_modules\@modelcontextprotocol\sdk\package.json' },
    @{ Name = 'gcp-gcloud-wow-runtime'; SdkRel = 'node_modules\@modelcontextprotocol\sdk\package.json' }
)
foreach ($r in $runtimes) {
    $sdk = Join-Path $RepoRoot (Join-Path 'tools' (Join-Path $r.Name $r.SdkRel))
    if (-not (Test-Path -LiteralPath $sdk)) {
        Add-Issue "Brak node_modules w tools\$($r.Name) - uruchom: tools\install-cursor-mcp-deps.ps1 lub bootstrap -InstallDeps"
    }
}

# gcloud (opcjonalne — tylko ostrzeżenie)
$gcloud = Get-Command gcloud -ErrorAction SilentlyContinue
if (-not $gcloud) {
    Add-Warn 'gcloud nie na PATH - gcloud-wow MCP moze blad; winget: Google.CloudSDK (patrz gcp-gcloud-wow-runtime)'
}

# mcp-postgres (pin w package.json) — preferowany lokalny modul
$pgMjs = Join-Path $RepoRoot 'node_modules\mcp-postgres\server.mjs'
if (-not (Test-Path -LiteralPath $pgMjs)) {
    Add-Warn 'Brak node_modules/mcp-postgres w korzeniu - npm install; postgres-vps-wow uzyje npx do czasu instalacji'
}

$ok = ($issues.Count -eq 0)
if ($Json) {
    $o = [ordered]@{
        ok     = $ok
        issues = @($issues)
        warns  = @($warns)
    }
    $o | ConvertTo-Json -Depth 4 -Compress
} else {
    Write-Host ""
    Write-Host '  [ RS Gravity | verify-cursor-mcp-wow | 2026-04 plus ]' -ForegroundColor Cyan
    Write-Host "  $McpJson" -ForegroundColor Gray
    Write-Host ""
    foreach ($w in $warns) { Write-Host "  [WARN] $w" -ForegroundColor Yellow }
    foreach ($i in $issues) { Write-Host "  [FAIL] $i" -ForegroundColor Red }
    if ($ok -and $warns.Count -eq 0) {
        Write-Host "  [OK]   MCP WOW stack: config + runtimes" -ForegroundColor Green
    } elseif ($ok) {
        Write-Host "  [OK]   krytyczne pliki (ostrzeżenia powyżej)" -ForegroundColor Green
    } else {
        Write-Host "  [FAIL] popraw i uruchom ponownie" -ForegroundColor Red
    }
    Write-Host ""
}

exit $(if ($ok) { 0 } else { 1 })
