'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const http = require('http');
const L = require('../lib');

test('mergeConfig: افتراضي عند null/قيم غير صالحة', () => {
  assert.equal(L.mergeConfig(null).url, 'http://localhost/joud/');
  const c = L.mergeConfig({ url: 'file:///etc/passwd', waitSeconds: 'x', startWampIfDown: 'yes', wampManagerPaths: [] });
  assert.equal(c.url, 'http://localhost/joud/'); assert.equal(c.waitSeconds, 60); assert.equal(c.startWampIfDown, true);
  assert.ok(c.wampManagerPaths.length > 0);
});
test('mergeConfig: قيم صالحة + حدود waitSeconds', () => {
  const c = L.mergeConfig({ url: 'http://localhost:8080/joud', waitSeconds: 9999, startWampIfDown: false });
  assert.equal(c.url, 'http://localhost:8080/joud/'); assert.equal(c.waitSeconds, 300); assert.equal(c.startWampIfDown, false);
  assert.equal(L.mergeConfig({ waitSeconds: 1 }).waitSeconds, 5);
});
test('normalizeUrl: يرفض غير http/https', () => {
  assert.equal(L.normalizeUrl('javascript:alert(1)'), null); assert.equal(L.normalizeUrl('ftp://x'), null); assert.equal(L.normalizeUrl('not a url'), null);
});
test('isInternal: نفس الأصل والمسار فقط', () => {
  const b = 'http://localhost/joud/';
  assert.ok(L.isInternal('http://localhost/joud/', b)); assert.ok(L.isInternal('http://localhost/joud', b));
  assert.ok(L.isInternal('http://localhost/joud/sales/pdf.php?id=3&inline=1', b));
  assert.ok(!L.isInternal('http://localhost/phpmyadmin/', b)); assert.ok(!L.isInternal('http://localhost/joud-evil/', b));
  assert.ok(!L.isInternal('http://127.0.0.1/joud/', b)); assert.ok(!L.isInternal('https://example.com/joud/', b));
  assert.ok(!L.isInternal('http://localhost:8080/joud/', b)); assert.ok(!L.isInternal('garbage', b));
});
test('findWampManager', () => {
  assert.equal(L.findWampManager(['a', 'b', 'c'], (p) => p === 'b'), 'b');
  assert.equal(L.findWampManager(['a'], () => false), null);
});
test('fitBounds: يعيد النافذة لشاشة ظاهرة ويفرض حدًا أدنى', () => {
  const areas = [{ x: 0, y: 0, width: 1920, height: 1040 }];
  assert.deepEqual(L.fitBounds({ x: 100, y: 50, width: 1300, height: 800 }, areas), { x: 100, y: 50, width: 1300, height: 800 });
  const off = L.fitBounds({ x: 5000, y: 5000, width: 300, height: 200 }, areas);
  assert.equal(off.x, undefined); assert.equal(off.width, 1000); assert.equal(off.height, 650);
});

function server(handler) { return new Promise((r) => { const s = http.createServer(handler).listen(0, '127.0.0.1', () => r(s)); }); }
test('probe/waitUntilReady: 302 و404 = جاهز (Apache يعمل)', async () => {
  const s = await server((q, r) => { r.statusCode = q.url === '/joud/' ? 302 : 404; r.setHeader('Location', '/joud/login.php'); r.end(); });
  const u = `http://127.0.0.1:${s.address().port}/joud/`;
  assert.equal(await L.probe(u), 302);
  assert.deepEqual(await L.waitUntilReady(u, { seconds: 5 }), { ok: true, status: 302 });
  s.close();
});
test('waitUntilReady: 500 ثم 200 (انتظار MySQL) ينجح', async () => {
  let n = 0; const ticks = [];
  const s = await server((q, r) => { r.statusCode = ++n < 3 ? 500 : 200; r.end('x'); });
  const res = await L.waitUntilReady(`http://127.0.0.1:${s.address().port}/`, { seconds: 10, intervalMs: 50, onWaiting: (st) => ticks.push(st) });
  assert.equal(res.ok, true); assert.deepEqual(ticks, [500, 500]); s.close();
});
test('waitUntilReady: 500 دائم → فشل مع apacheUp=true', async () => {
  const s = await server((q, r) => { r.statusCode = 500; r.end('db down'); });
  const res = await L.waitUntilReady(`http://127.0.0.1:${s.address().port}/`, { seconds: 0, intervalMs: 10 });
  assert.deepEqual(res, { ok: false, status: 500, apacheUp: true }); s.close();
});
test('waitUntilReady: لا خادم → فشل مع apacheUp=false', async () => {
  const s = await server((q, r) => r.end()); const port = s.address().port; await new Promise((r) => s.close(r));
  const res = await L.waitUntilReady(`http://127.0.0.1:${port}/`, { seconds: 0, intervalMs: 10 });
  assert.deepEqual(res, { ok: false, status: null, apacheUp: false });
});
