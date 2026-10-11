<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/accounting.php';
require __DIR__ . '/../includes/pdf.php';
need('manage_suppliers');
$d = db(); $id = (int)($_GET['id'] ?? 0);
$s = q('SELECT * FROM suppliers WHERE id=?', [$id])->fetch();
if (!$s) fail_page(404, 'المورد غير موجود', 'تحقق من الرابط.');
$f = $_GET['f'] ?? ''; $t = $_GET['t'] ?? ''; if (!is_date($f)) $f = ''; if (!is_date($t)) $t = '';
[$out, $opening, $totP, $totPay] = supplier_ledger($d, $id, $f, $t);
$close = $out ? end($out)['bal'] : $opening; $calc = supplier_balance_calc($d, $id);
$cols = [
    ['label' => 'التاريخ', 'key' => 'dt', 'type' => 'datetime', 'w' => 1.3],
    ['label' => 'نوع الحركة', 'key' => 'label', 'w' => 1.7],
    ['label' => 'المرجع', 'type' => 'html', 'w' => 1.1, 'f' => fn($r) => $r['pid'] && $r['ref'] ? '<a href="' . BASE . '/purchases/?id=' . $r['pid'] . '"><b>' . e($r['ref']) . '</b></a>' : e($r['ref'] ?: '—')],
    ['label' => 'مدين (دفعات −)', 'f' => fn($r) => $r['debit'] > 0 ? $r['debit'] : '', 'type' => 'money'],
    ['label' => 'دائن (مشتريات +)', 'f' => fn($r) => $r['credit'] > 0 ? $r['credit'] : '', 'type' => 'money'],
    ['label' => 'الرصيد بعد الحركة', 'key' => 'bal', 'type' => 'money'],
];
$opRow = ['dt' => $f ? $f . ' 00:00:00' : null, 'label' => 'رصيد سابق', 'ref' => '', 'pid' => null, 'kind' => '', 'debit' => 0, 'credit' => 0, 'bal' => $opening];
$meta = [['المورد', $s['name']], ['الهاتف', $s['phone'] ?: '—'], ['الفترة', ($f || $t) ? trim(($f ? 'من ' . d($f) : '') . ' ' . ($t ? 'إلى ' . d($t) : '')) : 'كل الفترات']];
$summary = [['الرصيد السابق', cur($opening)], ['إجمالي المشتريات', cur($totP)], ['إجمالي المدفوعات', cur($totPay)], ['الرصيد المستحق', cur($close)]];
$fmt = $_GET['fmt'] ?? '';
if ($fmt === 'csv' || $fmt === 'pdf') {
    $exp = array_merge([$opRow], $out);
    audit('statement_exported', 'supplier', $id, ['customer' => $s['name'], 'format' => strtoupper($fmt)]);
    if ($fmt === 'csv') csv_out('supplier-statement-' . $id . '.csv', $cols, $exp);
    pdf_send(pdf_table('كشف حساب مورد', $meta, $summary, $cols, $exp), 'supplier-statement-' . $id . '.pdf');
}
head('كشف حساب ' . $s['name'], 'manage_suppliers');
page_header('كشف حساب المورد: ' . $s['name'], $s['phone'] ?: 'بدون هاتف',
    '<a class="btn btn-soft" href="view.php?id=' . $id . '"><i class="bi bi-arrow-right"></i> تفاصيل المورد</a><button class="btn btn-outline-ink" onclick="print()"><i class="bi bi-printer"></i> طباعة</button><a class="btn btn-gold" href="' . e(qs(['fmt' => 'pdf'])) . '"><i class="bi bi-file-earmark-pdf"></i> PDF</a><a class="btn btn-outline-ink" href="' . e(qs(['fmt' => 'csv'])) . '"><i class="bi bi-filetype-csv"></i> CSV</a>');
echo '<div class="print-head"><h3 class="mb-0">' . e(company()) . '</h3><div class="small">' . e(setting('phone')) . ' ' . e(setting('address')) . '</div><h4 class="mt-2">كشف حساب مورد: ' . e($s['name']) . '</h4></div>';
if (abs($calc - (float)$s['balance']) > 0.004) echo '<div class="alert alert-warning no-print">الرصيد المخزَّن (' . cur($s['balance']) . ') يختلف عن المحسوب (' . cur($calc) . ') — أعد الاحتساب من الإعدادات.</div>';
?>
<form class="panel panel-pad mb-3 no-print"><input type="hidden" name="id" value="<?= $id ?>"><div class="filters"><div><label class="form-label">من تاريخ</label><input type="date" name="f" class="form-control" value="<?= e($f) ?>"></div><div><label class="form-label">إلى تاريخ</label><input type="date" name="t" class="form-control" value="<?= e($t) ?>"></div><div class="d-flex gap-2"><button class="btn btn-ink flex-fill"><i class="bi bi-funnel"></i> عرض</button><a class="btn btn-soft" href="?id=<?= $id ?>">مسح</a></div></div></form>
<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><div class="kpi t-ink"><span class="kpi-ico"><i class="bi bi-skip-backward"></i></span><div class="kpi-body"><div class="kpi-label">الرصيد السابق</div><div class="kpi-value" style="font-size:1.15rem"><?= cur($opening) ?></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="kpi"><span class="kpi-ico"><i class="bi bi-truck"></i></span><div class="kpi-body"><div class="kpi-label">إجمالي المشتريات</div><div class="kpi-value" style="font-size:1.15rem"><?= cur($totP) ?></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="kpi t-ok"><span class="kpi-ico"><i class="bi bi-cash-coin"></i></span><div class="kpi-body"><div class="kpi-label">إجمالي المدفوعات</div><div class="kpi-value" style="font-size:1.15rem"><?= cur($totPay) ?></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="kpi t-bad"><span class="kpi-ico"><i class="bi bi-hourglass-split"></i></span><div class="kpi-body"><div class="kpi-label">الرصيد المستحق للمورد</div><div class="kpi-value" style="font-size:1.15rem"><?= cur($close) ?></div></div></div></div>
</div>
<?php
$show = $out; if ($opening != 0 || $f) array_unshift($show, $opRow);
echo '<div class="panel">' . render_table($cols, $show, ['empty' => empty_state('لا توجد حركات', 'لا فواتير شراء أو دفعات لهذا المورد ضمن الفترة.', 'journal-text')]) . '</div>';
foot();
