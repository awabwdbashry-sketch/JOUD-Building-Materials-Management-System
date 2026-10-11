<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/accounting.php';
require __DIR__ . '/../includes/pdf.php';
need('manage_customers');
$d = db(); $id = (int)($_GET['id'] ?? 0);
$c = q('SELECT * FROM customers WHERE id=?', [$id])->fetch();
if (!$c) fail_page(404, 'العميل غير موجود', 'تحقق من الرابط.');

/** دفتر حساب العميل: كل الحركات مرتبة زمنيًا (فاتورة / إلغاء / دفعة / عكس) */
function customer_ledger(int $cid): array {
    $sql = "SELECT s.created_at dt,s.id sid,'sale' kind,s.invoice_no ref,'فاتورة بيع' label,s.total debit,0 credit,s.id ord FROM sales s WHERE s.customer_id=?
      UNION ALL SELECT s.cancelled_at,s.id,'cancel',s.invoice_no,'إلغاء فاتورة',0,s.total,s.id FROM sales s WHERE s.customer_id=? AND s.status='cancelled'
      UNION ALL SELECT TIMESTAMP(p.payment_date,TIME(p.created_at)),p.sale_id,p.type,COALESCE(s.invoice_no,p.reference,''),
        CONCAT(CASE p.type WHEN 'sale' THEN 'دفعة عند البيع' WHEN 'customer' THEN 'دفعة عميل' ELSE 'عكس دفعة' END,' - ',pm.name),
        IF(p.amount<0,-p.amount,0),IF(p.amount>0,p.amount,0),p.id FROM payments p JOIN payment_methods pm ON pm.id=p.payment_method_id LEFT JOIN sales s ON s.id=p.sale_id WHERE p.customer_id=?
      ORDER BY dt,ord";
    return q($sql, [$cid, $cid, $cid])->fetchAll();
}
$f = $_GET['f'] ?? ''; $t = $_GET['t'] ?? ''; if (!is_date($f)) $f = ''; if (!is_date($t)) $t = '';
$bal = 0.0; $opening = 0.0; $rows = []; $totD = 0; $totC = 0;
foreach (customer_ledger($id) as $e) {
    $day = substr($e['dt'], 0, 10);
    if ($f !== '' && $day < $f) { $opening += $e['debit'] - $e['credit']; continue; }
    if ($t !== '' && $day > $t) continue;
    $rows[] = $e;
}
$bal = r2($opening); $out = [];
foreach ($rows as $e) { $bal = r2($bal + $e['debit'] - $e['credit']); $totD += $e['debit']; $totC += $e['credit']; $out[] = $e + ['bal' => $bal]; }
$opening = r2($opening);
$calc = customer_balance_calc($d, $id);
$cols = [
    ['label' => 'التاريخ', 'key' => 'dt', 'type' => 'datetime', 'w' => 1.3],
    ['label' => 'نوع الحركة', 'key' => 'label', 'w' => 1.6],
    ['label' => 'المرجع / الفاتورة', 'type' => 'html', 'w' => 1.3, 'f' => fn($r) => $r['sid'] && can('view_sales') && $r['kind'] !== 'customer' ? '<a href="' . BASE . '/sales/invoice.php?id=' . $r['sid'] . '"><b>' . e($r['ref']) . '</b></a>' : e($r['ref'] ?: '—')],
    ['label' => 'مدين (+)', 'f' => fn($r) => $r['debit'] > 0 ? $r['debit'] : '', 'type' => 'money'],
    ['label' => 'دائن (−)', 'f' => fn($r) => $r['credit'] > 0 ? $r['credit'] : '', 'type' => 'money'],
    ['label' => 'الرصيد بعد الحركة', 'key' => 'bal', 'type' => 'money'],
];
$meta = [['العميل', $c['name']], ['الهاتف', $c['phone'] ?: '—'], ['الفترة', ($f || $t) ? trim(($f ? 'من ' . d($f) : '') . ' ' . ($t ? 'إلى ' . d($t) : '')) : 'كل الفترات']];
$summary = [['الرصيد السابق', cur($opening)], ['إجمالي الفواتير', cur($totD)], ['إجمالي المدفوعات', cur($totC)], ['الرصيد الختامي', cur($bal)]];
if (($_GET['fmt'] ?? '') === 'csv' || ($_GET['fmt'] ?? '') === 'pdf') {
    $exp = array_merge([['dt' => $f ? $f . ' 00:00:00' : '', 'label' => 'رصيد سابق', 'ref' => '', 'sid' => null, 'kind' => '', 'debit' => 0, 'credit' => 0, 'bal' => $opening]], $out);
    audit('statement_exported', 'customer', $id, ['customer' => $c['name'], 'format' => strtoupper($_GET['fmt'])]);
    if ($_GET['fmt'] === 'csv') csv_out('statement-' . $id . '.csv', $cols, $exp);
    pdf_send(pdf_table('كشف حساب عميل', $meta, $summary, $cols, $exp), 'statement-' . $id . '.pdf');
}
head('كشف حساب ' . $c['name'], 'manage_customers');
page_header('كشف حساب: ' . $c['name'], ($c['phone'] ?: 'بدون هاتف'),
    '<a class="btn btn-soft" href="./"><i class="bi bi-arrow-right"></i> العملاء</a>' . (can('receive_customer_payment') && $calc > 0 ? '<a class="btn btn-gold" href="payment.php?customer=' . $id . '"><i class="bi bi-cash-coin"></i> تسجيل دفعة</a>' : '')
    . '<button class="btn btn-outline-ink" onclick="print()"><i class="bi bi-printer"></i> طباعة</button><a class="btn btn-outline-ink" href="' . e(qs(['fmt' => 'pdf'])) . '"><i class="bi bi-file-earmark-pdf"></i> PDF</a><a class="btn btn-outline-ink" href="' . e(qs(['fmt' => 'csv'])) . '"><i class="bi bi-filetype-csv"></i> CSV</a>');
echo '<div class="print-head"><h3 class="mb-0">' . e(company()) . '</h3><div class="small">' . e(setting('phone')) . ' ' . e(setting('address')) . '</div><h4 class="mt-2">كشف حساب: ' . e($c['name']) . '</h4></div>';
if (abs($calc - (float)$c['balance']) > 0.004) echo '<div class="alert alert-warning no-print">الرصيد المخزَّن (' . cur($c['balance']) . ') يختلف عن المحسوب من الحركات (' . cur($calc) . ') — أعد احتسابه من الإعدادات ← فحص سلامة البيانات.</div>';
?>
<form class="panel panel-pad mb-3 no-print"><input type="hidden" name="id" value="<?= $id ?>"><div class="filters"><div><label class="form-label">من تاريخ</label><input type="date" name="f" class="form-control" value="<?= e($f) ?>"></div><div><label class="form-label">إلى تاريخ</label><input type="date" name="t" class="form-control" value="<?= e($t) ?>"></div><div class="d-flex gap-2"><button class="btn btn-ink flex-fill"><i class="bi bi-funnel"></i> عرض</button><a class="btn btn-soft" href="?id=<?= $id ?>">مسح</a></div></div></form>
<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><div class="kpi t-ink"><span class="kpi-ico"><i class="bi bi-skip-backward"></i></span><div class="kpi-body"><div class="kpi-label">الرصيد السابق</div><div class="kpi-value" style="font-size:1.15rem"><?= cur($opening) ?></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="kpi"><span class="kpi-ico"><i class="bi bi-receipt"></i></span><div class="kpi-body"><div class="kpi-label">إجمالي الفواتير</div><div class="kpi-value" style="font-size:1.15rem"><?= cur($totD) ?></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="kpi t-ok"><span class="kpi-ico"><i class="bi bi-cash-coin"></i></span><div class="kpi-body"><div class="kpi-label">إجمالي المدفوعات</div><div class="kpi-value" style="font-size:1.15rem"><?= cur($totC) ?></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="kpi t-bad"><span class="kpi-ico"><i class="bi bi-hourglass-split"></i></span><div class="kpi-body"><div class="kpi-label">الرصيد الختامي (المتبقي)</div><div class="kpi-value" style="font-size:1.15rem"><?= cur($bal) ?></div></div></div></div>
</div>
<?php
$show = $out;
if ($opening != 0 || $f) array_unshift($show, ['dt' => $f ? $f . ' 00:00:00' : null, 'label' => 'رصيد سابق', 'ref' => '', 'sid' => null, 'kind' => '', 'debit' => 0, 'credit' => 0, 'bal' => $opening]);
echo '<div class="panel">' . render_table($cols, $show, ['empty' => empty_state('لا توجد حركات', 'لا فواتير أو دفعات لهذا العميل ضمن الفترة.', 'journal-text')]) . '</div>';
foot();
