<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/accounting.php';
need('manage_suppliers');
$d = db(); $id = (int)($_GET['id'] ?? 0);
$s = q('SELECT * FROM suppliers WHERE id=?', [$id])->fetch();
if (!$s) fail_page(404, 'المورد غير موجود', 'تحقق من الرابط.');
$tot = q("SELECT COUNT(*) n,COALESCE(SUM(total),0) t FROM purchases WHERE supplier_id=? AND status='active'", [$id])->fetch();
$paid = (float)q('SELECT COALESCE(SUM(amount),0) FROM supplier_payments WHERE supplier_id=?', [$id])->fetchColumn();
$bal = supplier_balance_calc($d, $id);
$pq = trim($_GET['q'] ?? ''); $pg = page_no(); $per = 15;
$total = (int)q('SELECT COUNT(*) FROM purchases WHERE supplier_id=?', [$id])->fetchColumn();
$purch = q("SELECT * FROM purchases WHERE supplier_id=? ORDER BY id DESC LIMIT $per OFFSET " . (($pg - 1) * $per), [$id])->fetchAll();
$pays = q('SELECT sp.*,pm.name mn,u.full_name FROM supplier_payments sp JOIN payment_methods pm ON pm.id=sp.payment_method_id JOIN users u ON u.id=sp.user_id WHERE sp.supplier_id=? ORDER BY sp.id DESC LIMIT 15', [$id])->fetchAll();
head('المورد: ' . $s['name'], 'manage_suppliers');
page_header($s['name'], ($s['phone'] ?: 'بدون هاتف') . ($s['address'] ? ' — ' . $s['address'] : ''),
    '<a class="btn btn-soft" href="./"><i class="bi bi-arrow-right"></i> الموردون</a><a class="btn btn-outline-ink" href="statement.php?id=' . $id . '"><i class="bi bi-journal-text"></i> كشف الحساب</a><a class="btn btn-outline-ink" href="statement.php?id=' . $id . '&fmt=pdf"><i class="bi bi-file-earmark-pdf"></i> PDF</a>'
    . (can('create_purchase') && $s['is_active'] ? '<a class="btn btn-gold" href="' . BASE . '/purchases/?new=1&supplier=' . $id . '"><i class="bi bi-cart-plus"></i> شراء جديد</a>' : ''));
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><div class="kpi"><span class="kpi-ico"><i class="bi bi-truck"></i></span><div class="kpi-body"><div class="kpi-label">إجمالي المشتريات (<?= num($tot['n']) ?> فاتورة)</div><div class="kpi-value" style="font-size:1.15rem"><?= cur($tot['t']) ?></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="kpi t-ok"><span class="kpi-ico"><i class="bi bi-cash-coin"></i></span><div class="kpi-body"><div class="kpi-label">إجمالي المدفوع (صافي)</div><div class="kpi-value" style="font-size:1.15rem"><?= cur($paid) ?></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="kpi t-bad"><span class="kpi-ico"><i class="bi bi-hourglass-split"></i></span><div class="kpi-body"><div class="kpi-label">المستحق للمورد</div><div class="kpi-value" style="font-size:1.15rem"><?= cur($bal) ?></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="kpi t-ink"><span class="kpi-ico"><i class="bi bi-toggle-on"></i></span><div class="kpi-body"><div class="kpi-label">الحالة</div><div class="kpi-value" style="font-size:1.15rem"><?= $s['is_active'] ? 'فعّال' : 'معطّل' ?></div></div></div></div>
</div>
<?php if (abs($bal - (float)$s['balance']) > 0.004) echo '<div class="alert alert-warning">الرصيد المخزَّن (' . cur($s['balance']) . ') يختلف عن المحسوب (' . cur($bal) . ') — أعد الاحتساب من الإعدادات ← سلامة البيانات.</div>'; ?>
<div class="panel mb-3"><div class="panel-head"><h6 class="panel-title"><i class="bi bi-receipt"></i> فواتير الشراء</h6><a class="small" href="<?= BASE ?>/purchases/?sup=<?= $id ?>">فتح في المشتريات</a></div>
<?= render_table([
    ['label' => 'الفاتورة', 'type' => 'html', 'f' => fn($r) => '<a href="' . BASE . '/purchases/?id=' . $r['id'] . '"><b>' . e(purchase_no((int)$r['id'])) . '</b></a>'], ['label' => 'التاريخ', 'key' => 'created_at', 'type' => 'datetime'], ['label' => 'الإجمالي', 'key' => 'total', 'type' => 'money'],
    ['label' => 'المدفوع', 'f' => fn($r) => $r['status'] === 'cancelled' ? 0 : $r['paid'], 'type' => 'money'], ['label' => 'المتبقي', 'f' => fn($r) => $r['status'] === 'cancelled' ? 0 : $r['total'] - $r['paid'], 'type' => 'money'],
    ['label' => 'الحالة', 'type' => 'html', 'f' => fn($r) => $r['status'] === 'cancelled' ? badge('ملغاة', 'danger') : match ($r['pay_status']) { 'paid' => badge('مدفوعة بالكامل', 'success'), 'partial' => badge('جزئية', 'warning'), default => badge('آجلة', 'info') }],
    ['label' => '', 'type' => 'html', 'f' => fn($r) => '<div class="row-actions"><a class="btn-act btn" title="عرض" href="' . BASE . '/purchases/?id=' . $r['id'] . '"><i class="bi bi-eye"></i></a><a class="btn-act btn" title="PDF" href="' . BASE . '/purchases/pdf.php?id=' . $r['id'] . '"><i class="bi bi-file-earmark-pdf"></i></a>' . (can('pay_supplier') && $r['status'] === 'active' && $r['pay_status'] !== 'paid' ? '<a class="btn-act btn ok" title="تسجيل دفعة جديدة" href="' . BASE . '/purchases/?id=' . $r['id'] . '&pay=1"><i class="bi bi-cash-coin"></i></a>' : '') . '</div>'],
], $purch, ['row_class' => fn($r) => $r['status'] === 'cancelled' ? 'row-cancelled' : '', 'empty' => empty_state('لا توجد مشتريات', 'لم تُسجَّل فواتير شراء لهذا المورد بعد.', 'truck')]) ?></div>
<?= pager($total, $pg, $per) ?>
<div class="panel mt-3"><div class="panel-head"><h6 class="panel-title"><i class="bi bi-cash-stack"></i> آخر الدفعات</h6><a class="small" href="statement.php?id=<?= $id ?>">كشف الحساب الكامل</a></div>
<?= render_table([['label' => 'التاريخ', 'key' => 'payment_date', 'type' => 'date'], ['label' => 'الفاتورة', 'type' => 'html', 'f' => fn($r) => '<a href="' . BASE . '/purchases/?id=' . $r['purchase_id'] . '">' . e(purchase_no((int)$r['purchase_id'])) . '</a>'], ['label' => 'النوع', 'f' => fn($r) => ['initial' => 'دفعة عند الشراء', 'payment' => 'دفعة لاحقة', 'reversal' => 'استرداد (عكس)'][$r['type']]], ['label' => 'طريقة الدفع', 'key' => 'mn'], ['label' => 'سجّلها', 'key' => 'full_name'], ['label' => 'المبلغ', 'key' => 'amount', 'type' => 'money']], $pays, ['empty' => empty_state('لا توجد دفعات', 'لم تُسجَّل دفعات لهذا المورد.', 'cash-stack')]) ?></div>
<?php foot();
