<#
.SYNOPSIS
    PharmaCare (Ahmad Pharmacy) - Windows deployment installer.

.DESCRIPTION
    Installs and configures the Laravel + Livewire pharmacy app on a client
    Windows machine. Uses composer.lock and package-lock.json to guarantee
    exact dependency versions (no drift from what was tested/delivered).

    Requires PHP 8.3+, Composer, and Node.js to already be installed and on
    PATH. Run scripts/check-prereqs.ps1 first if unsure, or just run this
    script — it checks and stops with a clear message if something is
    missing rather than guessing.

.NOTES
    Run this from the project root (the folder containing artisan, composer.json).
    Usage:
        powershell -ExecutionPolicy Bypass -File install.ps1
#>

[CmdletBinding()]
param(
    [switch]$SkipBuild,       # skip npm run build (useful if assets are pre-built)
    [switch]$Reseed,          # force re-run migrate:fresh --seed even if DB exists
    [string]$AppUrl = "http://127.0.0.1:8000"
)

$ErrorActionPreference = "Stop"
$ProjectRoot = $PSScriptRoot
Set-Location $ProjectRoot

function Write-Step($msg) {
    Write-Host ""
    Write-Host "==> $msg" -ForegroundColor Cyan
}

function Write-Ok($msg) {
    Write-Host "    OK: $msg" -ForegroundColor Green
}

function Write-Fail($msg) {
    Write-Host "    FAILED: $msg" -ForegroundColor Red
}

function Assert-Command($name, $installHint) {
    $cmd = Get-Command $name -ErrorAction SilentlyContinue
    if (-not $cmd) {
        Write-Fail "$name not found on PATH."
        Write-Host "    Install it first: $installHint" -ForegroundColor Yellow
        exit 1
    }
    return $cmd
}

Write-Host "=============================================" -ForegroundColor Magenta
Write-Host " PharmaCare (Ahmad Pharmacy) - Installer" -ForegroundColor Magenta
Write-Host "=============================================" -ForegroundColor Magenta

# ---------------------------------------------------------------------------
# 1. Prerequisite checks
# ---------------------------------------------------------------------------
Write-Step "Checking prerequisites"

$phpCmd = Assert-Command "php" "https://windows.php.net/download/ (add to PATH, version 8.3 or newer)"
$phpVersionRaw = (& php -r "echo PHP_VERSION;")
Write-Ok "PHP found: $phpVersionRaw"

$phpMajorMinor = ($phpVersionRaw -split '\.')[0..1] -join '.'
if ([version]$phpMajorMinor -lt [version]"8.3") {
    Write-Fail "PHP $phpVersionRaw found, but 8.3+ is required."
    exit 1
}

$composerCmd = Assert-Command "composer" "https://getcomposer.org/download/"
$composerVersion = (& composer --version)
Write-Ok "Composer found: $composerVersion"

$nodeCmd = Assert-Command "node" "https://nodejs.org/ (LTS recommended)"
$nodeVersion = (& node --version)
Write-Ok "Node found: $nodeVersion"

$npmCmd = Assert-Command "npm" "bundled with Node.js"
$npmVersion = (& npm --version)
Write-Ok "npm found: $npmVersion"

if (-not (Test-Path "$ProjectRoot\composer.json")) {
    Write-Fail "composer.json not found in $ProjectRoot. Run this script from the project root."
    exit 1
}

if (-not (Test-Path "$ProjectRoot\composer.lock")) {
    Write-Fail "composer.lock is missing. This file pins exact package versions and must ship with the delivery."
    exit 1
}

if (-not (Test-Path "$ProjectRoot\package-lock.json")) {
    Write-Fail "package-lock.json is missing. This file pins exact npm package versions and must ship with the delivery."
    exit 1
}

Write-Ok "Lock files present (composer.lock, package-lock.json) - versions will match exactly."

# ---------------------------------------------------------------------------
# 2. PHP extension sanity check (common missing ones on fresh Windows PHP)
# ---------------------------------------------------------------------------
Write-Step "Checking required PHP extensions"

$requiredExt = @("pdo_sqlite", "sqlite3", "mbstring", "openssl", "curl", "fileinfo", "tokenizer", "ctype", "xml")
$loadedExt = (& php -m)
$missingExt = @()
foreach ($ext in $requiredExt) {
    if ($loadedExt -notcontains $ext) {
        $missingExt += $ext
    }
}

if ($missingExt.Count -gt 0) {
    Write-Fail "Missing PHP extensions: $($missingExt -join ', ')"
    Write-Host "    Edit php.ini and uncomment/add the extension= lines for these, then re-run." -ForegroundColor Yellow
    exit 1
} else {
    Write-Ok "All required PHP extensions are enabled."
}

# ---------------------------------------------------------------------------
# 3. Composer install (exact versions from composer.lock)
# ---------------------------------------------------------------------------
Write-Step "Installing PHP dependencies (composer install, respects composer.lock)"

& composer install --no-interaction --prefer-dist --optimize-autoloader
if ($LASTEXITCODE -ne 0) {
    Write-Fail "composer install failed. See output above."
    exit 1
}
Write-Ok "PHP dependencies installed."

# ---------------------------------------------------------------------------
# 4. npm install (exact versions from package-lock.json via npm ci)
# ---------------------------------------------------------------------------
Write-Step "Installing JS dependencies (npm ci, respects package-lock.json exactly)"

& npm ci
if ($LASTEXITCODE -ne 0) {
    Write-Fail "npm ci failed. See output above."
    exit 1
}
Write-Ok "JS dependencies installed."

# ---------------------------------------------------------------------------
# 5. .env setup
# ---------------------------------------------------------------------------
Write-Step "Configuring environment (.env)"

if (-not (Test-Path "$ProjectRoot\.env")) {
    if (Test-Path "$ProjectRoot\.env.example") {
        Copy-Item "$ProjectRoot\.env.example" "$ProjectRoot\.env"
        Write-Ok ".env created from .env.example"
    } else {
        Write-Fail ".env.example not found, cannot create .env."
        exit 1
    }
} else {
    Write-Ok ".env already exists, leaving as-is."
}

# Ensure APP_URL is set correctly for this deployment (http, not https,
# since this is a plain artisan serve / local IIS setup unless behind a
# reverse proxy the client controls separately).
$envContent = Get-Content "$ProjectRoot\.env" -Raw
if ($envContent -match "(?m)^APP_URL=.*$") {
    $envContent = $envContent -replace "(?m)^APP_URL=.*$", "APP_URL=$AppUrl"
} else {
    $envContent += "`nAPP_URL=$AppUrl`n"
}
Set-Content "$ProjectRoot\.env" $envContent -NoNewline
Write-Ok "APP_URL set to $AppUrl"

# Force APP_ENV=production and APP_DEBUG=false for a client deployment.
$envContent = Get-Content "$ProjectRoot\.env" -Raw
$envContent = $envContent -replace "(?m)^APP_ENV=.*$", "APP_ENV=production"
$envContent = $envContent -replace "(?m)^APP_DEBUG=.*$", "APP_DEBUG=false"
Set-Content "$ProjectRoot\.env" $envContent -NoNewline
Write-Ok "APP_ENV=production, APP_DEBUG=false set for client delivery."

# ---------------------------------------------------------------------------
# 6. App key
# ---------------------------------------------------------------------------
Write-Step "Generating application key"

$envContent = Get-Content "$ProjectRoot\.env" -Raw
if ($envContent -match "(?m)^APP_KEY=\s*$" -or $envContent -notmatch "(?m)^APP_KEY=base64:") {
    & php artisan key:generate --force
    if ($LASTEXITCODE -ne 0) {
        Write-Fail "php artisan key:generate failed."
        exit 1
    }
    Write-Ok "APP_KEY generated."
} else {
    Write-Ok "APP_KEY already set, leaving as-is."
}

# ---------------------------------------------------------------------------
# 7. SQLite database file
# ---------------------------------------------------------------------------
Write-Step "Preparing database"

$dbPath = "$ProjectRoot\database\database.sqlite"
$dbExisted = Test-Path $dbPath

if (-not $dbExisted) {
    New-Item -ItemType File -Path $dbPath -Force | Out-Null
    Write-Ok "Created empty SQLite database file."
} else {
    Write-Ok "SQLite database file already exists."
}

if (-not $dbExisted -or $Reseed) {
    Write-Host "    Running migrate:fresh --seed (this will wipe existing data)." -ForegroundColor Yellow
    & php artisan migrate:fresh --seed --force
    if ($LASTEXITCODE -ne 0) {
        Write-Fail "Migration/seed failed."
        exit 1
    }
    Write-Ok "Database migrated and seeded."
} else {
    Write-Host "    Existing database detected and -Reseed not passed. Running plain migrate (safe, no data loss)." -ForegroundColor Yellow
    & php artisan migrate --force
    if ($LASTEXITCODE -ne 0) {
        Write-Fail "Migration failed."
        exit 1
    }
    Write-Ok "Database migrated (existing data preserved)."
}

# ---------------------------------------------------------------------------
# 8. Storage link + caches
# ---------------------------------------------------------------------------
Write-Step "Finalizing Laravel setup"

& php artisan storage:link
& php artisan config:clear
& php artisan config:cache
& php artisan route:cache
& php artisan view:cache
Write-Ok "Config, route, and view caches built for production."

# ---------------------------------------------------------------------------
# 9. Frontend build
# ---------------------------------------------------------------------------
if (-not $SkipBuild) {
    Write-Step "Building frontend assets (npm run build)"
    & npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Fail "npm run build failed."
        exit 1
    }
    Write-Ok "Frontend assets built."
} else {
    Write-Host ""
    Write-Host "==> Skipping frontend build (-SkipBuild passed)" -ForegroundColor Cyan
}

# ---------------------------------------------------------------------------
# Done
# ---------------------------------------------------------------------------
Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host " Install complete." -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""
Write-Host "To start the app:" -ForegroundColor White
Write-Host "    php artisan serve --host=127.0.0.1 --port=8000" -ForegroundColor Yellow
Write-Host ""
Write-Host "Then open: $AppUrl" -ForegroundColor White
Write-Host ""
Write-Host "Default pharmacy name is 'Ahmad Pharmacy' - update it under Settings in the app." -ForegroundColor White
Write-Host ""
