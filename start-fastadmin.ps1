$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$public = Join-Path $root 'fastadmin\public'
$runtime = Join-Path $root 'fastadmin\runtime'
$pidFile = Join-Path $runtime 'php-server.pid'
$portFile = Join-Path $runtime 'php-server.port'
$urlFile = Join-Path $runtime 'php-server.url'
$adminUrlFile = Join-Path $runtime 'php-server-admin.url'
$php = 'C:\tools\php84\php.exe'

function Get-FreePort {
    param(
        [int]$StartPort = 8091,
        [int]$EndPort = 8110
    )

    for ($port = $StartPort; $port -le $EndPort; $port++) {
        $inUse = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
        if (-not $inUse) {
            return $port
        }
    }

    throw "No free port found between $StartPort and $EndPort"
}

function Get-RunningProcess {
    if (!(Test-Path $pidFile)) {
        return $null
    }

    $existingPid = Get-Content $pidFile -ErrorAction SilentlyContinue
    if (!$existingPid) {
        return $null
    }

    return Get-Process -Id $existingPid -ErrorAction SilentlyContinue
}

function Get-AdminEntryFile {
    $candidate = Get-ChildItem -Path $public -Filter '*.php' -File |
        Where-Object { $_.Name -notin @('index.php', 'router.php', 'install.php') } |
        Sort-Object Name |
        Select-Object -First 1

    return $candidate
}

if (!(Test-Path $runtime)) {
    New-Item -Path $runtime -ItemType Directory -Force | Out-Null
}

$proc = Get-RunningProcess
if ($proc) {
    $existingPort = if (Test-Path $portFile) { Get-Content $portFile -ErrorAction SilentlyContinue } else { '' }
    $existingUrl = if (Test-Path $urlFile) { Get-Content $urlFile -ErrorAction SilentlyContinue } else { '' }
    $existingAdminUrl = if (Test-Path $adminUrlFile) { Get-Content $adminUrlFile -ErrorAction SilentlyContinue } else { '' }

    if (!$existingUrl -and $existingPort) {
        $existingUrl = "http://127.0.0.1:$existingPort/"
    }

    if ($existingAdminUrl) {
        Write-Output "FastAdmin already running: $existingAdminUrl (PID $($proc.Id))"
    } else {
        Write-Output "FastAdmin already running: $existingUrl (PID $($proc.Id))"
    }
    exit 0
}

$port = Get-FreePort
$process = Start-Process -FilePath $php -ArgumentList @('-S', "127.0.0.1:$port", 'router.php') -WorkingDirectory $public -PassThru
$url = "http://127.0.0.1:$port/"
$adminEntry = Get-AdminEntryFile
$adminUrl = if ($adminEntry) { "http://127.0.0.1:$port/$($adminEntry.Name)" } else { $url }

$process.Id | Set-Content $pidFile
$port | Set-Content $portFile
$url | Set-Content $urlFile
$adminUrl | Set-Content $adminUrlFile

Write-Output "FastAdmin started: $adminUrl (PID $($process.Id))"
