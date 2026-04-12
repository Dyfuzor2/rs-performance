# One-shot: start Qdrant, verify /readyz, open operator deck.
$ErrorActionPreference = "Stop"
$Base = Split-Path -Parent $MyInvocation.MyCommand.Path

$p = Start-Process -FilePath "powershell.exe" -ArgumentList @(
    "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", (Join-Path $Base "start-qdrant.ps1")
) -Wait -PassThru -NoNewWindow
if ($p.ExitCode -ne 0) {
    Write-Host "start-qdrant.ps1 failed." -ForegroundColor Red
    exit $p.ExitCode
}

$ok = $false
for ($i = 0; $i -lt 12; $i++) {
    Start-Sleep -Seconds 3
    $t = Start-Process -FilePath "powershell.exe" -ArgumentList @(
        "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", (Join-Path $Base "test-qdrant-local.ps1")
    ) -Wait -PassThru -NoNewWindow
    if ($t.ExitCode -eq 0) {
        $ok = $true
        break
    }
    Write-Host "[qdrant-local] Waiting for Qdrant... ($($i + 1)/12)" -ForegroundColor DarkGray
}

$Wow = Join-Path $Base "wow.html"
if (Test-Path $Wow) {
    $uri = ([System.Uri]$Wow).AbsoluteUri
    Write-Host ""
    Write-Host "  Opening Qdrant local deck: $uri" -ForegroundColor DarkGray
    Start-Process $uri
}

Write-Host ""
if ($ok) {
    Write-Host "  DONE - Enable MCP servers qdrant-*-local in Cursor and reload MCP." -ForegroundColor Green
} else {
    Write-Host "  Qdrant did not become ready in time. Check: docker logs gravity-qdrant-local" -ForegroundColor Yellow
}
Write-Host ""
