#Requires -Version 5.1
<#
.SYNOPSIS
  Install Google Cloud SDK on Windows (winget), April 2026+ operator path.

.DESCRIPTION
  Idempotent: skips if gcloud already on PATH.
  After install: new terminal, then `gcloud init` or `gcloud auth application-default login`
  for local Vertex / ADC experiments. Production workloads remain on VPS per RELAY.
#>
$ErrorActionPreference = 'Stop'

$hasGcloud = $null -ne (Get-Command gcloud.cmd -ErrorAction SilentlyContinue) -or
    ($null -ne (Get-Command gcloud -ErrorAction SilentlyContinue))

if ($hasGcloud) {
    Write-Host 'gcloud already on PATH:'
    # gcloud prints "updates available" on stderr; with Stop, native stderr becomes a terminating error.
    $prevEap = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        & gcloud.cmd version 2>&1 | ForEach-Object { Write-Host $_ }
    } finally {
        $ErrorActionPreference = $prevEap
    }
    exit 0
}

$winget = Get-Command winget -ErrorAction SilentlyContinue
if (-not $winget) {
    Write-Error 'winget not found. Install Google Cloud SDK manually: https://cloud.google.com/sdk/docs/install'
    exit 1
}

Write-Host 'Installing Google.CloudSDK via winget...'
winget install --id Google.CloudSDK --accept-package-agreements --accept-source-agreements
Write-Host 'Done. Open a NEW PowerShell window, then run: gcloud version'
