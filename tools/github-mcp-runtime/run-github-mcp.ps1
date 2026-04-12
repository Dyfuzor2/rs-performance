# MCP stdio launcher: token from env GITHUB_PERSONAL_ACCESS_TOKEN, else first line of G:\gravity\git.md
$ErrorActionPreference = "Stop"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
$Exe = Join-Path $Base "bin\github-mcp-server.exe"
if (-not (Test-Path $Exe)) {
    Write-Error "Missing $Exe — run install-github-mcp.ps1 first."
    exit 1
}

$token = $env:GITHUB_PERSONAL_ACCESS_TOKEN
if ([string]::IsNullOrWhiteSpace($token)) {
    $GitMd = "G:\gravity\git.md"
    if (Test-Path $GitMd) {
        $token = (Get-Content -LiteralPath $GitMd -Raw).Trim().Split("`n")[0].Trim()
    }
}
if ([string]::IsNullOrWhiteSpace($token)) {
    Write-Error "Set GITHUB_PERSONAL_ACCESS_TOKEN or add PAT as first line of G:\gravity\git.md"
    exit 1
}

$env:GITHUB_PERSONAL_ACCESS_TOKEN = $token
if ([string]::IsNullOrWhiteSpace($env:GITHUB_TOOLSETS)) {
    $env:GITHUB_TOOLSETS = "repos,users,context"
}

& $Exe @("stdio", "--read-only")
