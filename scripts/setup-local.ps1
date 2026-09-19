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
$backendReady = Join-Path $runtime 'backend-dependencies.ready'
$frontendReady = Join-Path $runtime 'frontend-dependencies.ready'
$mysqlData = Join-Path $runtime 'mysql-data'
$mysqlRootConfig = Join-Path $runtime 'mysql-root.ini'
$backendEnvironment = Join-Path $projectRoot 'backend/.env'

New-Item -ItemType Directory -Force -Path $runtime | Out-Null

if ($env:OS -ne 'Windows_NT' -or ![Environment]::Is64BitOperatingSystem) {
    throw 'Este instalador requer Windows 64 bits.'
}

function Write-Utf8File($path, $content) {
    $encoding = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($path, $content, $encoding)
}

function Get-Sha256($path) {
    $stream = [System.IO.File]::OpenRead($path)
    $algorithm = [System.Security.Cryptography.SHA256]::Create()
    try {
        return ([System.BitConverter]::ToString($algorithm.ComputeHash($stream))).Replace('-', '')
    } finally {
        $algorithm.Dispose()
        $stream.Dispose()
    }
}

function New-RandomHex($byteCount) {
    $bytes = New-Object byte[] $byteCount
    $generator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try { $generator.GetBytes($bytes) } finally { $generator.Dispose() }
    return -join ($bytes | ForEach-Object { $_.ToString('x2') })
}

function Get-VerifiedFile($name, $url, $sha256, $destination) {
    for ($attempt = 1; $attempt -le 3; $attempt++) {
        try {
            [System.IO.File]::Delete($destination)
            Write-Host "Baixando $name (tentativa $attempt de 3)..."
            Invoke-WebRequest -Uri $url -OutFile $destination -UseBasicParsing
            $actualHash = Get-Sha256 $destination
            if ($actualHash -ne $sha256) { throw 'verificação de integridade recusada' }
            return
        } catch {
            [System.IO.File]::Delete($destination)
            if ($attempt -eq 3) { throw "Não foi possível baixar $name. Verifique a internet, proxy ou antivírus e tente novamente." }
            Start-Sleep -Seconds 2
        }
    }
}

function Install-Archive($name, $url, $sha256, $archivePath, $destination, $requiredFile) {
    if (Test-Path -LiteralPath $requiredFile) { return }
    if (Test-Path -LiteralPath $destination) { [System.IO.Directory]::Delete($destination, $true) }
    Get-VerifiedFile $name $url $sha256 $archivePath
    Expand-Archive -LiteralPath $archivePath -DestinationPath (Split-Path $destination -Parent) -Force
    [System.IO.File]::Delete($archivePath)
    if (!(Test-Path -LiteralPath $requiredFile)) { throw "A extração de $name não foi concluída." }
}

if (!(Test-Path -LiteralPath $php)) {
    $phpArchive = Join-Path $runtime 'php.zip'
    if (Test-Path -LiteralPath $phpDirectory) { [System.IO.Directory]::Delete($phpDirectory, $true) }
    Get-VerifiedFile 'PHP' 'https://windows.php.net/downloads/releases/php-8.3.33-nts-Win32-vs16-x64.zip' '534399107056313246F424ADBBB7937337E40FBBF6AA7BC26287BA9CFD2E4A2A' $phpArchive
    New-Item -ItemType Directory -Force -Path $phpDirectory | Out-Null
    Expand-Archive -LiteralPath $phpArchive -DestinationPath $phpDirectory -Force
    [System.IO.File]::Delete($phpArchive)
    if (!(Test-Path -LiteralPath $php)) { throw 'A extração do PHP não foi concluída.' }
}

Install-Archive 'Node.js' `
    'https://nodejs.org/dist/v22.23.2/node-v22.23.2-win-x64.zip' `
    '1177B4137BA5ADAA56354AE40F1080C7450E8AE09CECB47DA459D1C52AC99F97' `
    (Join-Path $runtime 'node.zip') `
    $nodeDirectory `
    (Join-Path $nodeDirectory 'node.exe')

# Os scripts de instalação do npm chamam `node` pelo PATH.
$env:Path = "$nodeDirectory;$env:Path"

Install-Archive 'MySQL' `
    'https://cdn.mysql.com/Downloads/MySQL-8.4/mysql-8.4.11-winx64.zip' `
    'A492371D687D2BAB088B0062581144A0044B8964BAEFDF4FAA579292B423D25C' `
    (Join-Path $runtime 'mysql.zip') `
    $mysqlDirectory `
    (Join-Path $mysqlDirectory 'bin/mysqld.exe')

if (!(Test-Path -LiteralPath $composer)) {
    Get-VerifiedFile 'Composer' 'https://getcomposer.org/download/2.10.3/composer.phar' '7A2D379D5B8FFDAA028580EF26494C36D2FEEF4B178D3DD1473A4DBC5E17C8D6' $composer
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

$composerLock = Join-Path $projectRoot 'backend/composer.lock'
$composerFingerprint = Get-Sha256 $composerLock
$savedComposerFingerprint = if (Test-Path -LiteralPath $backendReady) { (Get-Content -LiteralPath $backendReady -Raw).Trim() } else { '' }
if (!(Test-Path -LiteralPath (Join-Path $projectRoot 'backend/vendor/autoload.php')) -or $savedComposerFingerprint -ne $composerFingerprint) {
    Write-Host 'Instalando dependências do backend...'
    & $php $composer install --working-dir (Join-Path $projectRoot 'backend') --no-interaction --prefer-dist --no-progress
    if ($LASTEXITCODE -ne 0) { throw 'Falha ao instalar as dependências do backend.' }
    Write-Utf8File $backendReady $composerFingerprint
}

$packageLock = Join-Path $projectRoot 'frontend/package-lock.json'
$packageFingerprint = Get-Sha256 $packageLock
$savedPackageFingerprint = if (Test-Path -LiteralPath $frontendReady) { (Get-Content -LiteralPath $frontendReady -Raw).Trim() } else { '' }
if (!(Test-Path -LiteralPath (Join-Path $projectRoot 'frontend/node_modules/@vue/cli-service/bin/vue-cli-service.js')) -or $savedPackageFingerprint -ne $packageFingerprint) {
    Write-Host 'Instalando dependências do frontend...'
    # O binário do Cypress só é necessário para testes e seu download costuma
    # ser bloqueado por proxy ou antivírus. O pacote JS continua instalado.
    $env:CYPRESS_INSTALL_BINARY = '0'
    Push-Location (Join-Path $projectRoot 'frontend')
    try { & $npm ci --no-audit --no-fund --loglevel=error } finally { Pop-Location }
    if ($LASTEXITCODE -ne 0) { throw 'Falha ao instalar as dependências do frontend.' }
    Write-Utf8File $frontendReady $packageFingerprint
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
