$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$phpPath = 'C:\tools\php85\php.exe'
$hostAddress = '0.0.0.0'
$port = 8090
$pidFile = Join-Path $projectRoot 'storage\php-server.pid'

if (-not (Test-Path $phpPath)) {
    throw "PHP not found at $phpPath"
}

if (Test-Path $pidFile) {
    $existingPid = Get-Content $pidFile -ErrorAction SilentlyContinue
    if ($existingPid -and (Get-Process -Id $existingPid -ErrorAction SilentlyContinue)) {
        Write-Output "PHP server is already running on port $port with PID $existingPid."
        exit 0
    }

    Remove-Item $pidFile -ErrorAction SilentlyContinue
}

$arguments = @(
    '-NoExit',
    '-ExecutionPolicy', 'Bypass',
    '-Command',
    "Set-Location '$projectRoot'; & '$phpPath' -S $hostAddress`:$port -t public"
)

$process = Start-Process -FilePath 'powershell.exe' -ArgumentList $arguments -PassThru
$process.Id | Set-Content -Path $pidFile -Encoding ascii

Write-Output "Started PHP server in a dedicated window."
Write-Output "PID: $($process.Id)"
Write-Output "URL: http://127.0.0.1:$port/index.php?page=dashboard"
