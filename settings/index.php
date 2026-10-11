<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/accounting.php';
need('manage_settings');
$d = db(); $err = '';
$K = ['company_name' => 'اسم المنشأة', 'phone' => 'الهاتف', 'email' => 'البريد الإلكتروني', 'address' => 'العنوان', 'currency' => 'العملة (تظهر بجانب كل مبلغ)', 'invoice_prefix' => 'بادئة رقم الفاتورة', 'tax_no' => 'الرقم الضريبي / السجل التجاري', 'invoice_footer' => 'تذييل الفاتورة'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    try {
        if (($_POST['act'] ?? '') === 'recalc') {                         // إعادة احتساب الأرصدة من الدفتر
            $n = 0;
            in_tx($d, function () use ($d, &$n) {
                foreach ($d->query('SELECT id FROM sales WHERE status=\'completed\'')->fetchAll(PDO::FETCH_COLUMN) as $sid) recalc_sale($d, (int)$sid);
                foreach ($d->query('SELECT id,name,balance FROM customers')->fetchAll() as $c) {
                    $new = recalc_customer_balance($d, (int)$c['id']);
                    if (abs($new - $c['balance']) > 0.004) { $n++; audit('balance_recalculated', 'customer', $c['id'], ['customer' => $c['name'], 'old' => (float)$c['balance'], 'new' => $new]); }
                }
                foreach ($d->query('SELECT id FROM purchases')->fetchAll(PDO::FETCH_COLUMN) as $pid) recalc_purchase($d, (int)$pid);
                foreach ($d->query('SELECT id,name,balance FROM suppliers')->fetchAll() as $c) {
                    $new = recalc_supplier_balance($d, (int)$c['id']);
                    if (abs($new - $c['balance']) > 0.004) { $n++; audit('balance_recalculated', 'supplier', $c['id'], ['customer' => $c['name'], 'old' => (float)$c['balance'], 'new' => $new]); }
                }
            });
            flash('success', $n ? "تم تصحيح $n رصيد/أرصدة عملاء" : 'كل الأرصدة سليمة ومطابقة للدفتر'); redirect(qs());
        }
        $old = settings(true); $v = []; $chg = [];
        foreach ($K as $k => $l) $v[$k] = trim((string)($_POST[$k] ?? ''));
        if ($v['company_name'] === '') dom('اسم المنشأة مطلوب');
        if (mb_strlen($v['company_name']) > 80 || mb_strlen($v['currency']) > 12 || mb_strlen($v['invoice_footer']) > 250) dom('أحد الحقول أطول من المسموح');
        if ($v['currency'] === '') dom('العملة مطلوبة');
        if (!preg_match('/^[A-Za-z0-9]{1,10}$/', $v['invoice_prefix'])) dom('بادئة الفاتورة: حروف إنجليزية وأرقام فقط (حتى 10)');
        if ($v['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{5,30}$/', $v['phone'])) dom('رقم الهاتف غير صالح');
        if ($v['email'] !== '' && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) dom('البريد الإلكتروني غير صالح');
        if (!empty($_FILES['logo']['name'])) { $v['logo'] = save_uploaded_image($_FILES['logo'], __DIR__ . '/../uploads'); if (!empty($old['logo'])) @unlink(__DIR__ . '/../uploads/' . basename($old['logo'])); $chg['الشعار'] = ['', 'تم رفع شعار جديد']; }
        elseif (!empty($_POST['remove_logo'])) { if (!empty($old['logo'])) @unlink(__DIR__ . '/../uploads/' . basename($old['logo'])); $v['logo'] = ''; $chg['الشعار'] = ['', 'تمت إزالة الشعار']; }
        foreach ($v as $k => $val) { if ((string)($old[$k] ?? '') !== $val && $k !== 'logo') $chg[$K[$k]] = [$old[$k] ?? '', $val]; q('INSERT INTO settings(k,v) VALUES(?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)', [$k, $val]); }
        audit('settings_changed', 'settings', null, ['changes' => $chg]);
        flash('success', 'تم حفظ الإعدادات وتطبيقها على كل النظام'); redirect(qs());
    } catch (DomainException | RuntimeException $x) { $err = $x->getMessage(); }
}
$S = settings(true);
$issues = isset($_GET['check']) ? integrity_report($d) : null;
head('الإعدادات', 'manage_settings');
page_header('الإعدادات', 'بيانات الشركة تظهر في الفواتير وPDF والتقارير والشريط الجانبي');
?>
<div class="row g-3">
<div class="col-lg-8"><form method="post" enctype="multipart/form-data" class="panel panel-pad"><?= csrf_field() ?><?= $err ? '<div class="alert alert-danger">' . e($err) . '</div>' : '' ?>
  <h6 class="panel-title"><i class="bi bi-building"></i> بيانات المنشأة</h6>
  <div class="row g-3">
  <?php foreach ($K as $k => $l): $val = $_POST[$k] ?? ($S[$k] ?? ''); ?>
    <div class="col-12 <?= in_array($k, ['address', 'invoice_footer']) ? '' : 'col-md-6' ?>"><label class="form-label"><?= e($l) ?><?= $k === 'company_name' ? ' <span class="text-danger">*</span>' : '' ?></label>
    <input name="<?= $k ?>" class="form-control" value="<?= e($val) ?>" <?= in_array($k, ['company_name', 'currency', 'invoice_prefix']) ? 'required' : '' ?> maxlength="<?= $k === 'invoice_footer' ? 250 : 120 ?>" <?= $k === 'invoice_prefix' ? 'pattern="[A-Za-z0-9]{1,10}" dir="ltr"' : '' ?>><?= $k === 'invoice_prefix' ? '<div class="form-text">تؤثر على الفواتير الجديدة فقط، مثال: ' . e(($S['invoice_prefix'] ?? 'INV')) . '-00025</div>' : '' ?></div>
  <?php endforeach; ?>
  <div class="col-12"><label class="form-label">الشعار (PNG أو JPG حتى 1MB)</label>
    <div class="d-flex align-items-center gap-3 flex-wrap"><?= logo_url() ? '<img src="' . e(logo_url()) . '" style="height:56px;border-radius:10px;border:1px solid var(--line);padding:4px" alt="">' : '<span class="text-muted small">لا يوجد شعار</span>' ?><input type="file" name="logo" accept="image/png,image/jpeg" class="form-control" style="max-width:320px"><?php if (logo_url()) echo '<label class="form-check"><input class="form-check-input" type="checkbox" name="remove_logo" value="1"> <span class="form-check-label">إزالة الشعار</span></label>'; ?></div></div>
  </div>
  <div class="mt-4"><button class="btn btn-gold"><i class="bi bi-check2"></i> حفظ الإعدادات</button></div>
</form></div>
<div class="col-lg-4">
  <div class="panel panel-pad mb-3"><h6 class="panel-title"><i class="bi bi-shield-check"></i> سلامة البيانات</h6>
    <p class="text-muted small">يقارن أرصدة العملاء وحالات الفواتير والمخزون بالحركات المسجلة فعليًا.</p>
    <div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-ink" href="?check=1"><i class="bi bi-search"></i> فحص الآن</a>
    <form method="post" data-confirm="إعادة احتساب أرصدة العملاء وحالات الفواتير من سجل الحركات؟"><?= csrf_field() ?><input type="hidden" name="act" value="recalc"><button class="btn btn-soft"><i class="bi bi-arrow-repeat"></i> إعادة الاحتساب</button></form></div>
    <?php if ($issues !== null): ?><div class="mt-3"><?php if (!$issues) echo '<div class="alert alert-success mb-0"><i class="bi bi-check-circle-fill"></i> لا توجد مشاكل: كل الأرقام متسقة.</div>'; else { echo '<div class="alert alert-warning">' . count($issues) . ' ملاحظة</div><ul class="small ps-3">'; foreach ($issues as $i) echo '<li><b>' . e($i['type']) . ' ' . e($i['ref']) . ':</b> ' . e($i['msg']) . '</li>'; echo '</ul>'; } ?></div><?php endif; ?>
  </div>
  <div class="panel panel-pad"><h6 class="panel-title"><i class="bi bi-info-circle"></i> معلومات النظام</h6><dl class="kv small mb-0"><dt>إصدار القاعدة</dt><dd><?= e($S['schema_version'] ?? '?') ?></dd><dt>PHP</dt><dd><?= e(PHP_VERSION) ?></dd><dt>المنطقة الزمنية</dt><dd><?= e(date_default_timezone_get()) ?></dd></dl></div>
</div></div>
<?php foot();
