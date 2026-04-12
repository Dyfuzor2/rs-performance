# One-shot "wow" setup: install binary (if needed), verify PAT, open local status page.
$ErrorActionPreference = "Stop"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
$Exe = Join-Path $Base "bin\github-mcp-server.exe"

function Invoke-SetupScript {
    param([string]$Path)
    $p = Start-Process -FilePath "powershell.exe" -ArgumentList @(
        "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", $Path
    ) -Wait -PassThru -NoNewWindow
    return $p.ExitCode
}

if (-not (Test-Path $Exe)) {
    Write-Host ""
    Write-Host "  === GitHub MCP — installing official binary (v0.32.0) ===" -ForegroundColor Cyan
    Write-Host ""
    $code = Invoke-SetupScript (Join-Path $Base "install-github-mcp.ps1")
    if ($code -ne 0) {
        Write-Host "Install failed with exit $code" -ForegroundColor Red
        exit $code
    }
} else {
    Write-Host "[github-mcp] Binary already present: $Exe"
}

Write-Host ""
Write-Host "  === Verifying GitHub token (API /user) ===" -ForegroundColor Cyan
Write-Host ""
$tokCode = Invoke-SetupScript (Join-Path $Base "test-github-token.ps1")
$tokenOk = ($tokCode -eq 0)

$Wow = Join-Path $Base "wow.html"
if (Test-Path $Wow) {
    $uri = ([System.Uri]$Wow).AbsoluteUri
    Write-Host ""
    Write-Host "  Opening status deck: $uri" -ForegroundColor DarkGray
    Start-Process $uri
}

Write-Host ""
if ($tokenOk) {
    Write-Host "  DONE — Reload MCP in Cursor (github server) and use agent tools to explore public repos." -ForegroundColor Green
} else {
    Write-Host "  Token check failed — fix git.md or GITHUB_PERSONAL_ACCESS_TOKEN, then re-run this script." -ForegroundColor Yellow
}
Write-Host ""
