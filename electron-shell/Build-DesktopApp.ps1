<#
.SYNOPSIS
    Builds PharmaCare.exe - a real double-clickable desktop app that opens
    PharmaCare instantly in its own window (no browser, no console window).

.NOTES
    Run this ONCE, from inside the "electron-shell" folder, on the same
    Windows PC where you already ran installer.ps1 (Node.js is required).
    Safe to re-run any time you update the app - it will just rebuild.
#>

$ErrorActionPreference = "Stop"
$ProjectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ProjectRoot

Write-Host "==================================================" -ForegroundColor Magenta
Write-Host " Building PharmaCare desktop app" -ForegroundColor Magenta
Write-Host "==================================================" -ForegroundColor Magenta

if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
    Write-Host "[FAIL] Node.js not found. Run installer.ps1 in the main project folder first." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host ""
Write-Host ">> Installing build tools (first run only, this can take a minute)..." -ForegroundColor Cyan
npm install
if ($LASTEXITCODE -ne 0) { Write-Host "[FAIL] npm install failed." -ForegroundColor Red; Read-Host "Press Enter to exit"; exit 1 }

Write-Host ""
Write-Host ">> Building PharmaCare.exe..." -ForegroundColor Cyan
# Skip code-signing lookups entirely -- we're not signing the app, and the
# tooling it downloads for that (winCodeSign) contains macOS symlinks that
# fail to extract without extra Windows privileges. Not needed here.
$env:CSC_IDENTITY_AUTO_DISCOVERY = "false"
npm run dist
if ($LASTEXITCODE -ne 0) { Write-Host "[FAIL] Build failed." -ForegroundColor Red; Read-Host "Press Enter to exit"; exit 1 }

Write-Host ""
Write-Host "[OK] Done! Your installer is here:" -ForegroundColor Green
Write-Host "     $ProjectRoot\dist\PharmaCare Setup 1.0.0.exe" -ForegroundColor Green
Write-Host ""
Write-Host "Run that .exe once - it installs PharmaCare with a desktop icon." -ForegroundColor Green
Write-Host "After that, just double-click the PharmaCare icon to open it instantly." -ForegroundColor Green
Write-Host ""
Write-Host "(A portable single-file version was also created in the same 'dist' folder" -ForegroundColor DarkGray
Write-Host " if you'd rather not install anything.)" -ForegroundColor DarkGray
Read-Host "Press Enter to close"
