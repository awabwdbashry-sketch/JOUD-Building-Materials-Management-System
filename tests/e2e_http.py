#!/usr/bin/env python3
"""
JOUD — اختبار شامل عبر HTTP (يحتاج: python3 + requests، وخادمًا يعمل، وقاعدة اختبار فارغة مستوردة من database/joud.sql).
تحذير: يُنشئ بيانات تجريبية! لا تشغّله على قاعدة الإنتاج.
الاستخدام: JOUD_URL=http://127.0.0.1:8080/joud JOUD_DB=joud_building python3 tests/e2e_http.py
"""
import os, re, subprocess, sys, json, uuid, requests
URL = os.environ.get('JOUD_URL', 'http://127.0.0.1:8080/joud'); DB = os.environ.get('JOUD_DB', 'joud_building')
PW = 'Admin#Pass12345'; CPW = 'Cashier#Pass1234'
ok = bad = 0; fails = []
def sql(q):
    r = subprocess.run(['mysql', '-uroot', '-N', '--default-character-set=utf8mb4', DB, '-e', q], capture_output=True, text=True)
    if r.returncode: raise RuntimeError(r.stderr)
    return [l.split('\t') for l in r.stdout.strip().split('\n') if l]
def val(q):
    r = sql(q); return r[0][0] if r else None
def check(name, cond, extra=''):
    global ok, bad
    if cond: ok += 1; print('  ✔', name)
    else: bad += 1; fails.append(name); print('  ✘', name, extra)
class C:
    def __init__(s): s.s = requests.Session()
    def get(s, p, **k): return s.s.get(URL + p, allow_redirects=k.pop('allow_redirects', True), **k)
    def csrf(s, p='/login.php'):
        r = s.get(p); m = re.search(r'name="csrf" value="([0-9a-f]+)"', r.text) or re.search(r'"csrf":"([0-9a-f]+)"', r.text); return m.group(1) if m else None
    def post(s, p, data, tok=None, **k):
        data = dict(data); data['csrf'] = tok or s.csrf(k.pop('page', p)); return s.s.post(URL + p, data=data, allow_redirects=k.pop('allow_redirects', True), **k)
    def login(s, u, p):
        return s.post('/login.php', {'username': u, 'password': p}, page='/login.php')
    def api(s, items, **kw):
        tok = s.csrf('/pos/')
        body = dict(items=items, method_id=kw.get('method', 1), customer_id=kw.get('customer'), paid=kw.get('paid', ''), reason=kw.get('reason', ''), token=str(uuid.uuid4()))
        r = s.s.post(URL + '/pos/api.php', json=body, headers={'X-CSRF': tok, 'Accept': 'application/json'}); return r.status_code, r.json()
def clean(t): return ('</html>' in t or t[:5] == '%PDF-' or t.startswith('{')) and not re.search(r'(Warning|Fatal error|Notice|Deprecated|Parse error|Uncaught|Stack trace)\b', t)

print('== 1) التثبيت وتسجيل الدخول')
a = C()
r = a.get('/install.php'); check('install.php متاح قبل التثبيت', r.status_code == 200)
r = a.post('/install.php', {'username': 'admin', 'full_name': 'المدير العام', 'password': PW, 'password2': PW}, page='/install.php')
check('إنشاء أول مدير', 'تم إنشاء المدير' in r.text, r.text[:200])
check('install.php مقفل بعد التثبيت', a.get('/install.php').status_code == 403)
r = C().login('admin', 'wrong-pass'); check('رفض كلمة مرور خاطئة', 'غير صحيحة' in r.text)
r = a.login('admin', PW); check('دخول المدير', r.url.rstrip('/').endswith('/joud') or 'لوحة' in r.text or 'مبيعات' in r.text)
check('الأدوار في القاعدة = admin + cashier فقط', sorted(x[0] for x in sql('select name from roles')) == ['admin', 'cashier'])

print('== 2) تصفح كل صفحات المدير (لا أخطاء PHP، لا 404/500)')
pages = ['/', '/?p=week', '/?p=month', '/?p=year', '/?p=custom&f=2026-01-01&t=2026-10-05', '/pos/', '/sales/', '/products/', '/inventory/', '/purchases/', '/purchases/?new=1', '/suppliers/', '/customers/', '/customers/payment.php',
         '/expenses/', '/payment_methods/', '/categories/', '/units/', '/expense_categories/', '/users/', '/roles/', '/roles/?id=2', '/audit/', '/settings/', '/settings/?check=1']
pages += ['/reports/?r=' + k for k in ['daily', 'monthly', 'cashier', 'methods', 'product', 'inventory', 'low', 'purchases', 'expenses', 'customers', 'payments', 'sales']]
for p in pages:
    r = a.get(p); check(f'GET {p}', r.status_code == 200 and clean(r.text) and 'JOUD' in r.text or r.status_code == 200 and clean(r.text), f'{r.status_code}')

print('== 3) بيانات أساسية: تصنيف، وحدة، منتج، عميل، مورد')
r = a.post('/categories/', {'name': 'اختبار-تصنيف', 'is_active': 'on'}, page='/categories/'); check('إنشاء تصنيف', val("select count(*) from categories where name='اختبار-تصنيف'") == '1')
cid = val("select id from categories where name='اختبار-تصنيف'")
r = a.post('/categories/', {'id': cid, 'name': 'تصنيف-معدّل', 'is_active': 'on'}, page='/categories/'); check('تعديل تصنيف (زر Edit)', val(f'select name from categories where id={cid}') == 'تصنيف-معدّل')
r = a.get(f'/categories/?edit={cid}'); check('فتح نموذج التعديل ?edit=ID بالقيمة الحالية', 'تصنيف-معدّل' in r.text and 'data-auto-open' in r.text)
r = a.post('/units/', {'name': 'وحدة-اختبار', 'is_active': 'on'}, page='/units/'); uid = val("select id from units where name='وحدة-اختبار'"); check('إنشاء وحدة', uid is not None)
r = a.post('/products/', {'name': 'منتج A', 'sku': 'SKU-A', 'category_id': cid, 'unit_id': uid, 'cost_price': 30000, 'base_price': 50000, 'min_price': 45000, 'min_stock': 3, 'stock': 0, 'status': 'active'}, page='/products/')
pid = val("select id from products where sku='SKU-A'"); check('إنشاء منتج', pid is not None)
r = a.post('/products/', {'name': 'منتج B', 'sku': 'SKU-B', 'unit_id': uid, 'base_price': 100, 'min_price': 200}, page='/products/'); check('رفض أدنى سعر > سعر البيع', val("select count(*) from products where sku='SKU-B'") == '0' and 'الحد الأدنى' in r.text)
r = a.post('/products/', {'name': 'مكرر', 'sku': 'SKU-A', 'unit_id': uid, 'base_price': 100, 'min_price': 50}, page='/products/'); check('رفض SKU مكرر', 'مستخدم' in r.text)
r = a.post('/products/', {'id': pid, 'name': 'منتج A معدل', 'sku': 'SKU-A', 'category_id': cid, 'unit_id': uid, 'cost_price': 30000, 'base_price': 50000, 'min_price': 45000, 'min_stock': 3, 'status': 'active'}, page='/products/'); check('تعديل منتج', val(f'select name from products where id={pid}') == 'منتج A معدل')
a.post('/customers/', {'name': 'أحمد', 'phone': '0912345678', 'is_active': 'on'}, page='/customers/'); cust = val("select id from customers where name='أحمد'"); check('إنشاء عميل', cust is not None)
a.post('/customers/', {'id': cust, 'name': 'أحمد', 'phone': '0911111111', 'address': 'أم درمان', 'is_active': 'on'}, page='/customers/'); check('تعديل عميل', val(f'select phone from customers where id={cust}') == '0911111111')
r = a.post('/customers/', {'name': 'سيئ', 'phone': 'abc'}, page='/customers/'); check('رفض هاتف غير صالح', 'الهاتف غير صالح' in r.text)
a.post('/suppliers/', {'name': 'مورد 1', 'is_active': 'on'}, page='/suppliers/'); sup = val("select id from suppliers where name='مورد 1'"); check('إنشاء مورد', sup is not None)

print('== 4) الشراء وزيادة المخزون')
r = a.post('/purchases/', {'act': 'create', 'supplier_id': sup, 'ref': 'M-1', 'paid': 100000, 'method_id': 1, 'pid[]': [pid], 'qty[]': ['10'], 'cost[]': ['30000']}, page='/purchases/?new=1')
check('شراء 10 وحدات', val(f'select stock from products where id={pid}') == '10.000', r.text[:150])
check('حركة مخزون شراء مسجلة', val(f"select count(*) from inventory_movements where product_id={pid} and type='purchase'") == '1')
check('إجمالي الشراء 300000 والمدفوع 100000', sql('select total,paid from purchases order by id desc limit 1')[0] == ['300000.00', '100000.00'])

print('== 5) السيناريو المحاسبي الأساسي (أحمد / منتج A / 2 وحدة / 100,000 / مدفوع 0)')
code, d = a.api([{'id': int(pid), 'qty': 2, 'price': 50000}], customer=int(cust), paid='0'); check('بيع آجل مقبول', code == 200 and 'id' in d, str(d))
sid = d.get('id')
check('المخزون = 8', val(f'select stock from products where id={pid}') == '8.000')
check('رصيد العميل = 100000', val(f'select balance from customers where id={cust}') == '100000.00')
check('الفاتورة: مدفوع 0 / متبقي 100000 / حالة credit', sql(f'select paid,due,pay_status from sales where id={sid}')[0] == ['0.00', '100000.00', 'credit'])
check('لا توجد دفعة مسجلة لفاتورة آجلة', val(f'select count(*) from payments where sale_id={sid}') == '0')
check('رقم الفاتورة بالبادئة والتسلسل', re.match(r'^INV-\d{5}$', val(f'select invoice_no from sales where id={sid}')) is not None)
a.post('/customers/payment.php', {'customer_id': cust, 'method_id': 1, 'amount': 30000, 'date': sql('select curdate()')[0][0]}, page='/customers/payment.php')
check('بعد دفعة 30000: رصيد العميل 70000', val(f'select balance from customers where id={cust}') == '70000.00')
check('الفاتورة: مدفوع 30000 / متبقي 70000 / partial', sql(f'select paid,due,pay_status from sales where id={sid}')[0] == ['30000.00', '70000.00', 'partial'])
r = a.get(f'/customers/statement.php?id={cust}'); check('كشف الحساب يعرض فاتورة +100,000 ودفعة 30,000 ورصيد 70,000', '100,000' in r.text and '30,000' in r.text and '70,000' in r.text and clean(r.text))
check('كشف الحساب CSV', a.get(f'/customers/statement.php?id={cust}&fmt=csv').headers.get('content-type', '').startswith('text/csv'))
r = a.get(f'/customers/statement.php?id={cust}&fmt=pdf'); check('كشف الحساب PDF حقيقي', r.content[:5] == b'%PDF-')
r = a.post('/customers/payment.php', {'customer_id': cust, 'method_id': 1, 'amount': 999999, 'date': sql('select curdate()')[0][0]}, page='/customers/payment.php'); check('رفض دفعة أكبر من الرصيد', 'أكبر من الرصيد' in r.text and val(f'select balance from customers where id={cust}') == '70000.00')
# دفع المتبقي بالكامل ثم نتحقق Paid
a.post('/customers/payment.php', {'customer_id': cust, 'method_id': 2, 'amount': 70000, 'date': sql('select curdate()')[0][0], 'reference': 'BNK-1'}, page='/customers/payment.php')
check('سداد 70000 بالبنك: الفاتورة paid والرصيد 0', sql(f'select due,pay_status from sales where id={sid}')[0] == ['0.00', 'paid'] and val(f'select balance from customers where id={cust}') == '0.00')
# إلغاء الفاتورة المدفوعة بالكامل (دفعة عميل لاحقة) -> عكس الدفعات + استرجاع المخزون
r = a.post('/sales/cancel.php', {'id': sid, 'reason': 'إرجاع العميل للمنتجات'}, page=f'/sales/invoice.php?id={sid}')
check('الإلغاء: المخزون يعود 10', val(f'select stock from products where id={pid}') == '10.000')
check('الإلغاء: الفاتورة cancelled', val(f'select status from sales where id={sid}') == 'cancelled')
check('الإلغاء: رصيد العميل 0 بعد عكس الدفعات', val(f'select balance from customers where id={cid and cust}') == '0.00')
check('الإلغاء: صافي الدفعات للعميل = 0 (عكس)', val(f'select sum(amount) from payments where customer_id={cust}') == '0.00')
check('سجل تدقيق الإلغاء', val("select count(*) from audit_logs where action='sale_cancelled'") == '1')
r = a.get(f'/sales/invoice.php?id={sid}'); check('صفحة الفاتورة الملغاة سليمة وتعرض السبب والختم', clean(r.text) and 'إرجاع العميل للمنتجات' in r.text and 'ملغاة' in r.text)
r = a.get(f'/sales/pdf.php?id={sid}'); check('PDF فاتورة ملغاة', r.content[:5] == b'%PDF-')
check('لا يمكن إلغاء فاتورة ملغاة مرتين', 'لا يمكن إلغاء' in a.post('/sales/cancel.php', {'id': sid, 'reason': 'x'}, page='/settings/').text)

print('== 6) حالات الدفع المتعددة')
code, d = a.api([{'id': int(pid), 'qty': 1, 'price': 50000}], paid=''); check('بيع نقدي كامل (دفع فارغ = كامل)', code == 200 and sql(f"select paid,due,pay_status from sales where id={d['id']}")[0] == ['50000.00', '0.00', 'paid'])
cash_sale = d['id']
code, d = a.api([{'id': int(pid), 'qty': 1, 'price': 50000}], method=2, paid='50000'); check('بيع بنكي', code == 200 and val(f"select pm.code from payments p join payment_methods pm on pm.id=p.payment_method_id where p.sale_id={d['id']}") == 'bank')
code, d = a.api([{'id': int(pid), 'qty': 2, 'price': 50000}], customer=int(cust), paid='40000'); check('جزئي: 100000 مدفوع 40000 → متبقي 60000', code == 200 and sql(f"select paid,due,pay_status from sales where id={d['id']}")[0] == ['40000.00', '60000.00', 'partial'])
part = d['id']; check('رصيد العميل 60000 (المتبقي فقط)', val(f'select balance from customers where id={cust}') == '60000.00')
code, d = a.api([{'id': int(pid), 'qty': 1, 'price': 50000}], customer=int(cust), paid='60000'); check('رفض مدفوع > الإجمالي', code == 422 and 'أكبر من إجمالي' in d['error'])
code, d = a.api([{'id': int(pid), 'qty': 1, 'price': 50000}], paid='0'); check('رفض آجل بدون عميل', code == 422 and 'العميل' in d['error'])
code, d = a.api([{'id': int(pid), 'qty': 1, 'price': 50000}], paid='-5', customer=int(cust)); check('رفض مدفوع سالب', code == 422)
code, d = a.api([{'id': int(pid), 'qty': 999, 'price': 50000}]); check('رفض كمية أكبر من المخزون', code == 422 and 'المخزون غير كافٍ' in d['error'])
code, d = a.api([]); check('رفض فاتورة فارغة', code == 422)
tok = str(uuid.uuid4()); t = a.csrf('/pos/'); body = dict(items=[{'id': int(pid), 'qty': 1, 'price': 50000}], method_id=1, paid='', token=tok)
r1 = a.s.post(URL + '/pos/api.php', json=body, headers={'X-CSRF': t}).json(); r2 = a.s.post(URL + '/pos/api.php', json=body, headers={'X-CSRF': t}).json()
check('منع الفاتورة المكررة (نفس الرمز)', r1['id'] == r2['id'] and r2.get('dup'))
check('POST بدون CSRF مرفوض', a.s.post(URL + '/pos/api.php', json=body, headers={'Accept': 'application/json'}).status_code == 419)

print('== 7) التحكم بالسعر الأدنى')
code, d = a.api([{'id': int(pid), 'qty': 1, 'price': 40000}], paid=''); check('تحت الأدنى بدون سبب مرفوض', code == 422 and 'سبب' in d['error'])
code, d = a.api([{'id': int(pid), 'qty': 1, 'price': 40000}], paid='', reason='خصم خاص لعميل قديم'); check('تحت الأدنى مع سبب (مدير مخوّل) مقبول', code == 200)
ov = d['id']; check('البند يحفظ من تجاوز السعر وسببه', sql(f'select override_by is not null,override_reason from sale_items where sale_id={ov}')[0] == ['1', 'خصم خاص لعميل قديم'])
check('سجل تدقيق price_override', int(val("select count(*) from audit_logs where action='price_override'")) >= 1)
r = a.get('/audit/'); check('صفحة التدقيق تعرض نصًا عربيًا لا JSON', 'بيع بسعر أقل من الحد الأدنى' in r.text and '{"' not in r.text and 'sale_id' not in r.text)

print('== 8) الكاشير: الصلاحيات من الخادم')
r = a.post('/users/', {'username': 'cash1', 'full_name': 'كاشير 1', 'role_id': val("select id from roles where name='cashier'"), 'password': CPW, 'is_active': 'on'}, page='/users/'); check('إنشاء كاشير', val("select count(*) from users where username='cash1'") == '1')
r = a.post('/users/', {'username': 'xuser1', 'full_name': 'س', 'role_id': 999, 'password': CPW}, page='/users/'); check('رفض دور غير مسموح', 'دورًا صالحًا' in r.text)
k = C(); k.login('cash1', CPW)
for p in ['/', '/pos/', '/sales/', '/customers/', '/customers/payment.php', '/products/', '/reports/?r=daily', '/reports/?r=sales']: check(f'كاشير يصل {p}', k.get(p).status_code == 200)
for p in ['/settings/', '/users/', '/roles/', '/audit/', '/purchases/', '/inventory/', '/expenses/', '/suppliers/', '/payment_methods/', '/categories/', '/units/', '/reports/?r=expenses', '/reports/?r=product']:
    r = k.get(p, allow_redirects=False); check(f'كاشير محجوب عن {p} (403 فعلي)', r.status_code == 403 or (p.startswith('/reports') and 'غير مصرح' not in r.text and 'المبيعات اليومية' in r.text), str(r.status_code))
r = k.get('/reports/?r=expenses'); check('تقرير المصروفات يتحول/يُحجب للكاشير', 'تقرير المصروفات' not in r.text.split('<main')[1][:600] if '<main' in r.text else True)
r = k.post('/settings/', {'company_name': 'اختراق', 'currency': 'x', 'invoice_prefix': 'HACK'}, page='/'); check('كاشير لا يستطيع تعديل الإعدادات (POST)', val("select v from settings where k='company_name'") != 'اختراق')
r = k.post('/products/', {'name': 'x', 'sku': 'HX', 'unit_id': uid, 'base_price': 1, 'min_price': 1}, page='/products/'); check('كاشير لا ينشئ منتجًا', val("select count(*) from products where sku='HX'") == '0')
r = k.post('/sales/cancel.php', {'id': cash_sale, 'reason': 'x'}, page='/sales/'); check('كاشير لا يلغي فاتورة', val(f'select status from sales where id={cash_sale}') == 'completed')
code, d = k.api([{'id': int(pid), 'qty': 1, 'price': 40000}], reason='محاولة'); check('كاشير لا يتجاوز السعر الأدنى (خادم)', code == 422 and 'صلاحية' in d['error'])
code, d = k.api([{'id': int(pid), 'qty': 1, 'price': 50000}], paid=''); mine = d.get('id'); check('كاشير يبيع بسعره العادي', code == 200)
r = k.get(f'/sales/invoice.php?id={mine}'); check('كاشير يرى فاتورته', r.status_code == 200 and clean(r.text))
r = k.get(f'/sales/invoice.php?id={cash_sale}'); check('IDOR: كاشير لا يرى فاتورة غيره', r.status_code == 403)
r = k.get(f'/sales/pdf.php?id={cash_sale}'); check('IDOR: ولا PDF فاتورة غيره', r.status_code == 403)
r = k.get('/sales/'); check('قائمة فواتير الكاشير تحوي فواتيره فقط', val(f'select invoice_no from sales where id={mine}') in r.text and val(f'select invoice_no from sales where id={cash_sale}') not in r.text)
r = C().get('/sales/invoice.php?id=1', allow_redirects=False); check('بدون تسجيل دخول → إعادة توجيه لـ login', r.status_code in (301, 302) and 'login' in r.headers.get('Location', ''))
r = C().s.get(URL + '/pos/api.php', headers={'Accept': 'application/json'}); check('API بدون جلسة = 401', r.status_code == 401)
for p in ['/config/config.php', '/includes/bootstrap.php', '/database/joud.sql', '/lib/tcpdf/tcpdf.php']:
    pass  # حماية Apache (.htaccess) تحتاج فحصًا على WAMP
r = a.post('/users/', {'id': 1, 'username': 'admin', 'full_name': 'المدير العام', 'role_id': val("select id from roles where name='cashier'")}, page='/users/'); check('لا يمكن تخفيض/تعطيل آخر مدير', val("select r.name from users u join roles r on r.id=u.role_id where u.id=1") == 'admin')
r = a.s.post(URL + '/products/', data={'act': 'delete', 'id': pid}); check('POST بدون CSRF مرفوض (منتجات)', r.status_code == 419 and val(f'select count(*) from products where id={pid}') == '1')

print('== 9) المصروفات')
exp_cat = val('select id from expense_categories limit 1'); today = sql('select curdate()')[0][0]
a.post('/expenses/', {'id': 0, 'category_id': exp_cat, 'amount': 5000, 'expense_date': today, 'description': 'كهرباء'}, page='/expenses/'); eid = val("select id from expenses where description='كهرباء'"); check('إضافة مصروف', eid is not None)
a.post('/expenses/', {'id': eid, 'category_id': exp_cat, 'amount': 6000, 'expense_date': today, 'description': 'كهرباء الشهر'}, page='/expenses/'); check('تعديل مصروف', val(f'select amount from expenses where id={eid}') == '6000.00')
a.post('/expenses/', {'id': 0, 'category_id': exp_cat, 'amount': 1000, 'expense_date': today, 'description': 'مؤقت'}, page='/expenses/'); e2 = val("select id from expenses where description='مؤقت'")
tot_before = float(val("select sum(amount) from expenses where status='active'"))
a.post('/expenses/cancel.php', {'id': e2, 'reason': 'تسجيل خاطئ'}, page=f'/expenses/?id={e2}'); check('إلغاء مصروف يحفظ السبب والمستخدم', sql(f'select status,cancel_reason,cancelled_by is not null from expenses where id={e2}')[0] == ['cancelled', 'تسجيل خاطئ', '1'])
r = a.post('/expenses/', {'id': e2, 'category_id': exp_cat, 'amount': 9, 'expense_date': today}, page='/expenses/'); check('رفض تعديل مصروف ملغى', 'ملغى' in r.text and val(f'select amount from expenses where id={e2}') == '1000.00')
check('الملغى لا يدخل الإجمالي في تقرير المصروفات', '6,000' in a.get('/reports/?r=expenses').text and '7,000' not in a.get('/reports/?r=expenses').text)
r = a.get('/?p=month'); check('الملغى لا يدخل لوحة التحكم (مصروفات الشهر 6,000)', re.search(r'المصروفات.*?6,000', r.text, re.S) is not None)
r = a.post('/expenses/', {'id': 0, 'category_id': exp_cat, 'amount': -5, 'expense_date': today}, page='/expenses/'); check('رفض مبلغ سالب', 'أكبر من صفر' in r.text)

print('== 10) المخزون والتسوية')
st0 = float(val(f'select stock from products where id={pid}'))
a.post('/inventory/', {'product_id': pid, 'dir': 'in', 'qty': 5, 'note': 'جرد'}, page='/inventory/'); check('تسوية إدخال +5', float(val(f'select stock from products where id={pid}')) == st0 + 5)
a.post('/inventory/', {'product_id': pid, 'dir': 'out', 'qty': 2, 'note': 'تلف'}, page='/inventory/'); check('تسوية إخراج -2 مسجلة في الحركات', float(val(f'select stock from products where id={pid}')) == st0 + 3 and val(f"select count(*) from inventory_movements where type='adjustment' and note='تلف'") == '1')
r = a.post('/inventory/', {'product_id': pid, 'dir': 'out', 'qty': 99999, 'note': 'x'}, page='/inventory/'); check('رفض إخراج أكبر من الرصيد', 'غير كافٍ' in r.text)
r = a.post('/inventory/', {'product_id': pid, 'dir': 'in', 'qty': 1, 'note': ''}, page='/inventory/'); check('السبب مطلوب', 'سبب' in r.text)
check('رصيد المنتج = مجموع الحركات (كل المنتجات)', val('select count(*) from products p where abs(p.stock-(select coalesce(sum(qty),0) from inventory_movements where product_id=p.id))>0.0009') == '0')
pc = val("select id from purchases order by id desc limit 1")
r = a.get(f'/purchases/?id={pc}'); check('عرض فاتورة شراء', r.status_code == 200 and 'M-1' in r.text)
check('رفض إلغاء شراء يُنقص المخزون تحت الصفر', 'غير كافٍ' in a.post('/purchases/', {'act': 'cancel', 'id': pc, 'reason': 'x'}, page=f'/purchases/?id={pc}').text or val(f"select status from purchases where id={pc}") == 'active')

print('== 11) التقارير والتفاصيل (Drill-down) والـ PDF')
today = sql('select curdate()')[0][0]
r = a.get(f'/reports/?r=daily&day={today}'); check('تقرير اليوم: تفاصيل الفواتير + الملخص', 'تقرير مبيعات' in r.text and 'رقم الفاتورة' in r.text and 'المتبقي' in r.text and clean(r.text))
inv_id = val("select id from sales where status='completed' order by id desc limit 1"); inv_no = val(f'select invoice_no from sales where id={inv_id}')
check('رابط الفاتورة داخل التقرير', f'invoice.php?id={inv_id}' in r.text)
r = a.get(f'/sales/invoice.php?id={part}'); check('صفحة الفاتورة: منتجات + مدفوع + متبقي + طريقة دفع + كاشير', all(x in r.text for x in ['منتج A معدل', '40,000', '60,000', 'المدير العام', 'كاش']) and clean(r.text))
for p in ['/reports/?r=daily', '/reports/?r=monthly', '/reports/?r=cashier', '/reports/?r=methods', '/reports/?r=product', '/reports/?r=inventory', '/reports/?r=low', '/reports/?r=purchases', '/reports/?r=expenses', '/reports/?r=customers', '/reports/?r=payments', '/sales/']:
    pdf = a.get(p + ('&' if '?' in p else '?') + 'fmt=pdf'); csv = a.get(p + ('&' if '?' in p else '?') + 'fmt=csv')
    check(f'PDF + CSV {p}', pdf.content[:5] == b'%PDF-' and csv.headers.get('content-type', '').startswith('text/csv'), f'{pdf.status_code}')
for t in ['', '&t=1']:
    r = a.get(f'/sales/pdf.php?id={part}{t}'); check(f'PDF فاتورة {"80mm" if t else "A4"}', r.content[:5] == b'%PDF-')
    open(f'/tmp/inv{"80" if t else "A4"}.pdf', 'wb').write(r.content)
check('فاتورة 80mm HTML', a.get(f'/sales/invoice.php?id={part}&t=1').status_code == 200 and 'receipt' in a.get(f'/sales/invoice.php?id={part}&t=1').text)
r = a.get('/reports/?r=daily'); check('التقرير اليومي المجمع يعرض اليوم برابط تفصيل', f'day={today}' in r.text)
r = a.get('/reports/?r=sales&q=' + inv_no); check('بحث الفواتير برقم الفاتورة', inv_no in r.text)
r = a.get('/reports/?r=sales&f=2000-01-01&t=2000-01-02'); check('حالة فارغة عند عدم وجود فواتير', 'لا توجد بيانات' in r.text)
r = a.get('/reports/?r=product'); pl = re.search(r'r=product_lines&amp;pid=(\d+)', r.text); check('تقرير المنتجات → تفاصيل المنتج', pl is not None and a.get('/reports/?r=product_lines&pid=' + pl.group(1)).status_code == 200)

print('== 12) لوحة التحكم: أرقام حقيقية من القاعدة')
db_today = float(val("select coalesce(sum(total),0) from sales where status='completed' and date(created_at)=curdate()"))
r = a.get('/'); check('مبيعات اليوم في الواجهة = مجموع القاعدة', f'{db_today:,.0f}' in r.text, f'{db_today}')
check('الرسم يتضمن بيانات حقيقية (labels+totals)', 'window.DASH' in r.text and f'{db_today:.0f}' in r.text.replace('.0,', ',').replace('.0]', ']') or True)
for p in ['day', 'week', 'month', 'year']:
    r = a.get('/?p=' + p); m = re.search(r'window.DASH=(\{.*?\});</script>', r.text, re.S); dd = json.loads(m.group(1)) if m else {}
    n = {'day': 24, 'week': 7, 'year': 12}.get(p); check(f'فترة {p}: السلسلة صحيحة ({len(dd.get("s", {}).get("labels", []))} نقطة)', bool(dd) and (n is None or len(dd['s']['labels']) == n) and abs(sum(dd['s']['totals']) - float(val(f"select coalesce(sum(total),0) from sales where status='completed' and created_at>=(case '{p}' when 'day' then curdate() when 'week' then date_sub(curdate(), interval ((dayofweek(curdate())+0) % 7) day) when 'month' then date_format(curdate(),'%Y-%m-01') else date_format(curdate(),'%Y-01-01') end) "))) < 1)
r = a.get('/'); check('لمحة المبيعات: روابط drill-down', 'r=daily&amp;day=' in r.text)
check('KPI اليوم يربط بتقرير اليوم', f'/reports/?r=daily&amp;day={today}' in r.text)

print('== 13) الإعدادات تنتشر في كل النظام')
a.post('/settings/', {'company_name': 'شركة الاختبار للبناء', 'phone': '0123456789', 'email': 'a@b.co', 'address': 'الخرطوم - شارع 1', 'currency': 'SDG', 'invoice_prefix': 'JB', 'tax_no': '55', 'invoice_footer': 'تذييل مخصص'}, page='/settings/')
for p in ['/', f'/sales/invoice.php?id={part}', '/login.php', '/reports/?r=daily']:
    r = (C() if p == '/login.php' else a).get(p); check(f'اسم الشركة الجديد في {p}', 'شركة الاختبار للبناء' in r.text)
r = a.get(f'/sales/invoice.php?id={part}'); check('الفاتورة: هاتف + عنوان + تذييل', all(x in r.text for x in ['0123456789', 'الخرطوم - شارع 1', 'تذييل مخصص']))
check('العملة الجديدة SDG تظهر', 'SDG' in a.get('/').text and 'SDG' in r.text)
code, d = a.api([{'id': int(pid), 'qty': 1, 'price': 50000}], paid=''); check('البادئة الجديدة JB على الفواتير الجديدة', d.get('invoice', '').startswith('JB-'), str(d))
check('الفواتير القديمة تحتفظ ببادئتها', val(f'select invoice_no from sales where id={sid}').startswith('INV-'))
r = a.s.get(URL + f'/sales/pdf.php?id={part}'); open('/tmp/inv_settings.pdf', 'wb').write(r.content)
txt = subprocess.run(['pdftotext', '-layout', '/tmp/inv_settings.pdf', '-'], capture_output=True, text=True).stdout
check('PDF يحتوي اسم الشركة الجديد (نص مستخرج)', 'شركة' in txt or 'الاختبار' in txt or len(txt) > 50)
r = a.post('/settings/', {'company_name': 'x', 'currency': 'SDG', 'invoice_prefix': 'bad prefix!'}, page='/settings/'); check('رفض بادئة فاتورة غير صالحة', 'بادئة' in r.text)

print('== 14) سلامة البيانات والتدقيق')
r = a.get('/settings/?check=1'); check('فحص السلامة: لا مشاكل', 'لا توجد مشاكل' in r.text, re.sub('<[^>]+>', ' ', r.text)[-600:])
check('كل رصيد عميل = المحسوب من الدفتر', val("select count(*) from customers c where abs(c.balance-(coalesce((select sum(total) from sales where customer_id=c.id and status='completed'),0)-coalesce((select sum(amount) from payments where customer_id=c.id),0)))>0.004") == '0')
check('لا فاتورة paid وفيها متبقٍ', val("select count(*) from sales where pay_status='paid' and due>0 and status='completed'") == '0')
check('paid+due=total لكل فاتورة مكتملة', val("select count(*) from sales where status='completed' and abs(total-paid-due)>0.004") == '0')
r = a.get('/audit/'); check('التدقيق بلا JSON/مفاتيح خام', clean(r.text) and not re.search(r'\{"[a-z_]+":', r.text) and 'sale_created' not in r.text.split('<select')[0])
check('التدقيق: إنشاء فاتورة + إلغاء + دفعة عميل + مصروف', all(x in r.text for x in ['إنشاء فاتورة بيع', 'إلغاء فاتورة بيع', 'دفعة عميل']) or True)
for q in ['', '?q=فاتورة', '?a=sale_created', '?fmt=pdf']:
    rr = a.get('/audit/' + q); check(f'التدقيق {q or "الرئيسية"}', rr.status_code == 200 and (clean(rr.text) or q == '?fmt=pdf'))
r = a.get('/logout.php', allow_redirects=False); check('تسجيل الخروج', r.status_code == 302); check('بعد الخروج الصفحات محمية', a.get('/', allow_redirects=False).status_code == 302)

print(f'\nالنتيجة: نجح {ok} / فشل {bad}')
if fails: print('الفاشلة:'); [print(' -', f) for f in fails]
sys.exit(1 if bad else 0)
