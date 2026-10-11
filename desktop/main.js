'use strict';
/**
 * JOUD Desktop — غلاف سطح مكتب لنظام JOUD الحالي.
 * لا يحتوي أي منطق أعمال: يفتح نفس رابط WAMP (Apache + PHP + MySQL) داخل نافذة.
 */
const { app, BrowserWindow, Menu, Notification, dialog, ipcMain, screen, session, shell } = require('electron');
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');
const L = require('./lib');

let cfg = L.mergeConfig(null);
let win = null;
let quitting = false;
let wampTried = false;

const userFile = (n) => path.join(app.getPath('userData'), n);
const readJson = (f) => { try { return JSON.parse(fs.readFileSync(f, 'utf8')); } catch (_) { return null; } };

function loadConfig() {
  const f = userFile('config.json');
  const user = readJson(f);
  if (user === null && !fs.existsSync(f)) {
    // قالب أولي يسهّل تعديل الرابط لاحقًا؛ فشل الكتابة غير مؤثر
    try { fs.mkdirSync(path.dirname(f), { recursive: true }); fs.writeFileSync(f, JSON.stringify({ url: L.DEFAULTS.url, startWampIfDown: true, waitSeconds: 60 }, null, 2)); } catch (_) {}
  }
  cfg = L.mergeConfig(user);
}

/* ---------------- النافذة ---------------- */
function createWindow() {
  const st = readJson(userFile('window-state.json')) || {};
  const b = L.fitBounds(st, screen.getAllDisplays().map((d) => d.workArea));
  win = new BrowserWindow({
    ...b, minWidth: 1000, minHeight: 650, show: false, autoHideMenuBar: true, backgroundColor: '#f4f5f7', title: 'JOUD',
    webPreferences: { preload: path.join(__dirname, 'preload.js'), contextIsolation: true, nodeIntegration: false, sandbox: true, spellcheck: false, devTools: process.env.JOUD_DEVTOOLS === '1' },
  });
  if (st.maximized) win.maximize();
  win.once('ready-to-show', () => win.show());
  win.on('close', () => {
    try { fs.writeFileSync(userFile('window-state.json'), JSON.stringify({ ...win.getNormalBounds(), maximized: win.isMaximized() })); } catch (_) {}
  });
  win.on('closed', () => { win = null; });
  hardenContents(win.webContents);
  return win;
}

/** تقييد التنقل: داخل النظام فقط؛ الروابط الخارجية تُفتح في المتصفح الافتراضي */
function hardenContents(wc) {
  const external = (u) => { if (/^https?:\/\//i.test(u)) shell.openExternal(u); };
  wc.setWindowOpenHandler(({ url }) => {
    if (L.isInternal(url, cfg.url)) {
      // مثل target=_blank في الـ POS: نافذة ثانية بنفس الجلسة
      return { action: 'allow', overrideBrowserWindowOptions: { width: 1100, height: 760, autoHideMenuBar: true, parent: win || undefined, webPreferences: { contextIsolation: true, nodeIntegration: false, sandbox: true } } };
    }
    external(url); return { action: 'deny' };
  });
  wc.on('did-create-window', (child) => hardenContents(child.webContents));
  wc.on('will-navigate', (e, url) => {
    if (url.startsWith('file://') || L.isInternal(url, cfg.url)) return;
    e.preventDefault(); external(url);
  });
  wc.on('did-fail-load', (_e, code, desc, url, isMainFrame) => {
    if (isMainFrame && code !== -3 && L.isInternal(url, cfg.url)) showError('تعذّر الاتصال بالنظام', `${desc} (${code})`);
  });
  wc.on('context-menu', (_e, p) => {
    const t = [];
    if (p.isEditable) t.push({ label: 'قص', role: 'cut', enabled: p.editFlags.canCut }, { label: 'نسخ', role: 'copy', enabled: p.editFlags.canCopy }, { label: 'لصق', role: 'paste', enabled: p.editFlags.canPaste }, { type: 'separator' }, { label: 'تحديد الكل', role: 'selectAll' });
    else if (p.selectionText) t.push({ label: 'نسخ', role: 'copy' });
    if (t.length) Menu.buildFromTemplate(t).popup();
  });
  wc.on('before-input-event', (e, input) => {
    if (input.type !== 'keyDown') return;
    if (input.control && !input.shift && !input.alt && input.key.toLowerCase() === 'p') { e.preventDefault(); wc.print({ silent: false }); }
  });
}

function buildMenu() {
  Menu.setApplicationMenu(Menu.buildFromTemplate([
    { label: 'ملف', submenu: [{ label: 'إعادة تحميل', role: 'reload' }, { type: 'separator' }, { label: 'خروج', role: 'quit' }] },
    { label: 'تحرير', submenu: [{ label: 'قص', role: 'cut' }, { label: 'نسخ', role: 'copy' }, { label: 'لصق', role: 'paste' }, { label: 'تحديد الكل', role: 'selectAll' }] },
    { label: 'عرض', submenu: [{ label: 'تكبير', role: 'zoomIn', accelerator: 'CommandOrControl+=' }, { label: 'تصغير', role: 'zoomOut' }, { label: 'الحجم الأصلي', role: 'resetZoom' }, { type: 'separator' }, { label: 'ملء الشاشة', role: 'togglefullscreen' }] },
  ]));
}

/* ---------------- التشغيل ---------------- */
const status = (t) => { if (win && !win.isDestroyed()) win.webContents.send('status', t); };
function showError(title, detail) {
  if (!win || win.isDestroyed()) return;
  win.loadFile(path.join(__dirname, 'pages', 'error.html')).then(() => {
    win.webContents.send('error-info', { title, detail, url: cfg.url, config: userFile('config.json') });
  }).catch(() => {});
}

function tryStartWamp() {
  if (wampTried || !cfg.startWampIfDown) return false;
  wampTried = true;
  const exe = L.findWampManager(cfg.wampManagerPaths);
  if (!exe) return false;
  try { spawn(exe, [], { detached: true, stdio: 'ignore', cwd: path.dirname(exe) }).unref(); return true; } catch (_) { return false; }
}

async function start() {
  if (!win) createWindow();
  await win.loadFile(path.join(__dirname, 'pages', 'loading.html'));
  status('جارٍ التحقق من تشغيل الخادم…');
  let first = await L.waitUntilReady(cfg.url, { seconds: 3, intervalMs: 500 });
  if (!first.ok && !first.apacheUp && tryStartWamp()) status('جارٍ تشغيل WAMP… قد يستغرق ذلك دقيقة');
  const r = first.ok ? first : await L.waitUntilReady(cfg.url, {
    seconds: cfg.waitSeconds,
    onWaiting: (s, el) => status(s === null ? `بانتظار تشغيل Apache… (${el} ث)` : `Apache يعمل، بانتظار MySQL/PHP… (${el} ث)`),
  });
  if (!win || win.isDestroyed()) return;
  if (r.ok || r.apacheUp) { await win.loadURL(cfg.url).catch(() => {}); return; }  // إن كان 5xx يعرض النظام رسالته الخاصة
  showError('تعذّر الوصول إلى نظام JOUD', 'لم يستجب الخادم. تأكد من تشغيل WAMP (أيقونة خضراء) ثم أعد المحاولة.');
}

/* ---------------- التنزيلات (PDF/CSV) ---------------- */
function setupDownloads() {
  session.defaultSession.on('will-download', (_e, item) => {
    item.setSaveDialogOptions({ defaultPath: path.join(app.getPath('downloads'), item.getFilename()) });
    item.once('done', (_ev, state) => {
      if (state !== 'completed') return;
      const p = item.getSavePath();
      if (Notification.isSupported()) {
        const n = new Notification({ title: 'JOUD', body: 'تم حفظ الملف: ' + path.basename(p) });
        n.on('click', () => shell.openPath(p)); n.show();
      }
    });
  });
  const deny = (_wc, _perm, cb) => cb && cb(false);
  session.defaultSession.setPermissionRequestHandler(deny);
  session.defaultSession.setPermissionCheckHandler(() => false);
}

/* ---------------- دورة حياة التطبيق ---------------- */
if (!app.requestSingleInstanceLock()) { app.quit(); }
else {
  app.on('second-instance', () => { if (win) { if (win.isMinimized()) win.restore(); win.focus(); } });
  ipcMain.on('retry', () => { wampTried = false; start(); });
  ipcMain.on('quit', () => app.quit());
  ipcMain.on('open-config', () => shell.openPath(userFile('config.json')));
  app.on('before-quit', () => { quitting = true; });
  app.on('window-all-closed', () => app.quit());
  app.whenReady().then(() => { loadConfig(); buildMenu(); setupDownloads(); createWindow(); start(); });
}
