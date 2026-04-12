# Gravity Studio WOW (April 2026+) - one entry: PHP WOW gate + GitHub MCP + Laravel Boost + Qdrant + RAG readyz.
# Opens a single hub deck; sub-runtimes stay headless (no browser spam).
param(
    [switch]$NoBrowser
)
$ErrorActionPreference = "Stop"
# Fresh PATH for child processes (PHP from winget may not be in legacy session PATH).
$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = (Resolve-Path (Join-Path $Base "..\..")).Path
$Tools = Join-Path $RepoRoot "tools"

function Invoke-Ps {
    param([string]$ScriptPath)
    $p = Start-Process -FilePath "powershell.exe" -ArgumentList @(
        "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", $ScriptPath
    ) -Wait -PassThru -NoNewWindow
    return $p.ExitCode
}

$results = [ordered]@{
    gravityPhpWow   = $false
    gravityPhpSkip  = $false
    githubMcpBinary = $false
    githubToken     = $false
    laravelBoost    = $false
    qdrantLocal     = $false
    ragReadyzOk     = $false
    dockerRunning   = $false
}

Write-Host ""
Write-Host "  === Gravity Studio WOW ===" -ForegroundColor Cyan
Write-Host "  (PHP gate + GitHub + Boost + Qdrant + RAG readyz)" -ForegroundColor DarkGray
Write-Host ""

# --- PHP WOW gate (WinGet php.ini + mbstring) before any PHP child ---
if ($env:GRAVITY_SKIP_PHP_INI) {
    $results.gravityPhpSkip = $true
    $results.gravityPhpWow = $true
    Write-Host "[studio] PHP WOW gate: skipped (GRAVITY_SKIP_PHP_INI)" -ForegroundColor Yellow
} else {
    Write-Host "[studio] PHP WOW gate: Ensure-GravityPhpIni ..." -ForegroundColor Cyan
    $pe = Invoke-Ps (Join-Path $Tools "laravel-boost-mcp-runtime\Ensure-GravityPhpIni.ps1")
    $results.gravityPhpWow = ($pe -eq 0)
    if (-not $results.gravityPhpWow) {
        Write-Host "[studio] PHP gate FAILED - fix WinGet PHP or set GRAVITY_PHP (see laravel-boost-mcp-runtime/README)" -ForegroundColor Red
    }
}

# --- Docker probe (Qdrant) - stderr from docker must not stop the script ---
$dk = Get-Command docker -ErrorAction SilentlyContinue
if ($dk) {
    $prevEa = $ErrorActionPreference
    $ErrorActionPreference = "SilentlyContinue"
    $null = & docker info 2>&1
    if ($LASTEXITCODE -eq 0) {
        $results.dockerRunning = $true
    }
    $ErrorActionPreference = $prevEa
}

# --- GitHub MCP: install binary if missing ---
$ghBin = Join-Path $Tools "github-mcp-runtime\bin\github-mcp-server.exe"
if (-not (Test-Path $ghBin)) {
    Write-Host "[studio] Installing GitHub MCP binary ..." -ForegroundColor Cyan
    $c = Invoke-Ps (Join-Path $Tools "github-mcp-runtime\install-github-mcp.ps1")
    if ($c -ne 0) { Write-Host "[studio] GitHub install exit $c" -ForegroundColor Yellow }
}
$results.githubMcpBinary = (Test-Path $ghBin)

if ($results.githubMcpBinary) {
    $tok = Invoke-Ps (Join-Path $Tools "github-mcp-runtime\test-github-token.ps1")
    $results.githubToken = ($tok -eq 0)
}

# --- Laravel Boost (install script also re-runs Ensure; harmless if gate already green) ---
$lb = Invoke-Ps (Join-Path $Tools "laravel-boost-mcp-runtime\install-laravel-boost-mcp.ps1")
$results.laravelBoost = ($lb -eq 0)

# --- Qdrant local (Docker) ---
if ($results.dockerRunning) {
    $qs = Invoke-Ps (Join-Path $Tools "qdrant-local-runtime\start-qdrant.ps1")
    if ($qs -eq 0) {
        Start-Sleep -Seconds 2
        $qt = Invoke-Ps (Join-Path $Tools "qdrant-local-runtime\test-qdrant-local.ps1")
        $results.qdrantLocal = ($qt -eq 0)
        if ($results.qdrantLocal) {
            Write-Host "[studio] RAG plane: verify-rag-ready (readyz + collections) ..." -ForegroundColor Cyan
            $vr = Invoke-Ps (Join-Path $Tools "qdrant-local-runtime\verify-rag-ready.ps1")
            $results.ragReadyzOk = ($vr -eq 0)
        }
    }
}

# --- Persist status for hub page (no secrets) ---
$statusPath = Join-Path $Base "last-run-status.json"
$payload = [PSCustomObject]@{
    generatedAtUtc = (Get-Date).ToUniversalTime().ToString("o")
    gravityPhpWowOk = $results.gravityPhpWow
    gravityPhpGateSkipped = $results.gravityPhpSkip
    githubMcpBinary = $results.githubMcpBinary
    githubTokenOk   = $results.githubToken
    laravelBoostOk  = $results.laravelBoost
    dockerRunning   = $results.dockerRunning
    qdrantLocalOk   = $results.qdrantLocal
    ragReadyzOk     = $results.ragReadyzOk
}
$payload | ConvertTo-Json -Depth 3 | Set-Content -LiteralPath $statusPath -Encoding UTF8

function Html-Bool {
    param([bool]$Ok, [string]$Label)
    $cls = if ($Ok) { "ok" } else { "bad" }
    $txt = if ($Ok) { "OK" } else { "OFF" }
    return "<li><span class=""pill $cls"">$txt</span> $Label</li>"
}

function Html-Warn {
    param([string]$Label)
    return "<li><span class=""pill warn"">SKIP</span> $Label</li>"
}

$phpLine = if ($results.gravityPhpSkip) {
    Html-Warn "PHP WOW gate (GRAVITY_SKIP_PHP_INI)"
} else {
    Html-Bool $results.gravityPhpWow "PHP WOW gate (mbstring / WinGet php.ini)"
}

$ragLine = if (-not $results.dockerRunning) {
    Html-Bool $false "RAG readyz /collections (start Docker)"
} elseif (-not $results.qdrantLocal) {
    Html-Bool $false "RAG readyz (Qdrant not up)"
} else {
    Html-Bool $results.ragReadyzOk "RAG readyz + collections API"
}

$statusHtml = @"
<ul class="status">
$phpLine
$(Html-Bool $results.githubMcpBinary "GitHub MCP binary")
$(Html-Bool $results.githubToken "GitHub API token")
$(Html-Bool $results.laravelBoost "Laravel Boost (boost:mcp)")
$(Html-Bool $results.dockerRunning "Docker engine")
$(Html-Bool $results.qdrantLocal "Qdrant local (127.0.0.1:6333)")
$ragLine
</ul>
<p class="ts">Last run (UTC): $($payload.generatedAtUtc)</p>
"@

$setupPs1 = Join-Path $Base "setup-gravity-studio-wow.ps1"
$servePs1 = Join-Path $Base "serve-wow-hub.ps1"
$launchPs1 = Join-Path $Base "launch-gravity-studio-wow.ps1"
$mcpJson = Join-Path $RepoRoot ".mcp.json"
$copyPayload = [ordered]@{
    studioCmd       = "powershell -NoProfile -ExecutionPolicy Bypass -File `"$setupPs1`" -NoBrowser"
    serveCmd        = "powershell -NoProfile -ExecutionPolicy Bypass -File `"$servePs1`" -Port 18765 -Open"
    launchFullCmd   = "powershell -NoProfile -ExecutionPolicy Bypass -File `"$launchPs1`""
    mcpBlock = @(
        'github',
        'laravel-boost',
        'qdrant-rs-knowledge-local',
        'qdrant-rs-dynamic-local',
        'qdrant-rs-answer-routing-local'
    ) -join [Environment]::NewLine
    reloadChecklist = @(
        'Cursor: Settings > MCP - wlacz serwery, potem Reload.',
        'Minimum: github, laravel-boost.',
        'Lokalny RAG (Docker Qdrant): qdrant-rs-knowledge-local, qdrant-rs-dynamic-local, qdrant-rs-answer-routing-local.',
        "Zrodlo nazw serwerow: $mcpJson"
    ) -join [Environment]::NewLine
}
$jsonRaw = $copyPayload | ConvertTo-Json -Compress -Depth 5
$copyScriptTag = '<script type="application/json" id="gravity-studio-copy">' + $jsonRaw + '</script>'

$Wow = Join-Path $Base "wow.html"
$tpl = Join-Path $Base "wow.template.html"
if (Test-Path $tpl) {
    $raw = Get-Content -LiteralPath $tpl -Raw -Encoding UTF8
    $out = $raw -replace "<!--STUDIO_STATUS-->", $statusHtml
    $out = $out -replace "<!--GRAVITY_STUDIO_COPY_JSON-->", $copyScriptTag
    Set-Content -LiteralPath $Wow -Value $out -Encoding UTF8 -NoNewline
}

# --- Single hub deck ---
if (Test-Path $Wow) {
    $uri = ([System.Uri]$Wow).AbsoluteUri
    Write-Host ""
    if ($NoBrowser) {
        Write-Host "  Hub ready (no browser): $uri" -ForegroundColor DarkGray
    } else {
        Write-Host "  Opening Gravity Studio hub: $uri" -ForegroundColor DarkGray
        Start-Process $uri
    }
}

Write-Host ""
Write-Host "  SUMMARY" -ForegroundColor Cyan
$phpSummary = if ($results.gravityPhpSkip) { 'SKIP (env)' } elseif ($results.gravityPhpWow) { 'OK' } else { 'FAIL' }
$phpColor = if ($results.gravityPhpSkip) { 'Yellow' } elseif ($results.gravityPhpWow) { 'Green' } else { 'Red' }
Write-Host "  PHP WOW gate:       $phpSummary" -ForegroundColor $phpColor
Write-Host "  GitHub MCP exe:     $(if ($results.githubMcpBinary) { 'OK' } else { 'FAIL' })" -ForegroundColor $(if ($results.githubMcpBinary) { 'Green' } else { 'Red' })
Write-Host "  GitHub token (API): $(if ($results.githubToken) { 'OK' } else { 'SKIP/FAIL' })" -ForegroundColor $(if ($results.githubToken) { 'Green' } else { 'Yellow' })
Write-Host "  Laravel Boost:      $(if ($results.laravelBoost) { 'OK' } else { 'FAIL' })" -ForegroundColor $(if ($results.laravelBoost) { 'Green' } else { 'Red' })
Write-Host "  Docker engine:      $(if ($results.dockerRunning) { 'OK' } else { 'OFF' })" -ForegroundColor $(if ($results.dockerRunning) { 'Green' } else { 'Yellow' })
Write-Host "  Qdrant local:       $(if ($results.qdrantLocal) { 'OK' } else { if (-not $results.dockerRunning) { 'Start Docker Desktop' } else { 'FAIL' } })" -ForegroundColor $(if ($results.qdrantLocal) { 'Green' } else { 'Yellow' })
$ragS = if (-not $results.dockerRunning) { 'N/A (Docker)' } elseif (-not $results.qdrantLocal) { 'N/A (Qdrant)' } elseif ($results.ragReadyzOk) { 'OK' } else { 'FAIL' }
$ragC = if ($results.ragReadyzOk) { 'Green' } elseif ($ragS -like 'N/A*') { 'DarkGray' } else { 'Red' }
Write-Host "  RAG readyz:         $ragS" -ForegroundColor $ragC
Write-Host ""
Write-Host "  Local hub (Clipboard unlock): serve-wow-hub.ps1 -Open -> http://127.0.0.1:18765/wow.html" -ForegroundColor DarkGray
Write-Host "  One-shot: launch-gravity-studio-wow.ps1 (setup -NoBrowser + serve -Open)" -ForegroundColor DarkGray
Write-Host "  Next: Cursor - Reload MCP. Enable github, laravel-boost, qdrant-rs-*-local as needed." -ForegroundColor Green
Write-Host ""
exit 0
