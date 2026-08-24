<#
.SYNOPSIS
    PharmaCare installer. Run this from inside the project folder
    (the folder containing composer.json, artisan, etc).

.NOTES
    - Must be run as Administrator (needed for winget installs).
    - Requires Windows 10 1809+ / Windows 11 (winget preinstalled).
    - Safe to re-run: skips anything already installed/done.
#>

# ---------------------------------------------------------------
# 0. Setup - must run as admin, must run from project folder
# ---------------------------------------------------------------

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

$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "This installer needs Administrator rights (to install PHP/Node/Composer if missing)." -ForegroundColor Red
    Write-Host "Right-click Install-PharmaCare.ps1 -> Run with PowerShell as Administrator." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# The project root is wherever this script lives.
$ProjectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ProjectRoot

Write-Host "==================================================" -ForegroundColor Magenta
Write-Host " PharmaCare Installer" -ForegroundColor Magenta
Write-Host " Project folder: $ProjectRoot" -ForegroundColor Magenta
Write-Host "==================================================" -ForegroundColor Magenta

if (-not (Test-Path (Join-Path $ProjectRoot "artisan"))) {
    Write-Err "Could not find 'artisan' in this folder. Make sure this script sits inside the PharmaCare project folder (next to composer.json)."
    Read-Host "Press Enter to exit"
    exit 1
}

# ---------------------------------------------------------------
# 1. Check winget itself is available
# ---------------------------------------------------------------

Write-Step "Checking winget (Windows Package Manager)"
$wingetOk = $null -ne (Get-Command winget -ErrorAction SilentlyContinue)
if (-not $wingetOk) {
    Write-Warn "winget not found. Attempting automatic install..."

    try {
        $progressPreference = 'SilentlyContinue'
        Install-PackageProvider -Name NuGet -Force -ErrorAction Stop | Out-Null
        Install-Module -Name Microsoft.WinGet.Client -Force -Repository PSGallery -ErrorAction Stop
        Repair-WinGetPackageManager -ErrorAction Stop
    } catch {
        Write-Err "Automatic winget install failed: $($_.Exception.Message)"
        Write-Err "Install 'App Installer' from the Microsoft Store manually, then re-run this script."
        Read-Host "Press Enter to exit"
        exit 1
    }

    # Refresh PATH in this session so 'winget' is usable without restarting the shell.
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

    $wingetOk = $null -ne (Get-Command winget -ErrorAction SilentlyContinue)
    if (-not $wingetOk) {
        Write-Err "winget still not found after install attempt. Close this window, open a NEW PowerShell (Admin), and re-run this script."
        Read-Host "Press Enter to exit"
        exit 1
    }
    Write-Ok "winget installed"
} else {
    Write-Ok "winget available"
}

# ---------------------------------------------------------------
# 2. PHP 8.5+
# ---------------------------------------------------------------

Write-Step "Checking PHP"

function Get-PhpMajorMinor {
    try {
        $v = (php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;") 2>$null
        return $v
    } catch {
        return $null
    }
}

$phpVersion = Get-PhpMajorMinor

if ($phpVersion -and ([version]$phpVersion -ge [version]"8.5")) {
    Write-Ok "PHP $phpVersion found"
} else {
    if ($phpVersion) {
        Write-Warn "PHP $phpVersion found, but 8.5+ is required. Installing PHP 8.5..."
    } else {
        Write-Warn "PHP not found. Installing PHP 8.5..."
    }
    winget install --id PHP.PHP.8.5 -e --source winget --accept-source-agreements --accept-package-agreements
    # Refresh PATH in this session so 'php' is usable without restarting the shell.
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

    $phpVersion = Get-PhpMajorMinor
    if (-not $phpVersion -or [version]$phpVersion -lt [version]"8.5") {
        Write-Err "PHP install did not complete correctly. Close this window, open a NEW PowerShell (Admin), and re-run this script."
        Read-Host "Press Enter to exit"
        exit 1
    }
    Write-Ok "PHP $phpVersion installed"
}

# ---------------------------------------------------------------
# 3. Required PHP extensions
# ---------------------------------------------------------------

Write-Step "Checking required PHP extensions"

$requiredExt = @("pdo_sqlite", "sqlite3", "mbstring", "openssl", "tokenizer", "xml", "ctype", "curl", "fileinfo", "bcmath")
$loadedExt = (php -m) | ForEach-Object { $_.Trim().ToLower() }

$missingExt = @()
foreach ($ext in $requiredExt) {
    if ($loadedExt -notcontains $ext.ToLower()) {
        $missingExt += $ext
    }
}

if ($missingExt.Count -eq 0) {
    Write-Ok "All required PHP extensions are enabled"
} else {
    Write-Warn "Missing/disabled extensions: $($missingExt -join ', ')"
    Write-Warn "Attempting to enable them in php.ini..."

    $phpIniLine = (php --ini | Select-String "Loaded Configuration File")
    $phpIniPath = $null
    if ($phpIniLine) {
        $candidate = $phpIniLine.ToString().Split(":", 2)[1].Trim()
        if ($candidate -and $candidate -ne "(none)") {
            $phpIniPath = $candidate
        }
    }

    if (-not $phpIniPath) {
        # No php.ini loaded at all - fresh winget install ships templates
        # (php.ini-production / php.ini-development) next to php.exe but
        # doesn't activate either one. Create php.ini from the template.
        Write-Warn "No php.ini loaded. Creating one from PHP's default template..."

        $phpExePath = (Get-Command php -ErrorAction SilentlyContinue).Source
        $phpDir = Split-Path -Parent $phpExePath

        $template = Join-Path $phpDir "php.ini-production"
        if (-not (Test-Path $template)) {
            $template = Join-Path $phpDir "php.ini-development"
        }

        if (Test-Path $template) {
            $newIniPath = Join-Path $phpDir "php.ini"
            Copy-Item $template $newIniPath -Force

            # Windows PHP templates leave extension_dir commented out or
            # pointed at a placeholder - point it at the real ext folder.
            $extDir = Join-Path $phpDir "ext"
            if (Test-Path $extDir) {
                $iniLines = Get-Content $newIniPath
                $iniLines = $iniLines -replace '^;?\s*extension_dir\s*=.*$', ('extension_dir = "' + $extDir + '"')
                Set-Content -Path $newIniPath -Value $iniLines
            }

            $phpIniPath = $newIniPath
            Write-Ok "Created php.ini at $phpIniPath"
        } else {
            Write-Err "No php.ini template found in $phpDir either. Cannot proceed automatically."
            Write-Err "Manually create php.ini in $phpDir and enable: $($missingExt -join ', ')"
            Read-Host "Press Enter to exit"
            exit 1
        }
    }

    if ($phpIniPath -and (Test-Path $phpIniPath)) {
        $iniContent = Get-Content $phpIniPath
        foreach ($ext in $missingExt) {
            $disabledLine = ";extension=$ext"
            $enabledLine = "extension=$ext"
            if ($iniContent -match [regex]::Escape($disabledLine)) {
                $iniContent = $iniContent -replace [regex]::Escape($disabledLine), $enabledLine
                Write-Ok "Enabled $ext in php.ini"
            } else {
                # Not present at all - append it.
                $iniContent += $enabledLine
                Write-Ok "Added $ext to php.ini"
            }
        }
        Set-Content -Path $phpIniPath -Value $iniContent

        $loadedExt = (php -m) | ForEach-Object { $_.Trim().ToLower() }
        $stillMissing = $missingExt | Where-Object { $loadedExt -notcontains $_.ToLower() }
        if ($stillMissing.Count -gt 0) {
            Write-Err "Could not auto-enable: $($stillMissing -join ', '). Open $phpIniPath manually and uncomment these extensions."
            Read-Host "Press Enter to exit"
            exit 1
        }
    } else {
        Write-Err "Could not locate php.ini automatically. Run 'php --ini' to find it and enable manually: $($missingExt -join ', ')"
        Read-Host "Press Enter to exit"
        exit 1
    }
}

# ---------------------------------------------------------------
# 4. Composer
# ---------------------------------------------------------------

Write-Step "Checking Composer"

$composerOk = $null -ne (Get-Command composer -ErrorAction SilentlyContinue)
if ($composerOk) {
    Write-Ok "Composer found"
} else {
    Write-Warn "Composer not found. Installing via official installer..."

    # winget has no reliable Composer package - use getcomposer.org's own
    # installer script instead. Runs installer.php then places composer.phar
    # as a global 'composer' command. (Fetched over HTTPS from the official
    # domain, so this is the same trust level as downloading it by hand.)
    $composerDir = "C:\ProgramData\ComposerSetup\bin"
    New-Item -ItemType Directory -Path $composerDir -Force | Out-Null

    try {
        $installerPath = Join-Path $env:TEMP "composer-setup.php"
        Invoke-WebRequest -Uri "https://getcomposer.org/installer" -OutFile $installerPath -UseBasicParsing

        php $installerPath --install-dir="$composerDir" --filename=composer.phar --quiet
        if ($LASTEXITCODE -ne 0) { throw "composer-setup.php exited with code $LASTEXITCODE" }

        # Wrap composer.phar in a composer.bat so 'composer' works directly.
        $batContent = @(
            '@echo off'
            ('php "%~dp0composer.phar" %*')
        )
        Set-Content -Path (Join-Path $composerDir "composer.bat") -Value $batContent -Encoding ASCII

        # Add to machine PATH permanently (not just this session) if not already there.
        $machinePath = [System.Environment]::GetEnvironmentVariable("Path","Machine")
        if ($machinePath -notlike "*$composerDir*") {
            [System.Environment]::SetEnvironmentVariable("Path", "$machinePath;$composerDir", "Machine")
        }
    } catch {
        Write-Err "Composer install failed: $($_.Exception.Message)"
        Read-Host "Press Enter to exit"
        exit 1
    }

    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

    if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
        Write-Err "Composer install did not complete correctly. Close this window, open a NEW PowerShell (Admin), and re-run this script."
        Read-Host "Press Enter to exit"
        exit 1
    }
    Write-Ok "Composer installed"
}

# ---------------------------------------------------------------
# 5. Node.js (LTS, 20+)
# ---------------------------------------------------------------

Write-Step "Checking Node.js"

function Get-NodeMajor {
    try {
        $v = (node -v) 2>$null
        if ($v -match "v(\d+)\.") { return [int]$matches[1] }
        return $null
    } catch {
        return $null
    }
}

$nodeMajor = Get-NodeMajor

if ($nodeMajor -and $nodeMajor -ge 18) {
    Write-Ok "Node.js v$nodeMajor found"
} else {
    if ($nodeMajor) {
        Write-Warn "Node.js v$nodeMajor found, but 18+ is required. Installing Node LTS..."
    } else {
        Write-Warn "Node.js not found. Installing Node LTS..."
    }
    winget install --id OpenJS.NodeJS.LTS -e --source winget --accept-source-agreements --accept-package-agreements
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

    $nodeMajor = Get-NodeMajor
    if (-not $nodeMajor -or $nodeMajor -lt 18) {
        Write-Err "Node install did not complete correctly. Close this window, open a NEW PowerShell (Admin), and re-run this script."
        Read-Host "Press Enter to exit"
        exit 1
    }
    Write-Ok "Node.js v$nodeMajor installed"
}

# ---------------------------------------------------------------
# 6. Project setup - composer install, .env, key, migrate, npm build
# ---------------------------------------------------------------

Write-Step "Installing PHP dependencies (composer install)"
composer install --no-dev --optimize-autoloader
if ($LASTEXITCODE -ne 0) { Write-Err "composer install failed."; Read-Host "Press Enter to exit"; exit 1 }
Write-Ok "PHP dependencies installed"

Write-Step "Setting up environment file"
$envPath = Join-Path $ProjectRoot ".env"
$envExamplePath = Join-Path $ProjectRoot ".env.example"
if (-not (Test-Path $envPath)) {
    Copy-Item $envExamplePath $envPath
    Write-Ok ".env created from .env.example"
} else {
    Write-Ok ".env already exists, leaving it as is"
}

Write-Step "Generating application key"
$envContent = Get-Content $envPath -Raw
if ($envContent -match "APP_KEY=\s*$" -or $envContent -notmatch "APP_KEY=base64:") {
    php artisan key:generate --force
    Write-Ok "Application key generated"
} else {
    Write-Ok "Application key already set"
}

Write-Step "Setting up database"
$dbPath = Join-Path $ProjectRoot "database\database.sqlite"
if (-not (Test-Path $dbPath)) {
    New-Item -ItemType File -Path $dbPath -Force | Out-Null
    Write-Ok "Created empty database.sqlite"
} else {
    Write-Ok "database.sqlite already exists - will not overwrite existing data"
}

Write-Step "Running migrations"
php artisan migrate --force
if ($LASTEXITCODE -ne 0) { Write-Err "Migration failed."; Read-Host "Press Enter to exit"; exit 1 }
Write-Ok "Database migrated"

Write-Step "Installing frontend dependencies (npm install)"
npm install
if ($LASTEXITCODE -ne 0) { Write-Err "npm install failed."; Read-Host "Press Enter to exit"; exit 1 }
Write-Ok "Frontend dependencies installed"

Write-Step "Building frontend assets (npm run build)"
npm run build
if ($LASTEXITCODE -ne 0) { Write-Err "npm run build failed."; Read-Host "Press Enter to exit"; exit 1 }
Write-Ok "Frontend assets built"

# ---------------------------------------------------------------
# 6b. Activate this installation - bind license to this machine
# ---------------------------------------------------------------

Write-Step "Activating license for this device"
$licenseLockPath = Join-Path $ProjectRoot "storage\license.lock"
if (Test-Path $licenseLockPath) {
    Write-Ok "This installation is already activated - leaving existing activation in place."
    Write-Ok "(If you are re-installing on the SAME PC after a problem, this is expected.)"
} else {
    php artisan license:generate
    if ($LASTEXITCODE -ne 0) {
        Write-Err "License activation failed. Contact PharmaCare support before using the software."
        Read-Host "Press Enter to exit"
        exit 1
    }
    Write-Ok "License activated for this device."
}

# ---------------------------------------------------------------
# 7. Create the silent launcher + desktop shortcut
# ---------------------------------------------------------------

Write-Step "Creating launcher"

$launcherPs1 = Join-Path $ProjectRoot "Start-PharmaCare.ps1"

# Built as an array of plain strings (no here-string) to avoid any
# backtick/escaping parsing issues entirely.
$launcherLines = @(
    '# Starts the PharmaCare server hidden, waits for it to respond, opens the browser.'
    '# Uses $PSScriptRoot (this script''s own folder) so it keeps working even if'
    '# the whole project folder gets moved or copied to a different path/client PC.'
    '$ProjectRoot = $PSScriptRoot'
    'Set-Location $ProjectRoot'
    ''
    '$port = 8000'
    '$url = "http://127.0.0.1:$port"'
    ''
    '# Start php artisan serve completely hidden, no console window.'
    'Start-Process -FilePath "php" -ArgumentList "artisan","serve","--port=$port" -WindowStyle Hidden'
    ''
    '# Wait for the server to actually come up before opening the browser.'
    '$maxTries = 20'
    '$ready = $false'
    'for ($i = 0; $i -lt $maxTries; $i++) {'
    '    Start-Sleep -Milliseconds 500'
    '    try {'
    '        $resp = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 1'
    '        if ($resp.StatusCode -eq 200) { $ready = $true; break }'
    '    } catch {'
    '        # Not up yet, keep waiting.'
    '    }'
    '}'
    ''
    'Start-Process $url'
)
Set-Content -Path $launcherPs1 -Value $launcherLines -Encoding UTF8
Write-Ok "Created Start-PharmaCare.ps1"

# Desktop shortcut launches powershell.exe directly with -WindowStyle Hidden.
# (Previously used a .vbs wrapper for a fully silent launch, but Windows
# Script Host isn't available on some locked-down/sandboxed machines, which
# broke the shortcut with a "no script engine" error. PowerShell's own
# -WindowStyle Hidden achieves the same silent effect without that dependency.)
Write-Step "Creating desktop shortcut"
$desktopPath = [Environment]::GetFolderPath("Desktop")
$shortcutPath = Join-Path $desktopPath "PharmaCare.lnk"

$powershellExe = Join-Path $env:SystemRoot "System32\WindowsPowerShell\v1.0\powershell.exe"

$wshShell = New-Object -ComObject WScript.Shell
$shortcut = $wshShell.CreateShortcut($shortcutPath)
$shortcut.TargetPath = $powershellExe
$shortcut.Arguments = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "' + $launcherPs1 + '"'
$shortcut.WorkingDirectory = $ProjectRoot
$shortcut.IconLocation = "shell32.dll,220"   # generic "medical/store" style icon from Windows' own icon set
$shortcut.Description = "Start PharmaCare"
$shortcut.Save()

Write-Ok "Desktop shortcut created: $shortcutPath"

# ---------------------------------------------------------------
# 8. Create the Options desktop shortcut (Reset / Backup / Restore database)
# ---------------------------------------------------------------

Write-Step "Creating Options shortcut"

$optionsPs1 = Join-Path $ProjectRoot "options.ps1"

if (Test-Path $optionsPs1) {
    $optionsShortcutPath = Join-Path $desktopPath "PharmaCare Options.lnk"

    $optionsShortcut = $wshShell.CreateShortcut($optionsShortcutPath)
    $optionsShortcut.TargetPath = $powershellExe
    # No -WindowStyle Hidden here: Options is an interactive console menu,
    # so its window needs to stay visible for the user to read/respond to it.
    $optionsShortcut.Arguments = '-NoProfile -ExecutionPolicy Bypass -File "' + $optionsPs1 + '"'
    $optionsShortcut.WorkingDirectory = $ProjectRoot
    $optionsShortcut.IconLocation = "shell32.dll,166"   # generic "settings/gears" style icon from Windows' own icon set
    $optionsShortcut.Description = "PharmaCare database options: reset, backup, restore"
    $optionsShortcut.Save()

    Write-Ok "Desktop shortcut created: $optionsShortcutPath"
} else {
    Write-Warn "options.ps1 not found next to installer.ps1 - skipping Options shortcut."
}

# ---------------------------------------------------------------
# Done
# ---------------------------------------------------------------


Write-Host ""
Write-Host "==================================================" -ForegroundColor Green
Write-Host " Install complete." -ForegroundColor Green
Write-Host " Double-click the 'PharmaCare' icon on the Desktop to start the app." -ForegroundColor Green
Write-Host " It opens quietly in the background and launches your browser automatically." -ForegroundColor Green
Write-Host ""
Write-Host " Use the 'PharmaCare Options' icon on the Desktop to reset, back up," -ForegroundColor Green
Write-Host " or restore the database." -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Green

Read-Host "Press Enter to close"
