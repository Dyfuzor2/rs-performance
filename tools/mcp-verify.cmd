@echo off
rem RS Gravity — April 2026+ (jedno klikniecie / PATH bez prawdziwego PHP Composera)
setlocal
set "PS1=%~dp0verify-cursor-mcp-wow.ps1"
if not exist "%PS1%" (
  echo [FAIL] Brak: %PS1%
  exit /b 1
)
powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "%PS1%" %*
exit /b %ERRORLEVEL%
