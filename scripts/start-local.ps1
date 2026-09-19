$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
& (Join-Path $PSScriptRoot 'setup-local.ps1')
$runtime = Join-Path $projectRoot '.runtime'
$php = Join-Path $runtime 'php/php.exe'
$node = Join-Path $runtime 'node-v22.23.2-win-x64/node.exe'
$mysql = Join-Path $runtime 'mysql-8.4.11-winx64/bin/mysqld.exe'
foreach ($file in @($php, $node, $mysql, "$projectRoot/backend/.env", "$projectRoot/backend/vendor/autoload.php", "$projectRoot/frontend/node_modules/@vue/cli-service/bin/vue-cli-service.js")) {
    if (!(Test-Path -LiteralPath $file)) { throw "Arquivo ausente: $file. Consulte RODAR-LOCAL.md." }
}

function Start-LocalService($name, $exe, $arguments, $directory, $port) {
    $listener = Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($listener) {
        $existing = Get-CimInstance Win32_Process -Filter "ProcessId = $($listener.OwningProcess)"
        if ($existing.ExecutablePath -ne $exe.Replace('/', '\')) { throw "Porta $port ocupada por outro programa." }
        Write-Host "$name já está rodando."
        return
    }
    $process = Start-Process -FilePath $exe -ArgumentList $arguments -WorkingDirectory $directory -WindowStyle Hidden -PassThru -RedirectStandardOutput "$runtime/$name.log" -RedirectStandardError "$runtime/$name-error.log"
    $process.Id | Set-Content "$runtime/$name.pid"
    for ($attempt = 0; $attempt -lt 90; $attempt++) {
        if ($process.HasExited) { throw "$name encerrou. Consulte .runtime/$name-error.log" }
        if (Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue) { return }
        Start-Sleep -Milliseconds 500
    }
    throw "$name não iniciou na porta $port. Consulte .runtime/$name-error.log"
}

Start-LocalService 'mysql' $mysql @('--no-defaults', "--basedir=`"$runtime/mysql-8.4.11-winx64`"", "--datadir=`"$runtime/mysql-data`"", '--port=3307', '--bind-address=127.0.0.1', '--mysqlx=OFF', '--console') $projectRoot 3307
& $php "$projectRoot/backend/run-migrations.php"
if ($LASTEXITCODE -ne 0) { throw 'Falha nas migrations. O backend não foi iniciado.' }
Start-LocalService 'backend' $php @('-S', '127.0.0.1:8181', '-t', 'public', 'public/index.php') "$projectRoot/backend" 8181
Start-LocalService 'frontend' $node @('node_modules/@vue/cli-service/bin/vue-cli-service.js', 'serve', '--host', '127.0.0.1') "$projectRoot/frontend" 3000
Write-Host 'Projeto disponível em http://localhost:3000'
Write-Host 'Demonstração: admin@email.com / admin'
