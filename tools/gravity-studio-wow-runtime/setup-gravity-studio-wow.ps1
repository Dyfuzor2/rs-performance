# Gravity Studio WOW (April 2026+) - one entry: GitHub MCP + Laravel Boost + optional Qdrant local.
# Opens a single hub deck; sub-runtimes stay headless (no browser spam).
$ErrorActionPreference = "Stop"
# Fresh PATH for child processes (PHP from winget may not be in legacy session PATH).
$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")
$Tools = "G:\gravity\tools"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path

function Invoke-Ps {
    param([string]$ScriptPath)
    $p = Start-Process -FilePath "powershell.exe" -ArgumentList @(
        "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", $ScriptPath
    ) -Wait -PassThru -NoNewWindow
    return $p.ExitCode
}

$results = [ordered]@{
    githubMcpBinary = $false
    githubToken     = $false
    laravelBoost    = $false
    qdrantLocal     = $false
    dockerRunning   = $false
}

Write-Host ""
Write-Host "  === Gravity Studio WOW ===" -ForegroundColor Cyan
Write-Host "  (GitHub MCP + Laravel Boost + Qdrant local)" -ForegroundColor DarkGray
Write-Host ""

# --- Docker probe (Qdrant) — stderr from docker must not stop the script ---
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

# --- Laravel Boost ---
$lb = Invoke-Ps (Join-Path $Tools "laravel-boost-mcp-runtime\install-laravel-boost-mcp.ps1")
$results.laravelBoost = ($lb -eq 0)

# --- Qdrant local (Docker) ---
if ($results.dockerRunning) {
    $qs = Invoke-Ps (Join-Path $Tools "qdrant-local-runtime\start-qdrant.ps1")
    if ($qs -eq 0) {
        Start-Sleep -Seconds 2
        $qt = Invoke-Ps (Join-Path $Tools "qdrant-local-runtime\test-qdrant-local.ps1")
        $results.qdrantLocal = ($qt -eq 0)
    }
}

# --- Persist status for hub page (no secrets) ---
$statusPath = Join-Path $Base "last-run-status.json"
$payload = [PSCustomObject]@{
    generatedAtUtc = (Get-Date).ToUniversalTime().ToString("o")
    githubMcpBinary = $results.githubMcpBinary
    githubTokenOk   = $results.githubToken
    laravelBoostOk  = $results.laravelBoost
    dockerRunning   = $results.dockerRunning
    qdrantLocalOk   = $results.qdrantLocal
}
$payload | ConvertTo-Json -Depth 3 | Set-Content -LiteralPath $statusPath -Encoding UTF8

function Html-Bool {
    param([bool]$Ok, [string]$Label)
    $cls = if ($Ok) { "ok" } else { "bad" }
    $txt = if ($Ok) { "OK" } else { "OFF" }
    return "<li><span class=""pill $cls"">$txt</span> $Label</li>"
}

$statusHtml = @"
<ul class="status">
$(Html-Bool $results.githubMcpBinary "GitHub MCP binary")
$(Html-Bool $results.githubToken "GitHub API token")
$(Html-Bool $results.laravelBoost "Laravel Boost (boost:mcp)")
$(Html-Bool $results.dockerRunning "Docker engine")
$(Html-Bool $results.qdrantLocal "Qdrant local (127.0.0.1:6333)")
</ul>
<p class="ts">Last run (UTC): $($payload.generatedAtUtc)</p>
"@

$Wow = Join-Path $Base "wow.html"
$tpl = Join-Path $Base "wow.template.html"
if (Test-Path $tpl) {
    $raw = Get-Content -LiteralPath $tpl -Raw -Encoding UTF8
    $out = $raw -replace "<!--STUDIO_STATUS-->", $statusHtml
    Set-Content -LiteralPath $Wow -Value $out -Encoding UTF8 -NoNewline
}

# --- Single hub deck ---
if (Test-Path $Wow) {
    $uri = ([System.Uri]$Wow).AbsoluteUri
    Write-Host ""
    Write-Host "  Opening Gravity Studio hub: $uri" -ForegroundColor DarkGray
    Start-Process $uri
}

Write-Host ""
Write-Host "  SUMMARY" -ForegroundColor Cyan
Write-Host "  GitHub MCP exe:     $(if ($results.githubMcpBinary) { 'OK' } else { 'FAIL' })" -ForegroundColor $(if ($results.githubMcpBinary) { 'Green' } else { 'Red' })
Write-Host "  GitHub token (API): $(if ($results.githubToken) { 'OK' } else { 'SKIP/FAIL' })" -ForegroundColor $(if ($results.githubToken) { 'Green' } else { 'Yellow' })
Write-Host "  Laravel Boost:      $(if ($results.laravelBoost) { 'OK' } else { 'FAIL' })" -ForegroundColor $(if ($results.laravelBoost) { 'Green' } else { 'Red' })
Write-Host "  Docker engine:      $(if ($results.dockerRunning) { 'OK' } else { 'OFF' })" -ForegroundColor $(if ($results.dockerRunning) { 'Green' } else { 'Yellow' })
Write-Host "  Qdrant local:       $(if ($results.qdrantLocal) { 'OK' } else { if (-not $results.dockerRunning) { 'Start Docker Desktop' } else { 'FAIL' } })" -ForegroundColor $(if ($results.qdrantLocal) { 'Green' } else { 'Yellow' })
Write-Host ""
Write-Host "  Next: Cursor - Reload MCP. Enable github, laravel-boost, qdrant-*-local as needed." -ForegroundColor Green
Write-Host ""
