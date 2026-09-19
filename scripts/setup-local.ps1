$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

$projectRoot = Split-Path $PSScriptRoot -Parent
$runtime = Join-Path $projectRoot '.runtime'
$phpDirectory = Join-Path $runtime 'php'
$nodeDirectory = Join-Path $runtime 'node-v22.23.2-win-x64'
$mysqlDirectory = Join-Path $runtime 'mysql-8.4.11-winx64'
$php = Join-Path $phpDirectory 'php.exe'
$npm = Join-Path $nodeDirectory 'npm.cmd'
$mysql = Join-Path $mysqlDirectory 'bin/mysqld.exe'
$mysqlClient = Join-Path $mysqlDirectory 'bin/mysql.exe'
$composer = Join-Path $runtime 'composer.phar'
$frontendReady = Join-Path $runtime 'frontend-dependencies.ready'
$mysqlData = Join-Path $runtime 'mysql-data'
$mysqlRootConfig = Join-Path $runtime 'mysql-root.ini'
$backendEnvironment = Join-Path $projectRoot 'backend/.env'

New-Item -ItemType Directory -Force -Path $runtime | Out-Null

function Write-Utf8File($path, $content) {
    $encoding = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($path, $content, $encoding)
}

function New-RandomHex($byteCount) {
    $bytes = New-Object byte[] $byteCount
    $generator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try { $generator.GetBytes($bytes) } finally { $generator.Dispose() }
    return -join ($bytes | ForEach-Object { $_.ToString('x2') })
}

function Install-Archive($name, $url, $sha256, $archivePath, $destination) {
    if (Test-Path -LiteralPath $destination) { return }
    Write-Host "Baixando $name (isso ocorre apenas no primeiro uso)..."
    Invoke-WebRequest -Uri $url -OutFile $archivePath
    $actualHash = (Get-FileHash -LiteralPath $archivePath -Algorithm SHA256).Hash
    if ($actualHash -ne $sha256) {
        Remove-Item -LiteralPath $archivePath -Force
        throw "O arquivo baixado para $name não passou na verificação de integridade."
    }
    Expand-Archive -LiteralPath $archivePath -DestinationPath (Split-Path $destination -Parent) -Force
    Remove-Item -LiteralPath $archivePath -Force
}

if (!(Test-Path -LiteralPath $php)) {
    $phpArchive = Join-Path $runtime 'php.zip'
    Write-Host 'Baixando PHP (isso ocorre apenas no primeiro uso)...'
    Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/php-8.3.33-nts-Win32-vs16-x64.zip' -OutFile $phpArchive
    $actualHash = (Get-FileHash -LiteralPath $phpArchive -Algorithm SHA256).Hash
    if ($actualHash -ne '534399107056313246F424ADBBB7937337E40FBBF6AA7BC26287BA9CFD2E4A2A') {
        Remove-Item -LiteralPath $phpArchive -Force
        throw 'O arquivo baixado para PHP não passou na verificação de integridade.'
    }
    New-Item -ItemType Directory -Force -Path $phpDirectory | Out-Null
    Expand-Archive -LiteralPath $phpArchive -DestinationPath $phpDirectory -Force
    Remove-Item -LiteralPath $phpArchive -Force
}

Install-Archive 'Node.js' `
    'https://nodejs.org/dist/v22.23.2/node-v22.23.2-win-x64.zip' `
    '1177B4137BA5ADAA56354AE40F1080C7450E8AE09CECB47DA459D1C52AC99F97' `
    (Join-Path $runtime 'node.zip') `
    $nodeDirectory

# Os scripts de instalação do npm chamam `node` pelo PATH.
$env:Path = "$nodeDirectory;$env:Path"

Install-Archive 'MySQL' `
    'https://cdn.mysql.com/Downloads/MySQL-8.4/mysql-8.4.11-winx64.zip' `
    'A492371D687D2BAB088B0062581144A0044B8964BAEFDF4FAA579292B423D25C' `
    (Join-Path $runtime 'mysql.zip') `
    $mysqlDirectory

if (!(Test-Path -LiteralPath $composer)) {
    Write-Host 'Baixando Composer...'
    Invoke-WebRequest -Uri 'https://getcomposer.org/download/2.10.3/composer.phar' -OutFile $composer
    $actualHash = (Get-FileHash -LiteralPath $composer -Algorithm SHA256).Hash
    if ($actualHash -ne '7A2D379D5B8FFDAA028580EF26494C36D2FEEF4B178D3DD1473A4DBC5E17C8D6') {
        Remove-Item -LiteralPath $composer -Force
        throw 'O Composer baixado não passou na verificação de integridade.'
    }
}

$phpConfiguration = @'
extension_dir="ext"
extension=curl
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=mysqli
extension=fileinfo
extension=zip
extension=intl
date.timezone=America/Sao_Paulo
memory_limit=512M
'@
Write-Utf8File (Join-Path $phpDirectory 'php.ini') $phpConfiguration

if (!(Test-Path -LiteralPath (Join-Path $projectRoot 'backend/vendor/autoload.php'))) {
    Write-Host 'Instalando dependências do backend...'
    & $php $composer install --working-dir (Join-Path $projectRoot 'backend') --no-interaction --prefer-dist
    if ($LASTEXITCODE -ne 0) { throw 'Falha ao instalar as dependências do backend.' }
}

if (!(Test-Path -LiteralPath $frontendReady)) {
    Write-Host 'Instalando dependências do frontend...'
    Push-Location (Join-Path $projectRoot 'frontend')
    try { & $npm ci } finally { Pop-Location }
    if ($LASTEXITCODE -ne 0) { throw 'Falha ao instalar as dependências do frontend.' }
    Write-Utf8File $frontendReady 'ok'
}

$newDatabase = !(Test-Path -LiteralPath (Join-Path $mysqlData 'mysql'))
if ($newDatabase) {
    Write-Host 'Preparando o banco local...'
    if (Test-Path -LiteralPath $mysqlData) { Remove-Item -LiteralPath $mysqlData -Recurse -Force }
    New-Item -ItemType Directory -Force -Path $mysqlData | Out-Null
    & $mysql --no-defaults --initialize-insecure "--basedir=$mysqlDirectory" "--datadir=$mysqlData"
    if ($LASTEXITCODE -ne 0) { throw 'Falha ao inicializar o MySQL local.' }
}

$listener = Get-NetTCPConnection -State Listen -LocalPort 3307 -ErrorAction SilentlyContinue | Select-Object -First 1
if (!$listener) {
    $process = Start-Process -FilePath $mysql -ArgumentList @('--no-defaults', "--basedir=`"$mysqlDirectory`"", "--datadir=`"$mysqlData`"", '--port=3307', '--bind-address=127.0.0.1', '--mysqlx=OFF', '--console') -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput "$runtime/mysql.log" -RedirectStandardError "$runtime/mysql-error.log"
    $process.Id | Set-Content "$runtime/mysql.pid"
    for ($attempt = 0; $attempt -lt 180; $attempt++) {
        if ($process.HasExited) { throw 'O MySQL encerrou. Consulte .runtime/mysql-error.log.' }
        if (Get-NetTCPConnection -State Listen -LocalPort 3307 -ErrorAction SilentlyContinue) { break }
        Start-Sleep -Milliseconds 500
    }
    if (!(Get-NetTCPConnection -State Listen -LocalPort 3307 -ErrorAction SilentlyContinue)) { throw 'O MySQL não iniciou na porta 3307.' }
} else {
    $existing = Get-CimInstance Win32_Process -Filter "ProcessId = $($listener.OwningProcess)"
    if ($existing.ExecutablePath -ne $mysql.Replace('/', '\')) { throw 'A porta 3307 está ocupada por outro programa.' }
}

if ($newDatabase) {
    $databasePassword = New-RandomHex 24
    $rootPassword = New-RandomHex 24
    $jwtSecret = New-RandomHex 32
    $bootstrapSql = @"
CREATE DATABASE hortas_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hortas_local'@'localhost' IDENTIFIED BY '$databasePassword';
GRANT ALL PRIVILEGES ON hortas_local.* TO 'hortas_local'@'localhost';
ALTER USER 'root'@'localhost' IDENTIFIED BY '$rootPassword';
"@
    $bootstrapSql | & $mysqlClient --protocol=tcp --host=127.0.0.1 --port=3307 --user=root
    if ($LASTEXITCODE -ne 0) { throw 'Falha ao criar o banco e o usuário local.' }
    Write-Utf8File $mysqlRootConfig "[client]`nuser=root`npassword=$rootPassword`nhost=127.0.0.1`nport=3307`n"
    $environment = @"
APP_ENV=development
APP_DEBUG=true
DB_HOST=127.0.0.1
DB_PORT=3307
DB_NAME=hortas_local
DB_USER=hortas_local
DB_PASS=$databasePassword
DB_CHARSET=utf8mb4
JWT_SECRET=$jwtSecret
API_VERSION=v1
"@
    Write-Utf8File $backendEnvironment $environment
}

foreach ($requiredFile in @($mysqlRootConfig, $backendEnvironment)) {
    if (!(Test-Path -LiteralPath $requiredFile)) {
        throw "A preparação local está incompleta: falta $requiredFile. Apague a pasta .runtime e execute novamente."
    }
}
