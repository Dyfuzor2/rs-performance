# Ensures php.ini exists next to resolved php.exe (WinGet / portable PHP often ships without it).
# Idempotent: creates php.ini from php.ini-production once; enables extensions Pint/Laravel need.
# April 2026+ - run before Boost MCP or Pint when Box reports missing mbstring.
param(
    [switch]$WhatIf,
    [switch]$Quiet
)

$ErrorActionPreference = "Stop"
$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")
$RuntimeDir = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $RuntimeDir "Resolve-GravityPhp.ps1")

$phpExe = Get-GravityPhpExecutable -RuntimeDir $RuntimeDir
if (-not $phpExe) {
    Write-Host "FAIL: PHP not found (GRAVITY_PHP, php.path, PATH, WinGet)." -ForegroundColor Red
    exit 1
}

if (-not $WhatIf) {
    & $phpExe -r "exit(extension_loaded('mbstring') ? 0 : 1);" 2>$null
    if ($LASTEXITCODE -eq 0) {
        if (-not $Quiet) {
            Write-Host "PHP mbstring already loaded; ini bootstrap skipped." -ForegroundColor DarkGray
        }
        exit 0
    }
}

$phpDir = Split-Path -Parent $phpExe
$iniPath = Join-Path $phpDir "php.ini"
$prod = Join-Path $phpDir "php.ini-production"
$devel = Join-Path $phpDir "php.ini-development"

if (-not (Test-Path -LiteralPath $iniPath)) {
    $src = $null
    if (Test-Path -LiteralPath $prod) { $src = $prod }
    elseif (Test-Path -LiteralPath $devel) { $src = $devel }
    if (-not $src) {
        Write-Host "FAIL: No php.ini-production next to $phpExe - cannot bootstrap." -ForegroundColor Red
        exit 1
    }
    if ($WhatIf) {
        Write-Host "WhatIf: would copy $src -> $iniPath"
        exit 0
    }
    Copy-Item -LiteralPath $src -Destination $iniPath -Force
    Write-Host "Created $iniPath from $(Split-Path -Leaf $src)" -ForegroundColor Green
}

$content = Get-Content -LiteralPath $iniPath -Raw -ErrorAction Stop

# extension_dir — WinGet PHP uses "ext" subfolder
$extDir = Join-Path $phpDir "ext"
if (Test-Path -LiteralPath $extDir) {
    $extNorm = ($extDir -replace '\\', '/')
    if ($content -notmatch '(?m)^extension_dir\s*=') {
        $content = "extension_dir = `"$extNorm`"`r`n" + $content
    }
    else {
        $content = $content -replace '(?m)^\s*;?\s*extension_dir\s*=.*$', "extension_dir = `"$extNorm`""
    }
}

$enable = @(
    'mbstring',
    'openssl',
    'curl',
    'fileinfo',
    'intl',
    'pdo_mysql',
    'zip',
    'sodium',
    'exif'
)

foreach ($name in $enable) {
    # Uncomment ;extension=name or ;extension=php_name.dll (capture group keeps the active line)
    $patterns = @(
        "(?m)^\s*;\s*(extension\s*=\s*$name\s*)$",
        "(?m)^\s*;\s*(extension\s*=\s*php_$name\.dll\s*)$"
    )
    foreach ($pat in $patterns) {
        $content = $content -replace $pat, '$1'
    }
}

if (-not $WhatIf) {
    Set-Content -LiteralPath $iniPath -Value $content -Encoding UTF8
}

if (-not $Quiet) {
    Write-Host "PHP ini: $iniPath" -ForegroundColor Cyan
}
if (-not $WhatIf -and -not $Quiet) {
    & $phpExe --ini
    & $phpExe -m 2>&1 | Select-String -Pattern 'mbstring|openssl|curl' | ForEach-Object { $_.Line }
}
