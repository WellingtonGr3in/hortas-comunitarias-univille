@echo off
cd /d "%~dp0"
powershell.exe -noprofile -executionpolicy bypass -file "%~dp0scripts\start-local.ps1"
if errorlevel 1 (
  echo.
  echo nao foi possivel iniciar o projeto. veja o erro acima.
  pause
  exit /b 1
)
