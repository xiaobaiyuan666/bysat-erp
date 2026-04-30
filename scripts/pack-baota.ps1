[CmdletBinding()]
param(
    [string]$OutputRoot = '',
    [string]$SourceDatabase = 'fastadmin_erp',
    [string]$DbHost = '127.0.0.1',
    [int]$DbPort = 3307,
    [string]$DbUser = 'root',
    [string]$DbPassword = 'root',
    [string]$MysqlBinDir = 'C:\tools\mysql\mysql\current\bin',
    [ValidateSet('full', 'patch')]
    [string]$PackageMode = 'full',
    [string]$BaseRef = 'HEAD~1',
    [switch]$KeepStaging
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Write-Step {
    param([string]$Message)
    Write-Host "[pack] $Message" -ForegroundColor Cyan
}

function Assert-CommandFile {
    param([string]$Path, [string]$Label)
    if (-not (Test-Path -LiteralPath $Path)) {
        throw "$Label not found: $Path"
    }
}

function Invoke-MySqlScript {
    param(
        [string]$MysqlExe,
        [string]$DbHostValue,
        [int]$Port,
        [string]$User,
        [string]$Password,
        [string]$Sql,
        [string]$Database = ''
    )

    $arguments = @(
        "--host=$DbHostValue",
        "--port=$Port",
        "--user=$User",
        "--password=$Password",
        '--default-character-set=utf8mb4'
    )

    if ($Database -ne '') {
        $arguments += "--database=$Database"
    }

    $Sql | & $MysqlExe @arguments
    if ($LASTEXITCODE -ne 0) {
        throw "MySQL script failed for database [$Database]"
    }
}

function Invoke-MySqlFile {
    param(
        [string]$PhpExe,
        [string]$PhpTool,
        [string]$DbHostValue,
        [int]$Port,
        [string]$User,
        [string]$Password,
        [string]$FilePath,
        [string]$Database
    )

    if (-not (Test-Path -LiteralPath $FilePath)) {
        throw "SQL file not found: $FilePath"
    }

    & $PhpExe $PhpTool import-file "--host=$DbHostValue" "--port=$Port" "--user=$User" "--password=$Password" "--database=$Database" "--file=$FilePath"
    if ($LASTEXITCODE -ne 0) {
        throw "UTF-8 SQL import failed for database [$Database]"
    }
}

function Export-MySqlDatabase {
    param(
        [string]$PhpExe,
        [string]$PhpTool,
        [string]$DbHostValue,
        [int]$Port,
        [string]$User,
        [string]$Password,
        [string]$Database,
        [string]$TargetFile
    )

    $null = New-Item -ItemType Directory -Force -Path (Split-Path -Path $TargetFile -Parent)

    & $PhpExe $PhpTool dump "--host=$DbHostValue" "--port=$Port" "--user=$User" "--password=$Password" "--database=$Database" "--file=$TargetFile"
    if ($LASTEXITCODE -ne 0) {
        throw "UTF-8 database dump failed for [$Database]"
    }
}

function Invoke-RobocopyMirror {
    param(
        [string]$Source,
        [string]$Destination
    )

    $null = New-Item -ItemType Directory -Force -Path $Destination
    $logFile = Join-Path $Destination 'robocopy.log'
    $arguments = @(
        $Source,
        $Destination,
        '/E',
        '/R:1',
        '/W:1',
        '/NFL',
        '/NDL',
        '/NJH',
        '/NJS',
        '/NP',
        "/LOG:$logFile",
        '/XD', 'node_modules', 'runtime', 'data\updater',
        '/XF', '.env', 'application\admin\command\Install\install.lock'
    )

    & robocopy @arguments | Out-Null
    if ($LASTEXITCODE -ge 8) {
        $logContent = if (Test-Path -LiteralPath $logFile) { Get-Content -Path $logFile -Raw } else { '' }
        throw "robocopy failed with exit code $LASTEXITCODE`n$logContent"
    }

    if (Test-Path -LiteralPath $logFile) {
        Remove-Item -LiteralPath $logFile -Force
    }
}

function Remove-TreeSafe {
    param([string]$Path)

    for ($attempt = 1; $attempt -le 3; $attempt++) {
        try {
            Remove-Item -LiteralPath $Path -Recurse -Force -ErrorAction Stop
            return
        } catch {
            if ($attempt -ge 3) {
                Write-Warning "Failed to remove path [$Path]: $($_.Exception.Message)"
                return
            }

            Start-Sleep -Milliseconds 500
        }
    }
}

function New-TempDatabaseName {
    param([string]$Prefix)
    return '{0}_{1}' -f $Prefix, ([DateTimeOffset]::Now.ToUnixTimeSeconds())
}

function Get-UpdateMigrationItems {
    param([string]$FastadminRoot)

    $migrationDir = Join-Path $FastadminRoot 'database\migrations'
    if (-not (Test-Path -LiteralPath $migrationDir)) {
        return @()
    }

    $items = @()
    foreach ($file in Get-ChildItem -Path $migrationDir -Filter '*.sql' -File | Sort-Object Name) {
        $id = [System.IO.Path]::GetFileNameWithoutExtension($file.Name)
        $items += [pscustomobject]@{
            id = $id
            file = ('database/migrations/' + $file.Name)
            checksum = (Get-FileHash -LiteralPath $file.FullName -Algorithm SHA256).Hash.ToLowerInvariant()
            description = $id
        }
    }

    return $items
}

function Normalize-InstallAuthRuleMenuType {
    param(
        [string]$MysqlExe,
        [string]$DbHostValue,
        [int]$Port,
        [string]$User,
        [string]$Password,
        [string]$Database
    )

    $sql = @"
UPDATE fa_auth_rule
SET menutype = NULL
WHERE menutype = '';
"@

    Invoke-MySqlScript -MysqlExe $MysqlExe -DbHostValue $DbHostValue -Port $Port -User $User -Password $Password -Database $Database -Sql $sql
}

function Get-PatchPackagePlan {
    param(
        [string]$ProjectRoot,
        [string]$BaseRevision
    )

    $lines = & git -c core.quotepath=false -C $ProjectRoot diff --name-status $BaseRevision -- fastadmin
    if ($LASTEXITCODE -ne 0) {
        throw "Failed to build patch package plan from [$BaseRevision]"
    }

    $copyFiles = New-Object System.Collections.Generic.List[string]
    $removeFiles = New-Object System.Collections.Generic.List[string]

    foreach ($line in $lines) {
        if ([string]::IsNullOrWhiteSpace($line)) {
            continue
        }

        $parts = $line -split "`t"
        $status = $parts[0]
        if ($status.StartsWith('R')) {
            if ($parts.Count -ge 3) {
                $removeFiles.Add($parts[1]) | Out-Null
                $copyFiles.Add($parts[2]) | Out-Null
            }
            continue
        }

        if ($status.StartsWith('D')) {
            if ($parts.Count -ge 2) {
                $removeFiles.Add($parts[1]) | Out-Null
            }
            continue
        }

        if ($parts.Count -ge 2) {
            $copyFiles.Add($parts[1]) | Out-Null
        }
    }

    $statusLines = & git -c core.quotepath=false -C $ProjectRoot status --porcelain --untracked-files=all -- fastadmin
    if ($LASTEXITCODE -ne 0) {
        throw "Failed to inspect untracked fastadmin files"
    }

    foreach ($statusLine in $statusLines) {
        if ([string]::IsNullOrWhiteSpace($statusLine)) {
            continue
        }

        if ($statusLine.StartsWith('?? ')) {
            $copyFiles.Add($statusLine.Substring(3).Trim()) | Out-Null
        }
    }

    if (-not ($copyFiles.Count -or $removeFiles.Count)) {
        throw "No fastadmin changes found between [$BaseRevision] and current workspace"
    }

    if (-not $copyFiles.Contains('fastadmin/deploy/update-manifest.json')) {
        $copyFiles.Add('fastadmin/deploy/update-manifest.json') | Out-Null
    }

    return [pscustomobject]@{
        copy_files = @($copyFiles | Sort-Object -Unique)
        remove_files = @($removeFiles | Sort-Object -Unique)
    }
}

function Copy-PackageFiles {
    param(
        [string]$ProjectRoot,
        [string]$StageRoot,
        [string[]]$Files
    )

    foreach ($relativeFile in $Files) {
        $sourcePath = Join-Path $ProjectRoot $relativeFile
        if (-not (Test-Path -LiteralPath $sourcePath)) {
            throw "Patch source file not found: $relativeFile"
        }

        $targetPath = Join-Path $StageRoot $relativeFile
        $null = New-Item -ItemType Directory -Force -Path (Split-Path -Path $targetPath -Parent)
        Copy-Item -LiteralPath $sourcePath -Destination $targetPath -Force
    }
}

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$fastadminRoot = Join-Path $projectRoot 'fastadmin'
$mysqlExe = Join-Path $MysqlBinDir 'mysql.exe'
$phpExe = (Get-Command php -ErrorAction Stop).Source
$phpTool = Join-Path $projectRoot 'scripts\mysql_utf8_tool.php'

Assert-CommandFile -Path $mysqlExe -Label 'mysql.exe'
Assert-CommandFile -Path $phpExe -Label 'php'
Assert-CommandFile -Path $phpTool -Label 'mysql_utf8_tool.php'
if ($PackageMode -eq 'full') {
    Assert-CommandFile -Path (Join-Path $fastadminRoot 'public\install.php') -Label 'install.php'
    Assert-CommandFile -Path (Join-Path $fastadminRoot 'deploy\baota\install-cli.php') -Label 'install-cli.php'
}

if ([string]::IsNullOrWhiteSpace($OutputRoot)) {
    $OutputRoot = Join-Path $projectRoot 'output'
}

$outputRootPath = [System.IO.Path]::GetFullPath($OutputRoot)
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$tempRoot = Join-Path $outputRootPath "tmp-baota-$stamp"
$stageRoot = Join-Path $outputRootPath "staging-baota-$stamp"
$stageFastadmin = Join-Path $stageRoot 'fastadmin'
$zipFileName = if ($PackageMode -eq 'patch') { "bysat-erp-fastadmin-patch-$stamp.zip" } else { "bysat-erp-fastadmin-baota-$stamp.zip" }
$zipPath = Join-Path $outputRootPath $zipFileName
$sourceDump = Join-Path $tempRoot 'source.sql'
$demoDatabase = New-TempDatabaseName -Prefix 'fastadmin_pack_demo'
$cleanDatabase = New-TempDatabaseName -Prefix 'fastadmin_pack_clean'
$demoDumpTarget = Join-Path $stageFastadmin 'database\install_demo.sql'
$cleanDumpTarget = Join-Path $stageFastadmin 'database\install_clean.sql'
$manifestTarget = Join-Path $stageFastadmin 'deploy\baota\package-manifest.json'
$updateManifestTarget = Join-Path $stageFastadmin 'deploy\update-manifest.json'

$demoCleanupSql = @"
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM fa_ai_conversation;
DELETE FROM fa_ai_task;
DELETE FROM fa_admin_log;
DELETE FROM fa_staff_audit;
UPDATE fa_ai_setting
SET provider_name = 'OpenAI Compatible',
    base_url = '',
    api_key = '',
    model = '',
    updatetime = UNIX_TIMESTAMP();
UPDATE fa_admin
SET token = '',
    loginfailure = 0,
    loginip = '',
    logintime = 0;
UPDATE fa_admin
SET password = MD5(CONCAT(MD5('Admin@123'), 'btad01')),
    salt = 'btad01',
    email = 'admin@example.com',
    mobile = '',
    token = '',
    loginfailure = 0,
    loginip = '',
    logintime = 0,
    status = 'normal'
WHERE id = 1;
UPDATE fa_admin
SET password = MD5(CONCAT(MD5('Start@123'), 'btusr01')),
    salt = 'btusr01',
    email = '',
    mobile = '',
    token = '',
    loginfailure = 0,
    loginip = '',
    logintime = 0,
    status = 'normal'
WHERE id > 1;
SET FOREIGN_KEY_CHECKS = 1;
"@

$cleanCleanupSql = @"
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM fa_ai_conversation;
DELETE FROM fa_ai_task;
DELETE FROM fa_admin_log;
DELETE FROM fa_staff_audit;
DELETE FROM fa_auth_group_access WHERE uid > 1;
DELETE FROM fa_staff_profile WHERE admin_id > 1;
DELETE FROM fa_admin WHERE id > 1;
DELETE FROM fa_finance_transaction;
DELETE FROM fa_finance_invoice;
DELETE FROM fa_project_task;
DELETE FROM fa_project;
DELETE FROM fa_app_issue_followup;
DELETE FROM fa_app_issue;
DELETE FROM fa_app_tech_ticket;
DELETE FROM fa_app_release;
DELETE FROM fa_app_milestone;
DELETE FROM fa_app_report;
DELETE FROM fa_app_risk;
DELETE FROM fa_app_material;
DELETE FROM fa_app_project;
DELETE FROM fa_business_approval;
DELETE FROM fa_business_payment_plan;
DELETE FROM fa_business_payment_request;
DELETE FROM fa_business_expense_request;
DELETE FROM fa_business_purchase_invoice;
DELETE FROM fa_business_purchase_settlement;
DELETE FROM fa_business_purchase_reconciliation;
DELETE FROM fa_business_purchase_order;
DELETE FROM fa_business_customer_followup;
DELETE FROM fa_business_receivable_plan;
DELETE FROM fa_business_contract;
DELETE FROM fa_business_supplier;
DELETE FROM fa_business_customer;
UPDATE fa_ai_setting
SET provider_name = 'OpenAI Compatible',
    base_url = '',
    api_key = '',
    model = '',
    updatetime = UNIX_TIMESTAMP();
UPDATE fa_admin
SET password = MD5(CONCAT(MD5('Admin@123'), 'btad01')),
    salt = 'btad01',
    username = 'admin',
    nickname = 'Admin',
    email = 'admin@example.com',
    mobile = '',
    token = '',
    loginfailure = 0,
    loginip = '',
    logintime = 0,
    status = 'normal'
WHERE id = 1;
UPDATE fa_staff_profile
SET account = 'admin',
    name = 'Admin',
    role_key = 'admin',
    status = 'active',
    manager_admin_id = 0,
    updatetime = UNIX_TIMESTAMP()
WHERE admin_id = 1;
SET FOREIGN_KEY_CHECKS = 1;
"@

$releaseAuthRuleSql = @'
SET @now = UNIX_TIMESTAMP();

INSERT INTO fa_auth_rule (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', p.id, 'finance/workbench/reportprint', CONVERT(0xe68993e58db0e68aa5e8a1a8 USING utf8mb4), '', '', '', CONVERT(0xe68993e58db0e8b4a2e58aa1e6b187e680bbe68aa5e8a1a8 USING utf8mb4), 0, NULL, '', '', '', @now, @now, 0, 'normal'
FROM fa_auth_rule p
LEFT JOIN fa_auth_rule e ON e.name = 'finance/workbench/reportprint'
WHERE p.name = 'finance/workbench' AND e.id IS NULL
LIMIT 1;

INSERT INTO fa_auth_rule (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', p.id, 'finance/workbench/reportexport', CONVERT(0xe5afbce587bae68aa5e8a1a8 USING utf8mb4), '', '', '', CONVERT(0xe5afbce587bae8b4a2e58aa1e7bb9fe8aea120435356 USING utf8mb4), 0, NULL, '', '', '', @now, @now, 0, 'normal'
FROM fa_auth_rule p
LEFT JOIN fa_auth_rule e ON e.name = 'finance/workbench/reportexport'
WHERE p.name = 'finance/workbench' AND e.id IS NULL
LIMIT 1;

INSERT INTO fa_auth_rule (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', p.id, 'finance/transaction/printview', CONVERT(0xe68993e58db0e9a284e8a788 USING utf8mb4), '', '', '', CONVERT(0xe8b584e98791e6b581e6b0b4e68993e58db0e9a284e8a788 USING utf8mb4), 0, NULL, '', '', '', @now, @now, 0, 'normal'
FROM fa_auth_rule p
LEFT JOIN fa_auth_rule e ON e.name = 'finance/transaction/printview'
WHERE p.name = 'finance/transaction' AND e.id IS NULL
LIMIT 1;

INSERT INTO fa_auth_rule (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', p.id, 'finance/invoice/printview', CONVERT(0xe68993e58db0e9a284e8a788 USING utf8mb4), '', '', '', CONVERT(0xe5ba94e694b6e5ba94e4bb98e8b4a6e58d95e68993e58db0e9a284e8a788 USING utf8mb4), 0, NULL, '', '', '', @now, @now, 0, 'normal'
FROM fa_auth_rule p
LEFT JOIN fa_auth_rule e ON e.name = 'finance/invoice/printview'
WHERE p.name = 'finance/invoice' AND e.id IS NULL
LIMIT 1;

INSERT INTO fa_auth_rule (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', p.id, 'general/upgrade/rollback', CONVERT(0xe59b9ee6bb9a USING utf8mb4), '', '', '', CONVERT(0xe4bb8ee69bb4e696b0e5a487e4bbbde59b9ee6bb9ae7b3bbe7bb9fe69687e4bbb6e5928ce695b0e68daee5ba93 USING utf8mb4), 0, NULL, '', '', '', @now, @now, 0, 'normal'
FROM fa_auth_rule p
LEFT JOIN fa_auth_rule e ON e.name = 'general/upgrade/rollback'
WHERE p.name = 'general/upgrade' AND e.id IS NULL
LIMIT 1;
'@

try {
    Write-Step "Preparing output directory"
    $null = New-Item -ItemType Directory -Force -Path $outputRootPath
    $null = New-Item -ItemType Directory -Force -Path $tempRoot

    $patchPlan = $null
    if ($PackageMode -eq 'patch') {
        Write-Step "Collecting patch files from git"
        $patchPlan = Get-PatchPackagePlan -ProjectRoot $projectRoot -BaseRevision $BaseRef
        Copy-PackageFiles -ProjectRoot $projectRoot -StageRoot $stageRoot -Files $patchPlan.copy_files
    } else {
        Write-Step "Copying fastadmin workspace to staging"
        Invoke-RobocopyMirror -Source $fastadminRoot -Destination $stageFastadmin

        $stageUpdaterData = Join-Path $stageFastadmin 'data\updater'
        if (Test-Path -LiteralPath $stageUpdaterData) {
            Remove-Item -LiteralPath $stageUpdaterData -Recurse -Force
        }

        $envTarget = Join-Path $stageFastadmin '.env'
        if (Test-Path -LiteralPath $envTarget) {
            Remove-Item -LiteralPath $envTarget -Force
        }
        $lockTarget = Join-Path $stageFastadmin 'application\admin\command\Install\install.lock'
        if (Test-Path -LiteralPath $lockTarget) {
            Remove-Item -LiteralPath $lockTarget -Force
        }

        Write-Step "Exporting source database"
        Export-MySqlDatabase -PhpExe $phpExe -PhpTool $phpTool -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Database $SourceDatabase -TargetFile $sourceDump

        Write-Step "Building demo install dataset"
        Invoke-MySqlScript -MysqlExe $mysqlExe -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Sql "DROP DATABASE IF EXISTS $demoDatabase; CREATE DATABASE $demoDatabase CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        Invoke-MySqlFile -PhpExe $phpExe -PhpTool $phpTool -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Database $demoDatabase -FilePath $sourceDump
        Invoke-MySqlScript -MysqlExe $mysqlExe -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Database $demoDatabase -Sql $demoCleanupSql
        Invoke-MySqlScript -MysqlExe $mysqlExe -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Database $demoDatabase -Sql $releaseAuthRuleSql
        Normalize-InstallAuthRuleMenuType -MysqlExe $mysqlExe -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Database $demoDatabase
        Export-MySqlDatabase -PhpExe $phpExe -PhpTool $phpTool -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Database $demoDatabase -TargetFile $demoDumpTarget

        Write-Step "Building clean install dataset"
        Invoke-MySqlScript -MysqlExe $mysqlExe -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Sql "DROP DATABASE IF EXISTS $cleanDatabase; CREATE DATABASE $cleanDatabase CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        Invoke-MySqlFile -PhpExe $phpExe -PhpTool $phpTool -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Database $cleanDatabase -FilePath $sourceDump
        Invoke-MySqlScript -MysqlExe $mysqlExe -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Database $cleanDatabase -Sql $cleanCleanupSql
        Invoke-MySqlScript -MysqlExe $mysqlExe -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Database $cleanDatabase -Sql $releaseAuthRuleSql
        Normalize-InstallAuthRuleMenuType -MysqlExe $mysqlExe -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Database $cleanDatabase
        Export-MySqlDatabase -PhpExe $phpExe -PhpTool $phpTool -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Database $cleanDatabase -TargetFile $cleanDumpTarget

        Write-Step "Writing package manifest"
        $null = New-Item -ItemType Directory -Force -Path (Split-Path -Path $manifestTarget -Parent)
        $manifest = @{
            package_name = [System.IO.Path]::GetFileName($zipPath)
            built_at = (Get-Date).ToString('yyyy-MM-dd HH:mm:ss')
            source_database = $SourceDatabase
            install_modes = @('clean', 'demo')
            admin_entry = 'MWDObBuRlr.php'
            admin_default = 'admin / Admin@123'
            recommended_mode = 'clean'
            guide_files = @(
                'docs/首次使用说明.md',
                'docs/正式部署与升级说明.md',
                'docs/正式版发布说明.md',
                'docs/上线检查清单.md',
                'public/docs/首次使用说明.html',
                'public/docs/正式部署与升级说明.html'
            )
        } | ConvertTo-Json -Depth 4
        Set-Content -Path $manifestTarget -Encoding UTF8 -Value $manifest
    }

    if (Test-Path -LiteralPath $updateManifestTarget) {
        Write-Step "Stamping online update manifest"
        $updateManifest = Get-Content -LiteralPath $updateManifestTarget -Raw | ConvertFrom-Json
        $gitCommit = (& git -C $projectRoot rev-parse HEAD).Trim()
        $gitBranch = (& git -C $projectRoot rev-parse --abbrev-ref HEAD).Trim()
        $gitRemote = (& git -C $projectRoot remote get-url origin).Trim()
        $remoteMatch = [regex]::Match($gitRemote, 'github\.com[:/](?<owner>[^/]+)/(?<repo>[^/.]+)')

        $updateManifest.built_at = (Get-Date).ToString('yyyy-MM-ddTHH:mm:ssK')
        if ($remoteMatch.Success) {
            $updateManifest.source.owner = $remoteMatch.Groups['owner'].Value
            $updateManifest.source.repo = $remoteMatch.Groups['repo'].Value
        }
        $updateManifest.source.branch = $gitBranch
        $updateManifest.source.commit = $gitCommit
        $updateManifest | Add-Member -NotePropertyName database -NotePropertyValue ([pscustomobject]@{
            backup_mode = 'when_migrations'
            migration_table = 'fa_erp_update_migration'
            migration_strategy = 'pre_deploy'
        }) -Force
        $updateManifest.package | Add-Member -NotePropertyName package_type -NotePropertyValue $PackageMode -Force
        if ($PackageMode -eq 'patch') {
            $updateManifest.source | Add-Member -NotePropertyName base_ref -NotePropertyValue $BaseRef -Force
            $updateManifest | Add-Member -NotePropertyName cleanup -NotePropertyValue ([pscustomobject]@{
                remove_files = @(
                    ($patchPlan.remove_files | ForEach-Object {
                        ($_ -replace '^fastadmin[\\/]', '') -replace '\\', '/'
                    })
                )
            }) -Force
        } else {
            $updateManifest | Add-Member -NotePropertyName cleanup -NotePropertyValue ([pscustomobject]@{
                remove_files = @()
            }) -Force
        }
        $updateManifest.migrations = @(Get-UpdateMigrationItems -FastadminRoot $stageFastadmin)

        $updateManifest | ConvertTo-Json -Depth 8 | Set-Content -LiteralPath $updateManifestTarget -Encoding UTF8
    }

    if (Test-Path -LiteralPath $zipPath) {
        Remove-Item -LiteralPath $zipPath -Force
    }

    Write-Step "Compressing deploy package"
    Compress-Archive -Path $stageFastadmin -DestinationPath $zipPath -CompressionLevel Optimal

    Write-Step "Package ready"
    Write-Host "ZIP: $zipPath" -ForegroundColor Green
    Write-Host "Staging: $stageRoot" -ForegroundColor Green
}
finally {
    foreach ($databaseName in @($demoDatabase, $cleanDatabase)) {
        try {
            Invoke-MySqlScript -MysqlExe $mysqlExe -DbHostValue $DbHost -Port $DbPort -User $DbUser -Password $DbPassword -Sql "DROP DATABASE IF EXISTS $databaseName;"
        } catch {
            Write-Warning "Failed to drop temp database [$databaseName]: $($_.Exception.Message)"
        }
    }

    if (Test-Path -LiteralPath $tempRoot) {
        Remove-TreeSafe -Path $tempRoot
    }

    if (-not $KeepStaging -and (Test-Path -LiteralPath $stageRoot)) {
        Remove-TreeSafe -Path $stageRoot
    }
}
