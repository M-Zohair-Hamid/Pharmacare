<#
.SYNOPSIS
    Creates a "PharmaCare" icon on your Desktop. Double-click that icon any
    time to open PharmaCare in your browser -- no folders, no other scripts.

.NOTES
    Run this ONCE. No admin rights needed.
#>

$ErrorActionPreference = "Stop"
$ProjectRoot = $PSScriptRoot

$desktop = [Environment]::GetFolderPath("Desktop")
$shortcutPath = Join-Path $desktop "PharmaCare.lnk"
$targetScript = Join-Path $ProjectRoot "Start-PharmaCare.ps1"
$iconPath = Join-Path $ProjectRoot "assets\icon.ico"

if (-not (Test-Path $targetScript)) {
    Write-Host "[FAIL] Could not find Start-PharmaCare.ps1 next to this script." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

$WshShell = New-Object -ComObject WScript.Shell
$Shortcut = $WshShell.CreateShortcut($shortcutPath)
$Shortcut.TargetPath = "powershell.exe"
$Shortcut.Arguments = "-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$targetScript`""
$Shortcut.WorkingDirectory = $ProjectRoot
if (Test-Path $iconPath) {
    $Shortcut.IconLocation = $iconPath
}
$Shortcut.Description = "Open PharmaCare"
$Shortcut.Save()

Write-Host ""
Write-Host "[OK] Done! A 'PharmaCare' icon is now on your Desktop." -ForegroundColor Green
Write-Host "     Just double-click it any time to open PharmaCare." -ForegroundColor Green
Write-Host ""
Read-Host "Press Enter to close"
