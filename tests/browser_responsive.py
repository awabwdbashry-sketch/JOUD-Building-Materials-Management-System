#!/usr/bin/env python3
"""اختبار المتصفح (Playwright/Chromium): ظهور القائمة، فتح Offcanvas، عدم وجود تمرير أفقي، لقطات شاشة. يحتاج بيانات موجودة (شغّل e2e_http.py أولًا)."""
import os, sys, re
from playwright.sync_api import sync_playwright
URL = os.environ.get('JOUD_URL', 'http://127.0.0.1:8080/joud'); OUT = os.environ.get('OUT', '/tmp/shots'); os.makedirs(OUT, exist_ok=True)
PW = os.environ.get('JOUD_PW', 'Admin#Pass12345')
W = [320, 375, 390, 430, 480, 768, 1024, 1280, 1440, 1920]
PAGES = ['/', '/pos/', '/sales/', '/products/', '/customers/', '/reports/?r=daily', '/audit/', '/settings/', '/expenses/', '/users/', '/purchases/?new=1', '/customers/payment.php']
ok = bad = 0
def chk(n, c, x=''):
    global ok, bad
    if c: ok += 1
    else: bad += 1; print('  ✘', n, x)
with sync_playwright() as p:
    b = p.chromium.launch(args=['--no-sandbox'])
    for w in W:
        ctx = b.new_context(viewport={'width': w, 'height': 800}, locale='ar'); pg = ctx.new_page()
        errs = []; pg.on('pageerror', lambda e: errs.append(str(e)))
        pg.goto(URL + '/login.php'); pg.fill('#username', 'admin'); pg.fill('#password', PW); pg.click('button.btn-gold'); pg.wait_for_load_state('networkidle')
        for path in PAGES:
            pg.goto(URL + path); pg.wait_for_load_state('networkidle')
            ov = pg.evaluate('document.documentElement.scrollWidth - window.innerWidth')
            chk(f'{w}px {path} بلا تمرير أفقي', ov <= 1, f'overflow={ov}')
            if w < 992:
                chk(f'{w}px {path} زر القائمة ظاهر', pg.is_visible('[data-bs-target="#mobileNav"]'))
                chk(f'{w}px {path} الشريط الجانبي مخفي', not pg.is_visible('#sidebar'))
            else:
                chk(f'{w}px {path} الشريط الجانبي ظاهر', pg.is_visible('#sidebar'))
        if w < 992:
            pg.goto(URL + '/'); pg.click('[data-bs-target="#mobileNav"]'); pg.wait_for_timeout(500)
            n = pg.locator('#mobileNav .nav-link-j:visible').count(); chk(f'{w}px القائمة تُفتح وفيها روابط ({n})', n >= 15)
            vis = pg.is_visible('#mobileNav .nav-link-j >> text=نقطة البيع'); chk(f'{w}px رابط نقطة البيع ظاهر', vis)
            if w in (320, 390): pg.screenshot(path=f'{OUT}/menu_{w}.png')
            pg.click('#mobileNav .nav-link-j >> text=الفواتير'); pg.wait_for_load_state('networkidle'); chk(f'{w}px التنقل من القائمة يعمل', '/sales/' in pg.url)
        for path, name in [('/', 'dash'), ('/pos/', 'pos'), ('/sales/', 'sales'), ('/products/', 'products')]:
            if w in (320, 390, 1440): pg.goto(URL + path); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(400); pg.screenshot(path=f'{OUT}/{name}_{w}.png', full_page=(w != 1440))
        chk(f'{w}px بلا أخطاء JavaScript', not errs, str(errs[:2]))
        ctx.close()
    b.close()
print(f'المتصفح: نجح {ok} / فشل {bad}'); sys.exit(1 if bad else 0)
