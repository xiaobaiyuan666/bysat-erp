[CmdletBinding()]
param(
    [string]$PackageZip,
    [string]$PhpPath = "php",
    [string]$MysqlHost = "127.0.0.1",
    [int]$MysqlPort = 3306,
    [string]$MysqlUser = "root",
    [string]$MysqlPassword = "root",
    [string]$MysqlPrefix = "fa_",
    [ValidateSet("clean", "demo")]
    [string]$InstallMode = "clean",
    [string]$AdminUsername = "admin",
    [string]$AdminPassword = "Admin@123",
    [int]$Port = 8094,
    [bool]$SkipUpdateSslVerify = $true,
    [string]$OutputRoot = "",
    [switch]$KeepWorkspace
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$script:AjaxHeaders = @{
    "X-Requested-With" = "XMLHttpRequest"
    "Accept"           = "application/json, text/javascript, */*; q=0.01"
}

function Resolve-RepoRoot {
    return (Resolve-Path (Join-Path $PSScriptRoot "..")).ProviderPath
}

function Get-Timestamp {
    return Get-Date -Format "yyyyMMdd-HHmmss"
}

function Write-Step {
    param(
        [string]$Message
    )

    Write-Host ("[{0}] {1}" -f (Get-Date -Format "HH:mm:ss"), $Message)
}

function Ensure-Directory {
    param(
        [string]$Path
    )

    if (-not (Test-Path -LiteralPath $Path)) {
        New-Item -ItemType Directory -Path $Path -Force | Out-Null
    }
}

function Resolve-LatestPackageZip {
    param(
        [string]$SearchRoot
    )

    $latest = Get-ChildItem -Path $SearchRoot -Filter "bysat-erp-fastadmin-baota-*.zip" |
        Sort-Object LastWriteTime -Descending |
        Select-Object -First 1

    if (-not $latest) {
        throw "No full deployment package was found under $SearchRoot"
    }

    return $latest.FullName
}

function Read-DatabaseDefaultsFromEnv {
    param(
        [string]$EnvPath
    )

    if (-not (Test-Path -LiteralPath $EnvPath)) {
        return @{}
    }

    $values = @{}
    $section = ""
    foreach ($line in Get-Content -Path $EnvPath) {
        $trimmed = $line.Trim()
        if (-not $trimmed -or $trimmed.StartsWith("#") -or $trimmed.StartsWith(";")) {
            continue
        }

        if ($trimmed -match '^\[(.+)\]$') {
            $section = $matches[1].Trim().ToLowerInvariant()
            continue
        }

        if ($section -ne "database") {
            continue
        }

        if ($trimmed -match '^([A-Za-z0-9_]+)\s*=\s*(.*)$') {
            $key = $matches[1].Trim().ToLowerInvariant()
            $value = $matches[2].Trim()
            $values[$key] = $value
        }
    }

    return $values
}

function Assert-PortAvailable {
    param(
        [int]$Port
    )

    $listener = $null
    try {
        $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, $Port)
        $listener.Start()
    } catch {
        throw "Port $Port is already in use or unavailable."
    } finally {
        if ($listener) {
            $listener.Stop()
        }
    }
}

function Wait-HttpReady {
    param(
        [string]$Url,
        [int]$TimeoutSeconds = 30
    )

    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    while ((Get-Date) -lt $deadline) {
        try {
            $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 5
            if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 500) {
                return $response
            }
        } catch {
        }
        Start-Sleep -Seconds 1
    }

    throw "Timed out while waiting for $Url"
}

function Save-Content {
    param(
        [string]$Path,
        [string]$Content
    )

    Ensure-Directory -Path (Split-Path -Parent $Path)
    [System.IO.File]::WriteAllText($Path, $Content, [System.Text.UTF8Encoding]::new($false))
}

function ConvertTo-PrettyJson {
    param(
        [Parameter(ValueFromPipeline = $true)]
        $InputObject
    )

    process {
        return $InputObject | ConvertTo-Json -Depth 20
    }
}

function Invoke-JsonRequest {
    param(
        [string]$Uri,
        [ValidateSet("GET", "POST")]
        [string]$Method = "GET",
        [Microsoft.PowerShell.Commands.WebRequestSession]$Session,
        [hashtable]$Body = @{},
        [hashtable]$Headers = @{}
    )

    $requestHeaders = @{}
    foreach ($entry in $script:AjaxHeaders.GetEnumerator()) {
        $requestHeaders[$entry.Key] = $entry.Value
    }
    foreach ($entry in $Headers.GetEnumerator()) {
        $requestHeaders[$entry.Key] = $entry.Value
    }

    $invokeArgs = @{
        Uri             = $Uri
        Method          = $Method
        UseBasicParsing = $true
        Headers         = $requestHeaders
        TimeoutSec      = 120
    }

    if ($Session) {
        $invokeArgs.WebSession = $Session
    }

    if ($Method -eq "POST") {
        $invokeArgs.Body = $Body
    }

    $response = Invoke-WebRequest @invokeArgs
    $json = $response.Content | ConvertFrom-Json
    return [pscustomobject]@{
        Response = $response
        Json     = $json
    }
}

function Resolve-AppRoot {
    param(
        [string]$ExtractRoot
    )

    $candidate = Get-ChildItem -Path $ExtractRoot -Recurse -Filter "install.php" -File |
        Where-Object { $_.FullName -like "*\public\install.php" } |
        Sort-Object FullName |
        Select-Object -First 1

    if (-not $candidate) {
        throw "Unable to locate public/install.php under $ExtractRoot"
    }

    return (Split-Path -Parent (Split-Path -Parent $candidate.FullName))
}

function Get-HtmlToken {
    param(
        [string]$Html
    )

    $match = [regex]::Match($Html, 'name="__token__"\s+value="([^"]+)"')
    if (-not $match.Success) {
        throw "Unable to find __token__ in login page."
    }
    return $match.Groups[1].Value
}

function Assert-ContentContains {
    param(
        [string]$Content,
        [string[]]$Patterns,
        [string]$Label
    )

    foreach ($pattern in $Patterns) {
        if ($Content -notmatch $pattern) {
            throw "$Label is missing expected marker: $pattern"
        }
    }
}

function Invoke-Check {
    param(
        [string]$Name,
        [scriptblock]$Action,
        [System.Collections.Generic.List[object]]$Results
    )

    $startedAt = Get-Date
    Write-Step $Name
    try {
        $details = & $Action
        $durationMs = [math]::Round(((Get-Date) - $startedAt).TotalMilliseconds, 0)
        $Results.Add([pscustomobject]@{
            name       = $Name
            status     = "passed"
            durationMs = $durationMs
            details    = $details
        }) | Out-Null
        return $details
    } catch {
        $durationMs = [math]::Round(((Get-Date) - $startedAt).TotalMilliseconds, 0)
        $Results.Add([pscustomobject]@{
            name       = $Name
            status     = "failed"
            durationMs = $durationMs
            details    = @{ error = $_.Exception.Message }
        }) | Out-Null
        throw
    }
}

$repoRoot = Resolve-RepoRoot
if (-not $OutputRoot) {
    $OutputRoot = Join-Path $repoRoot "output\automation"
}

$envDefaults = Read-DatabaseDefaultsFromEnv -EnvPath (Join-Path $repoRoot "fastadmin\.env")
if (-not $PSBoundParameters.ContainsKey("MysqlHost") -and $envDefaults.ContainsKey("hostname")) {
    $MysqlHost = [string]$envDefaults["hostname"]
}
if (-not $PSBoundParameters.ContainsKey("MysqlPort") -and $envDefaults.ContainsKey("hostport")) {
    $MysqlPort = [int]$envDefaults["hostport"]
}
if (-not $PSBoundParameters.ContainsKey("MysqlUser") -and $envDefaults.ContainsKey("username")) {
    $MysqlUser = [string]$envDefaults["username"]
}
if (-not $PSBoundParameters.ContainsKey("MysqlPassword") -and $envDefaults.ContainsKey("password")) {
    $MysqlPassword = [string]$envDefaults["password"]
}
if (-not $PSBoundParameters.ContainsKey("MysqlPrefix") -and $envDefaults.ContainsKey("prefix")) {
    $MysqlPrefix = [string]$envDefaults["prefix"]
}

Ensure-Directory -Path $OutputRoot

if (-not $PackageZip) {
    $PackageZip = Resolve-LatestPackageZip -SearchRoot (Join-Path $repoRoot "output")
} else {
    $PackageZip = (Resolve-Path $PackageZip).ProviderPath
}

Assert-PortAvailable -Port $Port

$timestamp = Get-Timestamp
$runRoot = Join-Path $OutputRoot ("minimal-automation-" + $timestamp)
$extractRoot = Join-Path $runRoot "package"
$httpRoot = Join-Path $runRoot "http"
$reportPath = Join-Path $runRoot "report.json"
$stdoutLog = Join-Path $runRoot "php-server.stdout.log"
$stderrLog = Join-Path $runRoot "php-server.stderr.log"
Ensure-Directory -Path $runRoot
Ensure-Directory -Path $extractRoot
Ensure-Directory -Path $httpRoot

$databaseName = ("bysat_smoke_{0}" -f ((Get-Date -Format "yyyyMMdd_HHmmss_fff")))
$baseUrl = "http://127.0.0.1:$Port"
$serverProcess = $null
$results = [System.Collections.Generic.List[object]]::new()
$adminEntry = $null
$appRoot = $null
$publicRoot = $null
$status = "failed"
$failureMessage = $null

try {
    Invoke-Check -Name "extract-package" -Results $results -Action {
        Expand-Archive -LiteralPath $PackageZip -DestinationPath $extractRoot -Force
        $script:appRoot = Resolve-AppRoot -ExtractRoot $extractRoot
        $script:publicRoot = Join-Path $script:appRoot "public"
        return @{
            packageZip = $PackageZip
            appRoot    = $script:appRoot
        }
    } | Out-Null

    Invoke-Check -Name "start-php-server" -Results $results -Action {
        $serverArgumentLine = "-S 127.0.0.1:$Port -t `"$($script:publicRoot)`""
        $script:serverProcess = Start-Process -FilePath $PhpPath `
            -ArgumentList $serverArgumentLine `
            -WorkingDirectory $script:appRoot `
            -RedirectStandardOutput $stdoutLog `
            -RedirectStandardError $stderrLog `
            -PassThru

        $response = Wait-HttpReady -Url "$baseUrl/install.php" -TimeoutSeconds 30
        return @{
            pid        = $script:serverProcess.Id
            installUrl = "$baseUrl/install.php"
            statusCode = $response.StatusCode
        }
    } | Out-Null

    Invoke-Check -Name "install-system" -Results $results -Action {
        $installUrl = "$baseUrl/install.php"
        $installPage = Invoke-WebRequest -Uri $installUrl -UseBasicParsing -TimeoutSec 30
        Save-Content -Path (Join-Path $httpRoot "install.get.html") -Content $installPage.Content
        Assert-ContentContains -Content $installPage.Content -Patterns @("name=""mysqlHostname""", "name=""installMode""") -Label "install page"

        $payload = @{
            mysqlHostname             = $MysqlHost
            mysqlHostport             = [string]$MysqlPort
            mysqlDatabase             = $databaseName
            mysqlUsername             = $MysqlUser
            mysqlPassword             = $MysqlPassword
            mysqlPrefix               = $MysqlPrefix
            adminUsername             = $AdminUsername
            adminPassword             = $AdminPassword
            adminPasswordConfirmation = $AdminPassword
            adminEmail                = "admin@example.com"
            siteName                  = "BySAT ERP Smoke"
            installMode               = $InstallMode
        }

        $installResult = Invoke-JsonRequest -Uri $installUrl -Method POST -Body $payload
        Save-Content -Path (Join-Path $httpRoot "install.post.json") -Content $installResult.Response.Content
        if ($installResult.Json.code -ne 1) {
            throw ("install failed: " + $installResult.Json.msg)
        }

        $script:adminEntry = [string]$installResult.Json.data.adminName
        if (-not $script:adminEntry) {
            throw "install succeeded but admin entry is empty"
        }

        $adminUrl = "$baseUrl/$script:adminEntry"
        $adminLanding = Invoke-WebRequest -Uri $adminUrl -UseBasicParsing -TimeoutSec 30
        Save-Content -Path (Join-Path $httpRoot "admin.entry.html") -Content $adminLanding.Content

        return @{
            adminEntry = $script:adminEntry
            database   = $databaseName
            adminUrl   = $adminUrl
        }
    } | Out-Null

    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

    Invoke-Check -Name "login" -Results $results -Action {
        $loginUrl = "$baseUrl/$script:adminEntry/index/login"
        $loginPage = Invoke-WebRequest -Uri $loginUrl -UseBasicParsing -WebSession $session -TimeoutSec 30
        Save-Content -Path (Join-Path $httpRoot "login.get.html") -Content $loginPage.Content
        if ($loginPage.Content -match 'name="captcha"') {
            throw "login page requires captcha, current smoke script does not solve captchas."
        }

        $token = Get-HtmlToken -Html $loginPage.Content
        $loginResult = Invoke-JsonRequest -Uri $loginUrl -Method POST -Session $session -Body @{
            username  = $AdminUsername
            password  = $AdminPassword
            __token__ = $token
        }
        Save-Content -Path (Join-Path $httpRoot "login.post.json") -Content $loginResult.Response.Content
        if ($loginResult.Json.code -ne 1) {
            throw ("login failed: " + $loginResult.Json.msg)
        }

        return @{
            url      = $loginResult.Json.url
            username = $loginResult.Json.data.username
        }
    } | Out-Null

    $pageChecks = @(
        @{
            Name     = "dashboard-page"
            Path     = "dashboard/index?addtabs=1"
            FileName = "dashboard.html"
            Patterns = @("erp-home")
        },
        @{
            Name     = "ai-page"
            Path     = "ai/conversation/index?addtabs=1"
            FileName = "ai.html"
            Patterns = @("ai-workbench", "ai-composer", "ai-chat-body", "answer-meta")
        },
        @{
            Name     = "project-ops-page"
            Path     = "app/workbench/index?addtabs=1"
            FileName = "project-ops.html"
            Patterns = @("wb-page", "wb-layout", "wb-tip-list")
        },
        @{
            Name     = "finance-page"
            Path     = "finance/workbench/index?addtabs=1"
            FileName = "finance.html"
            Patterns = @("wb-page", "smart-bookkeeping", "smart-text", "result-panel")
        },
        @{
            Name     = "upgrade-page"
            Path     = "general/upgrade/index?addtabs=1"
            FileName = "upgrade.html"
            Patterns = @("erp-upgrade-page", "check-update", "upgrade-config-form")
        }
    )

    foreach ($pageCheck in $pageChecks) {
        $pageName = $pageCheck.Name
        Invoke-Check -Name $pageName -Results $results -Action {
            $pageUrl = "$baseUrl/$script:adminEntry/$($pageCheck.Path)"
            $response = Invoke-WebRequest -Uri $pageUrl -UseBasicParsing -WebSession $session -TimeoutSec 60
            Save-Content -Path (Join-Path $httpRoot $pageCheck.FileName) -Content $response.Content
            Assert-ContentContains -Content $response.Content -Patterns $pageCheck.Patterns -Label $pageName
            return @{
                url        = $pageUrl
                statusCode = $response.StatusCode
            }
        } | Out-Null
    }

    Invoke-Check -Name "update-check" -Results $results -Action {
        $saveConfigResult = Invoke-JsonRequest -Uri "$baseUrl/$script:adminEntry/general/upgrade/saveconfig" -Method POST -Session $session -Body @{
            source_mode           = "branch"
            owner                 = "xiaobaiyuan666"
            repo                  = "bysat-erp"
            branch                = "master"
            release_tag           = "latest"
            release_asset_pattern = "bysat-erp-fastadmin-baota-*.zip"
            package_subdir        = "fastadmin"
            github_token          = ""
            skip_ssl_verify       = if ($SkipUpdateSslVerify) { 1 } else { 0 }
        }
        Save-Content -Path (Join-Path $httpRoot "upgrade.saveconfig.json") -Content $saveConfigResult.Response.Content
        if ($saveConfigResult.Json.code -ne 1) {
            throw ("save update config failed: " + $saveConfigResult.Json.msg)
        }

        $checkResult = Invoke-JsonRequest -Uri "$baseUrl/$script:adminEntry/general/upgrade/checkupdate" -Method POST -Session $session
        Save-Content -Path (Join-Path $httpRoot "upgrade.checkupdate.json") -Content $checkResult.Response.Content
        if ($checkResult.Json.code -ne 1) {
            throw ("update check failed: " + $checkResult.Json.msg)
        }

        if (-not $checkResult.Json.data.remote) {
            throw "update check returned no remote payload"
        }

        return @{
            message         = $checkResult.Json.msg
            updateAvailable = [bool]$checkResult.Json.data.update_available
            remoteRef       = [string]$checkResult.Json.data.remote.ref_short
        }
    } | Out-Null

    $status = "passed"
} catch {
    $failureMessage = $_.Exception.Message
    Write-Error $failureMessage
} finally {
    if ($serverProcess -and -not $serverProcess.HasExited) {
        Stop-Process -Id $serverProcess.Id -Force
    }

    $report = [pscustomobject]@{
        status        = $status
        failure       = $failureMessage
        startedAt     = $timestamp
        packageZip    = $PackageZip
        workRoot      = $runRoot
        appRoot       = $appRoot
        publicRoot    = $publicRoot
        outputRoot    = $OutputRoot
        baseUrl       = $baseUrl
        databaseName  = $databaseName
        adminEntry    = $adminEntry
        phpStdoutLog  = $stdoutLog
        phpStderrLog  = $stderrLog
        checks        = $results
    }
    Save-Content -Path $reportPath -Content ($report | ConvertTo-PrettyJson)

    if ($status -eq "passed") {
        Write-Host ""
        Write-Host "Minimal automation passed."
        Write-Host ("Report: {0}" -f $reportPath)
        Write-Host ("Artifacts: {0}" -f $runRoot)
    } else {
        Write-Host ""
        Write-Host "Minimal automation failed."
        Write-Host ("Report: {0}" -f $reportPath)
        Write-Host ("Artifacts: {0}" -f $runRoot)
        if (-not $KeepWorkspace) {
            Write-Host "Workspace was preserved for troubleshooting."
        }
        exit 1
    }
}
