$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$runtime = Join-Path $projectRoot '.runtime'
# Encerrar apenas os executáveis portáteis deste projeto, verificando seus caminhos.
foreach ($entry in @(@{Port=3000;Exe="$runtime/node-v22.23.2-win-x64/node.exe"}, @{Port=8181;Exe="$runtime/php/php.exe"})) {
    $listener = Get-NetTCPConnection -State Listen -LocalPort $entry.Port -ErrorAction SilentlyContinue | Select-Object -First 1
    if (!$listener) { continue }
    $process = Get-CimInstance Win32_Process -Filter "ProcessId = $($listener.OwningProcess)"
    if ($process.ExecutablePath -ne $entry.Exe.Replace('/', '\')) { throw "Porta $($entry.Port) pertence a outro programa. Nada foi encerrado nessa porta." }
    Stop-Process -Id $process.ProcessId
}
$listener = Get-NetTCPConnection -State Listen -LocalPort 3307 -ErrorAction SilentlyContinue | Select-Object -First 1
if ($listener) {
    $process = Get-CimInstance Win32_Process -Filter "ProcessId = $($listener.OwningProcess)"
    if ($process.ExecutablePath -ne "$runtime/mysql-8.4.11-winx64/bin/mysqld.exe".Replace('/', '\')) { throw 'A porta 3307 pertence a outro MySQL.' }
    & "$runtime/mysql-8.4.11-winx64/bin/mysqladmin.exe" "--defaults-file=$runtime/mysql-root.ini" shutdown
    if ($LASTEXITCODE -ne 0) { throw 'Não foi possível encerrar o MySQL normalmente.' }
}
Write-Host 'Serviços locais encerrados. Os dados foram preservados.'
