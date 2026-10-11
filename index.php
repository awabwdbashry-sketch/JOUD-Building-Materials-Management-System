<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/audit_format.php';
require __DIR__ . '/includes/dashboard_data.php';
need();
$me = me(); $analytics = can('view_analytics');
$perio = $_GET['p'] ?? 'day';
if (!in_array($perio, ['day', 'week', 'month', 'year', 'custom'], true)) $perio = 'day';
[$f, $t, $plabel, $kind] = period_range($perio, $_GET['f'] ?? null, $_GET['t'] ?? null);
$hr = (int)date('G'); $greet = $hr < 12 ? 'صباح الخير' : ($hr < 18 ? 'مساء الخير' : 'مساء النور');

/* روابط الاستعراض (Drill-down) */
$repSales = $kind === 'day' ? BASE . '/reports/?r=daily&day=' . $f : BASE . '/reports/?r=daily&f=' . $f . '&t=' . $t;
$range = '&f=' . $f . '&t=' . $t;
head('لوحة التحكم');

/* ---------------- ترويسة الترحيب ---------------- */
echo '<div class="hero mb-3"><div class="d-flex flex-wrap justify-content-between align-items-end gap-3"><div><h2>' . $greet . '، ' . e(explode(' ', $me['full_name'])[0]) . '</h2><p>' . e(company()) . ' — ' . ar_day(today()) . ' ' . d(today()) . '</p></div>';
if ($analytics) {
    echo '<div class="seg">';
    foreach (['day' => 'اليوم', 'week' => 'هذا الأسبوع', 'month' => 'هذا الشهر', 'year' => 'هذه السنة'] as $k => $l) echo '<a href="?p=' . $k . '" class="' . ($perio === $k ? 'on' : '') . '">' . $l . '</a>';
    echo '<a href="#customRange" data-bs-toggle="collapse" class="' . ($perio === 'custom' ? 'on' : '') . '">مخصص</a></div>';
}
echo '</div>';
if ($analytics) echo '<div class="collapse' . ($perio === 'custom' ? ' show' : '') . ' mt-3" id="customRange"><form class="d-flex flex-wrap gap-2 align-items-end"><input type="hidden" name="p" value="custom"><div><label class="small text-white-50">من</label><input type="date" name="f" class="form-control" value="' . e($f) . '" required></div><div><label class="small text-white-50">إلى</label><input type="date" name="t" class="form-control" value="' . e($t) . '" required></div><button class="btn btn-gold">عرض</button></form></div>';
$qa = [['pos/', 'بيع جديد', 'cart-check', 'create_sale', true], ['products/?new=1', 'إضافة منتج', 'box-seam', 'create_product', false], ['purchases/?new=1', 'تسجيل شراء', 'truck', 'create_purchase', false], ['customers/', 'إضافة عميل', 'person-plus', 'manage_customers', false], ['customers/payment.php', 'تسجيل دفعة', 'cash-coin', 'receive_customer_payment', false], ['expenses/', 'إضافة مصروف', 'wallet2', 'manage_expenses', false], ['reports/', 'التقارير', 'bar-chart-line', ['view_reports', 'view_sales_reports'], false]];
echo '<div class="quick mt-3">';
foreach ($qa as [$h, $l, $i, $pm, $pr]) if (is_array($pm) ? can_any($pm) : can($pm)) echo '<a class="' . ($pr ? 'primary' : '') . '" href="' . BASE . '/' . $h . '"><i class="bi bi-' . $i . '"></i> ' . $l . '</a>';
echo '</div></div>';

/* ---------------- لوحة الكاشير المبسّطة ---------------- */
if (!$analytics) {
    $t0 = today(); $u = (int)$me['id'];
    $m = q("SELECT COUNT(*) n,COALESCE(SUM(total),0) t,COALESCE(SUM(paid),0) pd,COALESCE(SUM(due),0) du FROM sales WHERE status='completed' AND user_id=? AND created_at>=? AND created_at<?", [$u, $t0, day_after($t0)])->fetch();
    $kp = [['مبيعاتي اليوم', cur($m['t']), 'cash-stack', '', BASE . '/sales/?f=' . $t0 . '&t=' . $t0], ['عدد فواتيري', num($m['n']), 'receipt', 't-ink', BASE . '/sales/?f=' . $t0 . '&t=' . $t0], ['المدفوع', cur($m['pd']), 'check2-circle', 't-ok', ''], ['المتبقي (آجل)', cur($m['du']), 'hourglass-split', 't-bad', '']];
    echo '<div class="row g-3 mb-3">'; foreach ($kp as [$l, $v, $i, $c, $h]) echo '<div class="col-6 col-lg-3"><' . ($h ? 'a href="' . $h . '"' : 'div') . ' class="kpi ' . $c . '"><span class="kpi-ico"><i class="bi bi-' . $i . '"></i></span><div class="kpi-body"><div class="kpi-label">' . $l . '</div><div class="kpi-value" style="font-size:1.15rem">' . $v . '</div></div></' . ($h ? 'a' : 'div') . '></div>'; echo '</div>';
    $rec = q('SELECT s.*,c.name cn FROM sales s LEFT JOIN customers c ON c.id=s.customer_id WHERE s.user_id=? ORDER BY s.id DESC LIMIT 8', [$u])->fetchAll();
    echo '<div class="panel"><div class="panel-head"><h6 class="panel-title"><i class="bi bi-receipt"></i> آخر فواتيري</h6><a href="' . BASE . '/sales/">الكل</a></div>' . render_table([['label' => 'الفاتورة', 'type' => 'html', 'f' => fn($r) => '<a href="' . BASE . '/sales/invoice.php?id=' . $r['id'] . '"><b>' . e($r['invoice_no']) . '</b></a>'], ['label' => 'الوقت', 'key' => 'created_at', 'type' => 'datetime'], ['label' => 'العميل', 'f' => fn($r) => $r['cn'] ?: 'نقدي'], ['label' => 'الإجمالي', 'key' => 'total', 'type' => 'money'], ['label' => 'الحالة', 'type' => 'html', 'f' => 'sale_status_badge']], $rec, ['empty' => empty_state('لا توجد فواتير بعد', 'ابدأ أول عملية بيع من نقطة البيع.', 'cart-check', '<a class="btn btn-gold mt-2" href="' . BASE . '/pos/">بيع جديد</a>')]) . '</div>';
    foot(); exit;
}

/* ---------------- لوحة المدير ---------------- */
$K = dash_kpis($f, $t);
$tiles = [
    ['مبيعات ' . $plabel, cur($K['total']), $K['n'] . ' فاتورة', 'cash-stack', '', $repSales],
    ['عدد الفواتير', num($K['n']), 'خصومات: ' . cur($K['disc']), 'receipt', 't-ink', BASE . '/sales/?f=' . $f . '&t=' . $t . '&status=completed'],
    ['المحصَّل (مدفوعات)', cur($K['collected']), 'نقدي وبنك ودفعات عملاء', 'check2-circle', 't-ok', BASE . '/reports/?r=methods' . $range],
    ['المتبقي من مبيعات الفترة', cur($K['due']), 'آجل وجزئي', 'hourglass-split', 't-bad', BASE . '/sales/?f=' . $f . '&t=' . $t . '&pay=due'],
    ['ديون العملاء الحالية', cur($K['debt']), $K['debtors'] . ' عميل مدين', 'person-exclamation', 't-bad', BASE . '/reports/?r=customers&debtors=1'],
    ['المشتريات', cur($K['purch']), $K['purch_n'] . ' فاتورة — مدفوع للموردين ' . cur($K['purch_paid']), 'truck', 't-info', BASE . '/reports/?r=purchases' . $range],
    ['مستحقات الموردين', cur($K['sup_debt']), 'ما علينا للموردين حاليًا', 'shop', 't-bad', BASE . '/reports/?r=suppliers&debtors=1'],
    ['المصروفات', cur($K['exp']), 'غير شامل الملغاة', 'wallet2', 't-bad', BASE . '/reports/?r=expenses' . $range . '&estatus=active'],
    ['صافي الحركة النقدية', cur($K['net_cash']), 'محصَّل − مدفوع للموردين − مصروفات', 'graph-up-arrow', $K['net_cash'] >= 0 ? 't-ok' : 't-bad', ''],
    ['الربح الإجمالي التقديري', cur($K['profit']), 'مبيعات − تكلفة البضاعة', 'piggy-bank', 't-ok', BASE . '/reports/?r=product' . $range],
    ['عدد المنتجات', num($K['products']), 'منتج فعّال', 'box-seam', 't-ink', BASE . '/products/'],
    ['منتجات منخفضة المخزون', num($K['low']), 'تحتاج إعادة توريد', 'exclamation-triangle', $K['low'] ? 't-bad' : 't-ok', BASE . '/products/?low=1'],
];
echo '<div class="row g-3 mb-3">';
foreach ($tiles as [$l, $v, $s, $i, $c, $h]) echo '<div class="col-6 col-xl-3"><' . ($h ? 'a href="' . e($h) . '"' : 'div') . ' class="kpi ' . $c . '"><span class="kpi-ico"><i class="bi bi-' . $i . '"></i></span><div class="kpi-body"><div class="kpi-label">' . e($l) . '</div><div class="kpi-value" style="font-size:1.2rem">' . $v . '</div><div class="kpi-sub">' . e($s) . '</div></div></' . ($h ? 'a' : 'div') . '></div>';
echo '</div>';

/* لمحة المبيعات (يوم/أسبوع/شهر/سنة) — دائمًا ثابتة المعنى بغض النظر عن الفترة المختارة */
$snap = [];
foreach (['day' => ['اليوم', 'day'], 'week' => ['الأسبوع', 'week'], 'month' => ['الشهر', 'month'], 'year' => ['السنة', 'year']] as $k => [$l]) {
    [$a, $b] = period_range($k); [$w, $p] = dash_range_sql('created_at', $a, $b);
    $r = q("SELECT COUNT(*) n,COALESCE(SUM(total),0) t FROM sales WHERE status='completed' AND $w", $p)->fetch();
    $snap[] = [$l, $r, $k === 'day' ? BASE . '/reports/?r=daily&day=' . $a : BASE . '/reports/?r=daily&f=' . $a . '&t=' . $b];
}
echo '<div class="panel panel-pad mb-3"><h6 class="panel-title"><i class="bi bi-lightning-charge"></i> لمحة المبيعات</h6><div class="row g-2">';
foreach ($snap as [$l, $r, $h]) echo '<div class="col-6 col-md-3"><a href="' . e($h) . '" class="d-block p-3 rounded-3 h-100" style="background:#f7f8fb;color:inherit"><div class="small text-muted">مبيعات ' . $l . '</div><div class="fw-bold fs-5">' . cur($r['t']) . '</div><div class="small text-muted">' . num($r['n']) . ' فاتورة</div></a></div>';
echo '</div></div>';

$S = dash_series($f, $t, $kind); $split = dash_split($f, $t); $top = dash_top_products($f, $t);
echo '<div class="row g-3 mb-3"><div class="col-lg-8"><div class="panel panel-pad h-100"><div class="d-flex justify-content-between flex-wrap gap-2 mb-2"><h6 class="panel-title mb-0"><i class="bi bi-graph-up"></i> مبيعات ' . e($plabel) . '</h6><a class="small" href="' . e($repSales) . '">تفاصيل التقرير ←</a></div><div class="chart-box"><canvas id="salesChart"></canvas></div></div></div>';
echo '<div class="col-lg-4"><div class="panel panel-pad h-100"><h6 class="panel-title"><i class="bi bi-pie-chart"></i> توزيع المبيعات</h6>' . ($split ? '<div class="chart-box" style="height:240px"><canvas id="splitChart"></canvas></div>' : empty_state('لا مبيعات', 'لا بيانات ضمن الفترة.', 'pie-chart')) . '</div></div></div>';

$low = q("SELECT id,name,stock,min_stock FROM products WHERE status='active' AND stock<=min_stock ORDER BY stock-min_stock LIMIT 6")->fetchAll();
$debt = q('SELECT id,name,balance FROM customers WHERE balance>0 ORDER BY balance DESC LIMIT 6')->fetchAll();
echo '<div class="row g-3 mb-3">';
echo '<div class="col-lg-4"><div class="panel h-100"><div class="panel-head"><h6 class="panel-title"><i class="bi bi-exclamation-triangle"></i> مخزون منخفض</h6><a class="small" href="' . BASE . '/reports/?r=low">الكل</a></div><div class="p-3">';
foreach ($low as $l) echo '<div class="d-flex justify-content-between py-2 border-bottom"><a href="' . BASE . '/inventory/?id=' . $l['id'] . '">' . e($l['name']) . '</a><b class="text-danger">' . qty($l['stock']) . '</b></div>';
echo $low ? '' : '<div class="text-muted text-center py-3">كل المنتجات فوق حد التنبيه ✓</div>'; echo '</div></div></div>';
echo '<div class="col-lg-4"><div class="panel h-100"><div class="panel-head"><h6 class="panel-title"><i class="bi bi-person-exclamation"></i> أعلى العملاء مديونية</h6><a class="small" href="' . BASE . '/reports/?r=customers&debtors=1">الكل</a></div><div class="p-3">';
foreach ($debt as $l) echo '<div class="d-flex justify-content-between py-2 border-bottom"><a href="' . BASE . '/customers/statement.php?id=' . $l['id'] . '">' . e($l['name']) . '</a><b class="text-danger amt">' . money($l['balance']) . '</b></div>';
echo $debt ? '' : '<div class="text-muted text-center py-3">لا توجد ديون على العملاء</div>'; echo '</div></div></div>';
echo '<div class="col-lg-4"><div class="panel h-100"><div class="panel-head"><h6 class="panel-title"><i class="bi bi-trophy"></i> الأكثر مبيعًا (' . e($plabel) . ')</h6><a class="small" href="' . BASE . '/reports/?r=product' . $range . '">الكل</a></div><div class="p-3">';
foreach ($top as $l) echo '<div class="d-flex justify-content-between py-2 border-bottom"><span>' . e($l['name']) . ' <small class="text-muted">× ' . qty($l['q']) . '</small></span><b class="amt">' . money($l['t']) . '</b></div>';
echo $top ? '' : '<div class="text-muted text-center py-3">لا مبيعات ضمن الفترة</div>'; echo '</div></div></div></div>';

$rec = q('SELECT s.*,c.name cn,u.full_name un FROM sales s JOIN users u ON u.id=s.user_id LEFT JOIN customers c ON c.id=s.customer_id ORDER BY s.id DESC LIMIT 8')->fetchAll();
$act = q('SELECT l.*,u.full_name FROM audit_logs l LEFT JOIN users u ON u.id=l.user_id WHERE l.action NOT IN (\'login\',\'logout\',\'report_exported\',\'invoice_pdf\',\'statement_exported\') ORDER BY l.id DESC LIMIT 7')->fetchAll();
echo '<div class="row g-3"><div class="col-lg-7"><div class="panel"><div class="panel-head"><h6 class="panel-title"><i class="bi bi-receipt"></i> آخر الفواتير</h6><a class="small" href="' . BASE . '/sales/">الكل</a></div>' . render_table([['label' => 'الفاتورة', 'type' => 'html', 'f' => fn($r) => '<a href="' . BASE . '/sales/invoice.php?id=' . $r['id'] . '"><b>' . e($r['invoice_no']) . '</b></a>'], ['label' => 'الوقت', 'key' => 'created_at', 'type' => 'datetime'], ['label' => 'العميل', 'f' => fn($r) => $r['cn'] ?: 'نقدي'], ['label' => 'الكاشير', 'key' => 'un'], ['label' => 'الإجمالي', 'key' => 'total', 'type' => 'money'], ['label' => 'الحالة', 'type' => 'html', 'f' => 'sale_status_badge']], $rec, ['row_class' => fn($r) => $r['status'] === 'cancelled' ? 'row-cancelled' : '', 'empty' => empty_state('لا توجد فواتير بعد', 'ستظهر هنا آخر عمليات البيع.', 'receipt')]) . '</div></div>';
echo '<div class="col-lg-5"><div class="panel"><div class="panel-head"><h6 class="panel-title"><i class="bi bi-clock-history"></i> آخر العمليات</h6>' . (can('view_audit') ? '<a class="small" href="' . BASE . '/audit/">الكل</a>' : '') . '</div>';
foreach ($act as $r) { $x = audit_describe($r); echo '<div class="audit-item py-2"><span class="audit-ico tone-' . $x['tone'] . '" style="width:34px;height:34px"><i class="bi bi-' . $x['icon'] . '"></i></span><div class="min-w-0"><div class="audit-title small">' . e($x['title']) . '</div><div class="audit-sum small">' . e($r['full_name'] ?: 'النظام') . ' · ' . dt($r['created_at']) . '</div></div></div>'; }
echo $act ? '' : empty_state('لا نشاط بعد', '', 'clock-history'); echo '</div></div></div>';

$js = '<script src="' . BASE . '/assets/vendor/chart/chart.umd.js"></script><script>window.DASH=' . json_encode(['s' => $S, 'split' => $split, 'cur' => currency(), 'gran' => $S['gran']], JSON_UNESCAPED_UNICODE) . ';</script><script src="' . BASE . '/assets/js/dashboard.js"></script>';
foot($js);
