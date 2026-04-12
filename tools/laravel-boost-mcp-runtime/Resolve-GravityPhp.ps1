# Resolves php.exe for G:\gravity Laravel MCP. Order: GRAVITY_PHP, PHP_BINARY, php.path, PATH, common installs.
function Get-GravityPhpExecutable {
    param(
        [string]$RuntimeDir
    )
    $candidates = @()
    if ($env:GRAVITY_PHP -and (Test-Path -LiteralPath $env:GRAVITY_PHP)) {
        return (Resolve-Path -LiteralPath $env:GRAVITY_PHP).Path
    }
    if ($env:PHP_BINARY -and (Test-Path -LiteralPath $env:PHP_BINARY)) {
        return (Resolve-Path -LiteralPath $env:PHP_BINARY).Path
    }
    $pathFile = Join-Path $RuntimeDir "php.path"
    if (Test-Path -LiteralPath $pathFile) {
        $line = (Get-Content -LiteralPath $pathFile -ErrorAction SilentlyContinue | Where-Object { $_.Trim() -ne "" -and $_ -notmatch "^\s*#" } | Select-Object -First 1)
        if ($line -and (Test-Path -LiteralPath $line.Trim())) {
            return (Resolve-Path -LiteralPath $line.Trim()).Path
        }
    }
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd -and $cmd.Source) {
        return $cmd.Source
    }
    $common = @(
        "C:\php\php.exe",
        "C:\tools\php\php.exe",
        "$env:ProgramFiles\PHP\php.exe",
        "$env:ProgramFiles\php\php.exe",
        "$env:LocalAppData\Programs\Php\php.exe"
    )
    foreach ($p in $common) {
        if (Test-Path -LiteralPath $p) {
            return (Resolve-Path -LiteralPath $p).Path
        }
    }
    $scoop = Join-Path $env:USERPROFILE "scoop\apps\php\current\php.exe"
    if (Test-Path -LiteralPath $scoop) {
        return (Resolve-Path -LiteralPath $scoop).Path
    }
    $herd = Join-Path $env:LocalAppData "Herd\bin\php.bat"
    if (Test-Path -LiteralPath $herd) {
        return (Resolve-Path -LiteralPath $herd).Path
    }
    return $null
}
