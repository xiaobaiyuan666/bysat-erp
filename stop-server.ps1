$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$pidFile = Join-Path $projectRoot 'storage\php-server.pid'

if (-not (Test-Path $pidFile)) {
    Write-Output 'No PID file found.'
    exit 0
}

$serverPid = Get-Content $pidFile -ErrorAction SilentlyContinue

if ($serverPid -and (Get-Process -Id $serverPid -ErrorAction SilentlyContinue)) {
    Stop-Process -Id $serverPid -Force
    Write-Output "Stopped PHP server PID $serverPid."
} else {
    Write-Output 'Server process was not running.'
}

Remove-Item $pidFile -ErrorAction SilentlyContinue
