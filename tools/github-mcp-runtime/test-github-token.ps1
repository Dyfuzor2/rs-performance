# Validates GITHUB_PERSONAL_ACCESS_TOKEN or first line of G:\gravity\git.md via GET /user (no token echoed).
$ErrorActionPreference = "Stop"
$token = $env:GITHUB_PERSONAL_ACCESS_TOKEN
if ([string]::IsNullOrWhiteSpace($token)) {
    $GitMd = "G:\gravity\git.md"
    if (Test-Path $GitMd) {
        $token = (Get-Content -LiteralPath $GitMd -Raw).Trim().Split("`n")[0].Trim()
    }
}
if ([string]::IsNullOrWhiteSpace($token)) {
    Write-Host "FAIL: No token (set GITHUB_PERSONAL_ACCESS_TOKEN or git.md line 1)."
    exit 1
}
$headers = @{ Authorization = "Bearer $token"; "User-Agent" = "RS-Gravity-GitHub-MCP-Health/2026" }
try {
    $u = Invoke-RestMethod -Uri "https://api.github.com/user" -Headers $headers -Method Get
    Write-Host "OK: authenticated as $($u.login) (id $($u.id))"
    exit 0
} catch {
    Write-Host "FAIL: GitHub API error - $($_.Exception.Message)"
    exit 1
}
