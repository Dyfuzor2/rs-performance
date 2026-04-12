# Installs official github/github-mcp-server Windows amd64 binary into tools/github-mcp-runtime/bin/
# Source: https://github.com/github/github-mcp-server/releases
$ErrorActionPreference = "Stop"
$Version = "v0.32.0"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
$BinDir = Join-Path $Base "bin"
$ZipUrl = "https://github.com/github/github-mcp-server/releases/download/$Version/github-mcp-server_Windows_x86_64.zip"
$ZipPath = Join-Path $Base "github-mcp-server_Windows_x86_64.zip"

New-Item -ItemType Directory -Force -Path $BinDir | Out-Null
Write-Host "Downloading $ZipUrl ..."
Invoke-WebRequest -Uri $ZipUrl -OutFile $ZipPath -UseBasicParsing
Expand-Archive -Path $ZipPath -DestinationPath $BinDir -Force
Remove-Item -Force $ZipPath
$Exe = Get-ChildItem -Path $BinDir -Filter "github-mcp-server.exe" -Recurse | Select-Object -First 1
if (-not $Exe) {
    throw "github-mcp-server.exe not found after extract."
}
Write-Host "OK: $($Exe.FullName)"
Write-Host "Next: ensure G:\gravity\git.md (first line = PAT) or set GITHUB_PERSONAL_ACCESS_TOKEN, then enable MCP server github in Cursor."
