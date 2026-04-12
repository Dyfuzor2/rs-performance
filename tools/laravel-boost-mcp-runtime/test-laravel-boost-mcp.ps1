# Exit 0 if PHP + boost:mcp work; 1 otherwise (used by wow setup).
$ErrorActionPreference = "Stop"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path
& (Join-Path $Base "install-laravel-boost-mcp.ps1")
exit $LASTEXITCODE
