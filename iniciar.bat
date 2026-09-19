@echo off
cd /d "%~dp0"
powershell.exe -noprofile -executionpolicy bypass -file "%~dp0scripts\start-local.ps1"
if errorlevel 1 (
  echo.
  echo ============================================================
  echo nao foi possivel iniciar o projeto. veja o erro acima.
  echo se o windows pediu permissao de rede, permita o acesso e
  echo execute o iniciar.bat novamente.
  echo ============================================================
  echo.
  echo pressione qualquer tecla para fechar.
  pause >nul
  exit /b 1
)

echo.
echo ============================================================
echo projeto iniciado com sucesso
echo.
echo url:   http://localhost:3000
echo login: admin@email.com
echo senha: admin
echo ============================================================
echo.
echo abrindo o sistema no navegador...
start "" "http://localhost:3000"
echo.
echo pode fechar esta janela. o projeto continuara rodando.
echo pressione qualquer tecla para fechar.
pause >nul
