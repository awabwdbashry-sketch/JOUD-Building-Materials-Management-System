'use strict';
/**
 * JOUD Desktop — منطق خالص (بدون Electron) حتى يمكن اختباره.
 * لا علاقة له بكود PHP أو قاعدة البيانات.
 */
const http = require('http');
const fs = require('fs');

const DEFAULTS = Object.freeze({
  // رابط النظام الحالي على WAMP — نفس الرابط الذي يُفتح به في المتصفح
  url: 'http://localhost/joud/',
  // إن لم يكن Apache يعمل: حاول تشغيل WAMP تلقائيًا
  startWampIfDown: true,
  wampManagerPaths: [
    'C:\\wamp64\\wampmanager.exe', 'D:\\wamp64\\wampmanager.exe', 'E:\\wamp64\\wampmanager.exe',
    'C:\\wamp\\wampmanager.exe', 'D:\\wamp\\wampmanager.exe',
  ],
  waitSeconds: 60,
});

/** دمج إعدادات المستخدم مع الافتراضية؛ أي قيمة غير صالحة تُهمل وتبقى الافتراضية */
function mergeConfig(user) {
  const c = { ...DEFAULTS, wampManagerPaths: [...DEFAULTS.wampManagerPaths] };
  if (!user || typeof user !== 'object') return c;
  if (typeof user.url === 'string') { const u = normalizeUrl(user.url); if (u) c.url = u; }
  if (typeof user.startWampIfDown === 'boolean') c.startWampIfDown = user.startWampIfDown;
  if (Array.isArray(user.wampManagerPaths)) {
    const p = user.wampManagerPaths.filter((x) => typeof x === 'string' && x.trim());
    if (p.length) c.wampManagerPaths = p;
  }
  if (Number.isFinite(user.waitSeconds)) c.waitSeconds = Math.min(300, Math.max(5, Math.round(user.waitSeconds)));
  return c;
}

/** يقبل http/https فقط، ويضمن شرطة نهائية؛ يعيد null إن كان غير صالح */
function normalizeUrl(s) {
  try {
    const u = new URL(String(s).trim());
    if (u.protocol !== 'http:' && u.protocol !== 'https:') return null;
    u.hash = '';
    if (!u.pathname.endsWith('/')) u.pathname += '/';
    return u.href;
  } catch (_) { return null; }
}

/** هل الرابط داخل نطاق النظام (نفس الأصل ونفس مسار المشروع)؟ */
function isInternal(target, base) {
  try {
    const t = new URL(target), b = new URL(base);
    if (t.origin !== b.origin) return false;
    const prefix = b.pathname.replace(/\/+$/, '');
    return prefix === '' || t.pathname === prefix || t.pathname.startsWith(prefix + '/');
  } catch (_) { return false; }
}

/** طلب واحد: يعيد رقم الحالة أو null عند فشل الاتصال */
function probe(url, timeoutMs = 3000) {
  return new Promise((resolve) => {
    let done = false;
    const fin = (v) => { if (!done) { done = true; resolve(v); } };
    try {
      const req = http.get(url, { timeout: timeoutMs, headers: { 'User-Agent': 'JOUD-Desktop-Probe' } }, (res) => {
        res.resume(); fin(res.statusCode);
      });
      req.on('timeout', () => { req.destroy(); fin(null); });
      req.on('error', () => fin(null));
    } catch (_) { fin(null); }
  });
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/**
 * ينتظر جاهزية النظام.
 *  - null  : Apache لا يعمل
 *  - 5xx   : Apache يعمل لكن PHP/MySQL غير جاهز بعد → استمر بالانتظار
 *  - غير ذلك: جاهز
 * onWaiting(status, elapsedSec) يُستدعى في كل محاولة فاشلة (لتحديث رسالة التحميل)
 */
async function waitUntilReady(url, { seconds = 60, intervalMs = 1500, probeFn = probe, onWaiting = () => {} } = {}) {
  const t0 = Date.now();
  let last = null;
  for (;;) {
    last = await probeFn(url);
    if (last !== null && last < 500) return { ok: true, status: last };
    const el = Math.round((Date.now() - t0) / 1000);
    onWaiting(last, el);
    if (el >= seconds) return { ok: false, status: last, apacheUp: last !== null };
    await sleep(intervalMs);
  }
}

function findWampManager(paths, existsFn = fs.existsSync) {
  for (const p of paths) { try { if (existsFn(p)) return p; } catch (_) { /* تجاهل */ } }
  return null;
}

/** إبقاء النافذة داخل شاشة ظاهرة؛ areas = قائمة workArea للشاشات */
function fitBounds(saved, areas, min = { width: 1000, height: 650 }) {
  const out = { width: Math.max(min.width, Number(saved?.width) || 1280), height: Math.max(min.height, Number(saved?.height) || 800) };
  if (Number.isFinite(saved?.x) && Number.isFinite(saved?.y)) {
    const vis = areas.some((a) => saved.x >= a.x - 20 && saved.y >= a.y - 20 && saved.x < a.x + a.width - 100 && saved.y < a.y + a.height - 100);
    if (vis) { out.x = saved.x; out.y = saved.y; }
  }
  return out;
}

module.exports = { DEFAULTS, mergeConfig, normalizeUrl, isInternal, probe, waitUntilReady, findWampManager, fitBounds };
