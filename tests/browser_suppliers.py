#!/usr/bin/env python3
"""متصفح حقيقي: دفعة من الواجهة + تجاوب صفحات الموردين/المشتريات + أخطاء الكونسول. يعمل بعد e2e_suppliers.py."""
import os, re, subprocess, sys
from playwright.sync_api import sync_playwright
URL = os.environ.get('JOUD_URL', 'http://127.0.0.1:8082/joud'); DB = os.environ.get('JOUD_DB', 'joud_fresh'); PW = os.environ.get('JOUD_PW', 'Admin#Pass12345'); OUT = '/tmp/shots2'; os.makedirs(OUT, exist_ok=True)
def val(q): return subprocess.run(['mysql', '-uroot', '-N', '--default-character-set=utf8mb4', DB, '-e', q], capture_output=True, text=True).stdout.strip()
ok = bad = 0
def chk(n, c, x=''):
    global ok, bad
    if c: ok += 1
    else: bad += 1; print('  ✘', n, x)
pid = val("select id from purchases where status='active' and pay_status='partial' order by id desc limit 1"); sid = val(f'select supplier_id from purchases where id={pid}')
PAGES = ['/purchases/', f'/purchases/?id={pid}', '/purchases/?new=1', '/suppliers/', f'/suppliers/view.php?id={sid}', f'/suppliers/statement.php?id={sid}', '/reports/?r=suppliers', '/reports/?r=supplier_payments']
with sync_playwright() as p:
    b = p.chromium.launch(args=['--no-sandbox'])
    for w in [320, 375, 390, 430, 768, 1280]:
        ctx = b.new_context(viewport={'width': w, 'height': 850}, locale='ar'); pg = ctx.new_page(); errs = []
        pg.on('pageerror', lambda e: errs.append(str(e))); pg.on('console', lambda m: errs.append(m.text) if m.type == 'error' else None)
        pg.goto(URL + '/login.php'); pg.fill('#username', 'admin'); pg.fill('#password', PW); pg.click('button.btn-gold'); pg.wait_for_load_state('networkidle')
        for path in PAGES:
            pg.goto(URL + path); pg.wait_for_load_state('networkidle'); ov = pg.evaluate('document.documentElement.scrollWidth - window.innerWidth')
            chk(f'{w}px {path} بلا تمرير أفقي', ov <= 1, f'overflow={ov}')
        if w in (375, 1280):   # دفعة حقيقية من الواجهة
            pg.goto(URL + f'/purchases/?id={pid}'); pg.wait_for_load_state('networkidle')
            before = val(f'select paid from purchases where id={pid}'); rem = float(val(f'select total-paid from purchases where id={pid}'))
            chk(f'{w}px زر "تسجيل دفعة جديدة" ظاهر', pg.is_visible('text=تسجيل دفعة جديدة >> nth=0'))
            pg.click('button:has-text("تسجيل دفعة جديدة")'); pg.wait_for_selector('#payModal.show'); pg.wait_for_timeout(400)
            chk(f'{w}px النافذة تُفتح', pg.is_visible('#payModal input[name=amount]'))
            if w == 375: pg.screenshot(path=f'{OUT}/pay_modal_{w}.png')
            pg.fill('#payModal input[name=amount]', '1'); pg.select_option('#payModal select[name=method_id]', index=1); pg.fill('#payModal input[name=reference]', 'UI-' + str(w))
            pg.click('#payModal button.btn-gold'); pg.wait_for_load_state('networkidle')
            after = val(f'select paid from purchases where id={pid}')
            chk(f'{w}px الدفعة من الواجهة سُجّلت (+1)', float(after) == float(before) + 1, f'{before}->{after}')
            chk(f'{w}px رسالة نجاح بالعربية', 'تم تسجيل الدفعة' in pg.content())
            pg.goto(URL + f'/purchases/?id={pid}&pay=1'); pg.wait_for_selector('#payModal.show')
            pg.fill('#payModal input[name=amount]', str(rem + 1000)); pg.evaluate("document.querySelector('#payModal input[name=amount]').removeAttribute('max')"); pg.select_option('#payModal select[name=method_id]', index=1)
            pg.click('#payModal button.btn-gold'); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(400)
            chk(f'{w}px التجاوز يُرفض ويعيد فتح النافذة مع رسالة خطأ', 'أكبر من المتبقي' in pg.content() and pg.is_visible('#payModal.show'))
            if w == 1280: pg.screenshot(path=f'{OUT}/pay_err_{w}.png')
            for n, path in [('purchase', f'/purchases/?id={pid}'), ('supplier', f'/suppliers/view.php?id={sid}'), ('stmt', f'/suppliers/statement.php?id={sid}')]:
                pg.goto(URL + path); pg.wait_for_load_state('networkidle'); pg.screenshot(path=f'{OUT}/{n}_{w}.png', full_page=True)
        chk(f'{w}px بلا أخطاء كونسول/JavaScript', not errs, str(errs[:2]))
        ctx.close()
    b.close()
print(f'المتصفح (موردون/مشتريات): نجح {ok} / فشل {bad}'); sys.exit(1 if bad else 0)
