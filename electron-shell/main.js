const { app, BrowserWindow, Menu, dialog } = require('electron');
const path = require('path');
const fs = require('fs');
const http = require('http');
const { spawn, execSync } = require('child_process');

const PORT = 8000;
const HOST = '127.0.0.1';
const URL = `http://${HOST}:${PORT}`;

let mainWindow = null;
let phpProcess = null;
let serverWasAlreadyRunning = false;
let projectRoot = null;

const configPath = path.join(app.getPath('userData'), 'pharmacare-config.json');

// --- Prevent double-launching the app (double-clicking twice just focuses the existing window) ---
const gotLock = app.requestSingleInstanceLock();
if (!gotLock) {
  app.quit();
} else {
  app.on('second-instance', () => {
    if (mainWindow) {
      if (mainWindow.isMinimized()) mainWindow.restore();
      mainWindow.focus();
    }
  });
}

function isValidProjectFolder(folder) {
  return !!folder && fs.existsSync(path.join(folder, 'artisan'));
}

function loadSavedProjectRoot() {
  try {
    const raw = fs.readFileSync(configPath, 'utf-8');
    const data = JSON.parse(raw);
    if (isValidProjectFolder(data.projectRoot)) {
      return data.projectRoot;
    }
  } catch (e) {
    // No config yet, or it's invalid -- that's fine, we'll ask.
  }
  return null;
}

function saveProjectRoot(folder) {
  try {
    fs.mkdirSync(path.dirname(configPath), { recursive: true });
    fs.writeFileSync(configPath, JSON.stringify({ projectRoot: folder }, null, 2));
  } catch (e) {
    // Non-fatal -- worst case we just ask again next launch.
  }
}

async function resolveProjectRoot() {
  const saved = loadSavedProjectRoot();
  if (saved) return saved;

  // First run (or the saved folder moved/was deleted) -- ask the user to point to it once.
  dialog.showMessageBoxSync({
    type: 'info',
    title: 'One-time setup',
    message: 'Select your PharmaCare project folder',
    detail:
      'Please choose the "pharmacare-laravel" folder (the one that contains a file named "artisan"). ' +
      "PharmaCare will remember this and won't ask again.",
  });

  while (true) {
    const result = dialog.showOpenDialogSync({
      title: 'Select the pharmacare-laravel folder',
      properties: ['openDirectory'],
    });

    if (!result || result.length === 0) {
      const retry = dialog.showMessageBoxSync({
        type: 'warning',
        buttons: ['Try again', 'Quit'],
        defaultId: 0,
        message: 'A folder is required to continue.',
      });
      if (retry === 1) {
        app.quit();
        process.exit(0);
      }
      continue;
    }

    const chosen = result[0];
    if (isValidProjectFolder(chosen)) {
      saveProjectRoot(chosen);
      return chosen;
    }

    dialog.showErrorBox(
      "That doesn't look right",
      'That folder doesn\'t contain "artisan". Please select the actual pharmacare-laravel project folder.'
    );
  }
}

function isServerUp() {
  return new Promise((resolve) => {
    const req = http.get(URL, (res) => {
      res.destroy();
      resolve(true);
    });
    req.on('error', () => resolve(false));
    req.setTimeout(1000, () => {
      req.destroy();
      resolve(false);
    });
  });
}

function waitForServer(maxTries = 40, intervalMs = 500) {
  return new Promise((resolve, reject) => {
    let tries = 0;
    const tick = async () => {
      tries += 1;
      if (await isServerUp()) {
        resolve();
        return;
      }
      if (tries >= maxTries) {
        reject(new Error('Server did not start in time.'));
        return;
      }
      setTimeout(tick, intervalMs);
    };
    tick();
  });
}

function startPhpServer() {
  // "php" must be on PATH -- the project's installer.ps1 already makes sure of this.
  phpProcess = spawn('php', ['artisan', 'serve', `--port=${PORT}`], {
    cwd: projectRoot,
    windowsHide: true,
    detached: false,
  });

  phpProcess.on('error', (err) => {
    dialog.showErrorBox(
      'PharmaCare could not start',
      `PHP could not be launched.\n\nMake sure PHP is installed (run installer.ps1 once if you haven't).\n\nDetails: ${err.message}`
    );
    app.quit();
  });
}

function stopPhpServer() {
  if (!phpProcess || serverWasAlreadyRunning) return;
  try {
    if (process.platform === 'win32') {
      // Kill the whole process tree so the hidden PHP server doesn't linger after the app closes.
      execSync(`taskkill /pid ${phpProcess.pid} /T /F`);
    } else {
      phpProcess.kill('SIGTERM');
    }
  } catch (e) {
    // Process may have already exited -- that's fine.
  }
}

function createLoadingWindow() {
  const win = new BrowserWindow({
    width: 420,
    height: 320,
    resizable: false,
    frame: false,
    icon: path.join(__dirname, 'assets', 'icon.png'),
    backgroundColor: '#0d9488',
    show: true,
  });
  win.loadFile(path.join(__dirname, 'loading.html'));
  return win;
}

async function createMainWindow() {
  projectRoot = await resolveProjectRoot();

  const loadingWin = createLoadingWindow();

  serverWasAlreadyRunning = await isServerUp();
  if (!serverWasAlreadyRunning) {
    startPhpServer();
  }

  try {
    await waitForServer();
  } catch (e) {
    loadingWin.close();
    dialog.showErrorBox(
      'PharmaCare could not start',
      'The local server did not respond in time.\n\n' +
        "This usually means PHP isn't installed/on PATH yet, or the selected project folder is missing dependencies. " +
        'Run installer.ps1 in the project folder once, then try again.'
    );
    app.quit();
    return;
  }

  mainWindow = new BrowserWindow({
    width: 1400,
    height: 900,
    minWidth: 1000,
    minHeight: 700,
    title: 'PharmaCare',
    icon: path.join(__dirname, 'assets', 'icon.png'),
    backgroundColor: '#ffffff',
    show: false,
    webPreferences: {
      contextIsolation: true,
      nodeIntegration: false,
    },
  });

  Menu.setApplicationMenu(null);

  mainWindow.once('ready-to-show', () => {
    loadingWin.close();
    mainWindow.maximize();
    mainWindow.show();
  });

  mainWindow.loadURL(URL);

  mainWindow.on('closed', () => {
    mainWindow = null;
  });
}

app.whenReady().then(() => {
  createMainWindow();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createMainWindow();
    }
  });
});

app.on('window-all-closed', () => {
  stopPhpServer();
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('before-quit', () => {
  stopPhpServer();
});
