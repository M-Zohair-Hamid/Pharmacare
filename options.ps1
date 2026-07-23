<#
.SYNOPSIS
    PharmaCare Options — database maintenance menu (Reset / Backup / Restore).
    Launched from the "PharmaCare Options" desktop shortcut created by the
    installer, or can be run directly from inside the project folder.

.NOTES
    - Works against the SQLite database at database\database.sqlite.
    - Safe to re-run any number of times.
#>

$ErrorActionPreference = "Stop"

function Write-Step($msg) {
    Write-Host ""
    Write-Host ">> $msg" -ForegroundColor Cyan
}

function Write-Ok($msg) {
    Write-Host "   [OK] $msg" -ForegroundColor Green
}

function Write-Warn($msg) {
    Write-Host "   [!] $msg" -ForegroundColor Yellow
}

function Write-Err($msg) {
    Write-Host "   [FAIL] $msg" -ForegroundColor Red
}

# Use $PSScriptRoot (this script's own folder) so it keeps working even if
# the whole project folder gets moved or copied to a different path/client PC.
$ProjectRoot = $PSScriptRoot
Set-Location $ProjectRoot

if (-not (Test-Path (Join-Path $ProjectRoot "artisan"))) {
    Write-Err "Could not find 'artisan' in this folder. Make sure Options.ps1 sits inside the PharmaCare project folder."
    Read-Host "Press Enter to exit"
    exit 1
}

$dbPath = Join-Path $ProjectRoot "database\database.sqlite"
$backupDir = Join-Path $ProjectRoot "database\backups"

function Stop-PharmaCareServer {
    # The launcher starts 'php artisan serve' hidden. Stop any running PHP
    # dev server processes for this project before touching the database
    # file, so nothing is reading/writing it mid-operation.
    Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" -ErrorAction SilentlyContinue |
        Where-Object { $_.CommandLine -like "*artisan*serve*" } |
        ForEach-Object {
            try { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue } catch {}
        }
}

function Invoke-StopPharmaCare {
    Write-Step "Stop PharmaCare"
    Stop-PharmaCareServer
    Write-Ok "Any running PharmaCare server processes stopped."
}

function Show-Menu {
    Write-Host ""
    Write-Host "==================================================" -ForegroundColor Magenta
    Write-Host " PharmaCare Options" -ForegroundColor Magenta
    Write-Host " Project folder: $ProjectRoot" -ForegroundColor Magenta
    Write-Host "==================================================" -ForegroundColor Magenta
    Write-Host ""
    Write-Host "  1) Reset database   (wipes all data, starts fresh/empty)"
    Write-Host "  2) Backup database  (save a copy of the current database)"
    Write-Host "  3) Restore database (replace current database from a backup)"
    Write-Host "  4) Stop PharmaCare  (kill running server process)"
    Write-Host "  5) Exit"
    Write-Host ""
}

function Invoke-ResetDatabase {
    Write-Step "Reset database"
    Write-Warn "This will PERMANENTLY erase ALL data in PharmaCare (medicines, sales,"
    Write-Warn "purchases, suppliers, customers, settings) and start with an empty database."
    Write-Host ""
    $confirm1 = Read-Host "Type YES to continue"
    if ($confirm1 -ne "YES") {
        Write-Warn "Reset cancelled."
        return
    }
    $confirm2 = Read-Host "This cannot be undone unless you have a backup. Type RESET to confirm"
    if ($confirm2 -ne "RESET") {
        Write-Warn "Reset cancelled."
        return
    }

    Stop-PharmaCareServer

    try {
        # Offer a safety-net backup before wiping, in case the user meant
        # to click Backup instead.
        if (Test-Path $dbPath) {
            New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
            $safetyName = "before-reset_" + (Get-Date -Format "yyyy-MM-dd_HHmmss") + ".sqlite"
            Copy-Item $dbPath (Join-Path $backupDir $safetyName) -Force
            Write-Ok "Safety backup saved as $safetyName (in case this was a mistake)"
        }

        Remove-Item $dbPath -Force -ErrorAction SilentlyContinue
        New-Item -ItemType File -Path $dbPath -Force | Out-Null
        Write-Ok "Created empty database.sqlite"

        Write-Step "Running migrations"
        php artisan migrate --force
        if ($LASTEXITCODE -ne 0) { throw "php artisan migrate failed." }
        Write-Ok "Database reset complete"
    } catch {
        Write-Err "Reset failed: $($_.Exception.Message)"
    }
}

function Invoke-BackupDatabase {
    Write-Step "Backup database"

    if (-not (Test-Path $dbPath)) {
        Write-Err "No database found at $dbPath. Nothing to back up."
        return
    }

    try {
        New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
        $name = "backup_" + (Get-Date -Format "yyyy-MM-dd_HHmmss") + ".sqlite"
        $destPath = Join-Path $backupDir $name
        Copy-Item $dbPath $destPath -Force
        Write-Ok "Backup saved: $destPath"
    } catch {
        Write-Err "Backup failed: $($_.Exception.Message)"
    }
}

function Invoke-RestoreDatabase {
    Write-Step "Restore database"

    if (-not (Test-Path $backupDir) -or -not (Get-ChildItem $backupDir -Filter "*.sqlite" -ErrorAction SilentlyContinue)) {
        Write-Err "No backups found in $backupDir. Create one with 'Backup database' first."
        return
    }

    $backups = Get-ChildItem $backupDir -Filter "*.sqlite" | Sort-Object LastWriteTime -Descending

    Write-Host ""
    Write-Host "Available backups:" -ForegroundColor Cyan
    for ($i = 0; $i -lt $backups.Count; $i++) {
        $b = $backups[$i]
        Write-Host ("  {0}) {1}   ({2})" -f ($i + 1), $b.Name, $b.LastWriteTime)
    }
    Write-Host ""

    $choice = Read-Host "Enter the number of the backup to restore (or 0 to cancel)"
    if ($choice -notmatch '^\d+$' -or [int]$choice -lt 1 -or [int]$choice -gt $backups.Count) {
        Write-Warn "Restore cancelled."
        return
    }

    $chosen = $backups[[int]$choice - 1]

    Write-Warn "This will REPLACE the current database with '$($chosen.Name)'."
    Write-Warn "Any data added since that backup was made will be lost."
    $confirm = Read-Host "Type YES to continue"
    if ($confirm -ne "YES") {
        Write-Warn "Restore cancelled."
        return
    }

    Stop-PharmaCareServer

    try {
        # Safety-net copy of the current database before overwriting it.
        if (Test-Path $dbPath) {
            New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
            $safetyName = "before-restore_" + (Get-Date -Format "yyyy-MM-dd_HHmmss") + ".sqlite"
            Copy-Item $dbPath (Join-Path $backupDir $safetyName) -Force
            Write-Ok "Safety backup of current database saved as $safetyName"
        }

        Copy-Item $chosen.FullName $dbPath -Force
        Write-Ok "Database restored from $($chosen.Name)"

        Write-Step "Applying any pending migrations"
        php artisan migrate --force
        if ($LASTEXITCODE -ne 0) { throw "php artisan migrate failed." }
        Write-Ok "Restore complete"
    } catch {
        Write-Err "Restore failed: $($_.Exception.Message)"
    }
}

# ---------------------------------------------------------------
# Menu loop
# ---------------------------------------------------------------

$exit = $false
while (-not $exit) {
    Show-Menu
    $selection = Read-Host "Choose an option (1-5)"
    switch ($selection) {
        "1" { Invoke-ResetDatabase }
        "2" { Invoke-BackupDatabase }
        "3" { Invoke-RestoreDatabase }
        "4" { Invoke-StopPharmaCare }
        "5" { $exit = $true }
        default { Write-Warn "Please enter 1, 2, 3, 4, or 5." }
    }
}

Write-Host ""
Write-Host "Done." -ForegroundColor Green