#Requires -Version 5.1
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    Write-Error 'npm not on PATH'
    exit 1
}
npm install
Write-Host 'gcp-gcloud-wow-runtime: npm install OK.'
