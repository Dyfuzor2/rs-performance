#Requires -Version 5.1
<#
.SYNOPSIS
  POST smoke for critical n8n webhooks (manifest-driven, April 2026+ operator gate).
#>
param(
    [string] $Manifest = (Join-Path $PSScriptRoot 'critical-webhooks.manifest.json')
)
$ErrorActionPreference = 'Stop'

if (-not (Test-Path -LiteralPath $Manifest)) {
    Write-Error "Manifest not found: $Manifest"
    exit 1
}

$doc = Get-Content -LiteralPath $Manifest -Raw -Encoding UTF8 | ConvertFrom-Json
$fail = 0
foreach ($w in $doc.webhooks) {
    $method = ($w.method | ForEach-Object { $_.ToString().ToUpperInvariant() })
    if ($method -ne 'POST') {
        Write-Warning "Skip non-POST: $($w.name)"
        continue
    }
    $uri = $w.url
    $body = $w.body | ConvertTo-Json -Compress -Depth 10
    try {
        $r = Invoke-WebRequest -Uri $uri -Method Post -Body $body -ContentType 'application/json; charset=utf-8' -UseBasicParsing -TimeoutSec 60
        $code = [int]$r.StatusCode
    } catch {
        $resp = $_.Exception.Response
        if ($resp -and $resp.StatusCode) {
            $code = [int]$resp.StatusCode.value__
        } else {
            $code = -1
        }
    }
    $min = 200
    $max = 299
    if ($null -ne $w.expect_min_status) { $min = [int]$w.expect_min_status }
    if ($null -ne $w.expect_max_status) { $max = [int]$w.expect_max_status }
    $ok = ($code -ge $min -and $code -le $max)
    if (-not $ok) { $fail++ }
    $flag = if ($ok) { 'OK' } else { 'FAIL' }
    Write-Host "[$flag] $($w.name) HTTP $code ($uri)"
}

if ($fail -gt 0) {
    exit 1
}
exit 0
