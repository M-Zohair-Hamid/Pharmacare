# PharmaCare Desktop App

Turns PharmaCare into a normal double-clickable Windows app — its own window,
its own icon, no browser tab, no console window. Double-click, it opens.

## One-time setup

1. Make sure you've already run `installer.ps1` in the main project folder
   at least once (PharmaCare needs PHP; this desktop wrapper needs Node.js —
   the installer sets up both).
2. Right-click **Build-DesktopApp.ps1** → **Run with PowerShell**.
   (No admin rights needed for this one.)
3. When it finishes, open the `dist` folder it creates and run
   **`PharmaCare Setup 1.0.0.exe`** once. That installs PharmaCare with a
   desktop shortcut, just like any other app.

## After that

Just double-click the **PharmaCare** icon on your desktop / Start menu.

The very first time you open it, a window will ask you to pick your
`pharmacare-laravel` folder (the one with `artisan` in it) — just select
it once. PharmaCare remembers that choice from then on, so every launch
after that opens instantly with no prompts.

It opens in its own window. Closing the window shuts down the background
server too — nothing keeps running after you close it.

## How it works

This is a small Electron wrapper. When you launch it:
- It quietly starts the same local server (`php artisan serve`) in the
  background — you never see a console window for it.
- It opens a native app window pointing at that local server.
- Your data still lives in `database/database.sqlite` in the main project
  folder exactly as before — this wrapper doesn't change how or where data
  is stored, it just changes how you open the app.

If you ever update the PharmaCare code itself, you don't need to rebuild
this wrapper — it always loads whatever is currently running on the local
server. You'd only re-run `Build-DesktopApp.ps1` if you change files inside
this `electron-shell` folder itself.
