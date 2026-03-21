$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$runtime = Join-Path $root 'fastadmin\runtime'
$pidFile = Join-Path $runtime 'php-server.pid'
$portFile = Join-Path $runtime 'php-server.port'
$urlFile = Join-Path $runtime 'php-server.url'
$adminUrlFile = Join-Path $runtime 'php-server-admin.url'

if (!(Test-Path $pidFile)) {
    Write-Output 'No FastAdmin PID file found.'
    exit 0
}

$serverPid = Get-Content $pidFile -ErrorAction SilentlyContinue
$port = if (Test-Path $portFile) { Get-Content $portFile -ErrorAction SilentlyContinue } else { '' }

if ($serverPid) {
    $proc = Get-Process -Id $serverPid -ErrorAction SilentlyContinue
    if ($proc) {
        Stop-Process -Id $serverPid -Force
        if ($port) {
            Write-Output "Stopped FastAdmin on port $port (PID $serverPid)"
        } else {
            Write-Output "Stopped FastAdmin PID $serverPid"
        }
    } else {
        Write-Output "FastAdmin PID $serverPid not running"
    }
}

Remove-Item $pidFile -ErrorAction SilentlyContinue
Remove-Item $portFile -ErrorAction SilentlyContinue
Remove-Item $urlFile -ErrorAction SilentlyContinue
Remove-Item $adminUrlFile -ErrorAction SilentlyContinue
