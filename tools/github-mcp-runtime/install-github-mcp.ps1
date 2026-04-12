# Installs official github/github-mcp-server Windows amd64 binary into tools/github-mcp-runtime/bin/
# Verifies SHA256 of the release ZIP against github-mcp-server_0.32.0_checksums.txt (April 2026 supply-chain hygiene).
$ErrorActionPreference = "Stop"
$Version = "v0.32.0"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
$BinDir = Join-Path $Base "bin"
$ZipName = "github-mcp-server_Windows_x86_64.zip"
$ZipUrl = "https://github.com/github/github-mcp-server/releases/download/$Version/$ZipName"
$ChecksumsUrl = "https://github.com/github/github-mcp-server/releases/download/$Version/github-mcp-server_0.32.0_checksums.txt"
$ZipPath = Join-Path $Base $ZipName
$ChecksumsPath = Join-Path $Base "github-mcp-server_0.32.0_checksums.txt"

# Embedded expected SHA256 for Windows x86_64 ZIP (must match checksums file for this version)
$ExpectedZipSha256 = "63c1622f66702481122e6cf517701719be4cfc135af2a71486337709d388a2ca"

New-Item -ItemType Directory -Force -Path $BinDir | Out-Null

Write-Host "[github-mcp] Downloading checksums ..."
Invoke-WebRequest -Uri $ChecksumsUrl -OutFile $ChecksumsPath -UseBasicParsing
$line = Get-Content -LiteralPath $ChecksumsPath | Where-Object { $_ -match [regex]::Escape($ZipName) } | Select-Object -First 1
if (-not $line) {
    throw "Checksum line for $ZipName not found in checksums file."
}
$fileHashFromUpstream = ($line -split '\s+', 2)[0].Trim().ToLowerInvariant()
if ($fileHashFromUpstream -ne $ExpectedZipSha256) {
    throw "Checksum file mismatch for $ZipName (embedded pin outdated or upstream changed). Expected $ExpectedZipSha256, file has $fileHashFromUpstream"
}

Write-Host "[github-mcp] Downloading $ZipUrl ..."
Invoke-WebRequest -Uri $ZipUrl -OutFile $ZipPath -UseBasicParsing

$actual = (Get-FileHash -LiteralPath $ZipPath -Algorithm SHA256).Hash.ToLowerInvariant()
if ($actual -ne $ExpectedZipSha256) {
    Remove-Item -Force $ZipPath -ErrorAction SilentlyContinue
    throw "SHA256 mismatch for downloaded ZIP. Got $actual, expected $ExpectedZipSha256"
}

Expand-Archive -Path $ZipPath -DestinationPath $BinDir -Force
Remove-Item -Force $ZipPath
Remove-Item -Force $ChecksumsPath -ErrorAction SilentlyContinue

$Exe = Get-ChildItem -Path $BinDir -Filter "github-mcp-server.exe" -Recurse | Select-Object -First 1
if (-not $Exe) {
    throw "github-mcp-server.exe not found after extract."
}
Write-Host "[github-mcp] OK: $($Exe.FullName)"
Write-Host "[github-mcp] Next: run setup-github-mcp-wow.ps1 or set token + reload MCP in Cursor."
