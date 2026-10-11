<?php
require __DIR__ . '/../includes/bootstrap.php';
need(['manage_users', 'manage_roles']);
$d = db(); $err = '';
$GROUPS = ['المبيعات' => ['create_sale', 'credit_sale', 'override_min_price', 'view_sales', 'view_all_sales', 'cancel_sale', 'manage_customers', 'receive_customer_payment'],
    'المنتجات والمخزون' => ['view_products', 'create_product', 'edit_product', 'delete_product', 'view_inventory', 'manage_inventory', 'manage_catalog'],
    'المشتريات والموردون' => ['view_purchases', 'create_purchase', 'pay_supplier', 'cancel_purchase', 'manage_suppliers'],
    'المالية والتقارير' => ['manage_expenses', 'cancel_expense', 'view_analytics', 'view_reports', 'view_sales_reports', 'manage_payment_methods'],
    'الإدارة' => ['manage_users', 'manage_roles', 'manage_settings', 'view_audit', 'delete_records']];
$roles = $d->query("SELECT * FROM roles WHERE name IN ('admin','cashier') ORDER BY id")->fetchAll();
$sel = (int)($_GET['id'] ?? ($roles[1]['id'] ?? 0));
$cur = null; foreach ($roles as $r) if ((int)$r['id'] === $sel) $cur = $r;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post(); need('manage_users');
    $id = (int)($_POST['id'] ?? 0);
    try {
        $r = q("SELECT * FROM roles WHERE id=? AND name IN ('admin','cashier')", [$id])->fetch();
        if (!$r) dom('الدور غير موجود');
        if ($r['name'] === 'admin') dom('صلاحيات المدير كاملة دائمًا ولا تُعدَّل');
        $all = $d->query('SELECT id,code,label FROM permissions')->fetchAll(); $ids = array_column($all, 'id');
        $new = array_values(array_intersect($ids, array_map('intval', $_POST['perms'] ?? [])));
        $oldp = q('SELECT permission_id FROM role_permissions WHERE role_id=?', [$id])->fetchAll(PDO::FETCH_COLUMN);
        in_tx($d, function () use ($d, $id, $new) { $d->prepare('DELETE FROM role_permissions WHERE role_id=?')->execute([$id]); foreach ($new as $pid) $d->prepare('INSERT INTO role_permissions(role_id,permission_id) VALUES(?,?)')->execute([$id, $pid]); });
        $lab = array_column($all, 'label', 'id');
        audit('permission_changed', 'role', $id, ['role' => $r['label'], 'added' => array_map(fn($x) => $lab[$x], array_diff($new, $oldp)), 'removed' => array_map(fn($x) => $lab[$x], array_diff($oldp, $new))]);
        flash('success', 'تم حفظ صلاحيات الدور «' . $r['label'] . '» وتسري فورًا على المستخدمين'); redirect(qs());
    } catch (DomainException $x) { $err = $x->getMessage(); }
}
$perms = $d->query('SELECT * FROM permissions')->fetchAll(PDO::FETCH_UNIQUE); $byCode = []; foreach ($perms as $pid => $p) $byCode[$p['code']] = $pid + 0;
$has = $sel ? q('SELECT permission_id FROM role_permissions WHERE role_id=?', [$sel])->fetchAll(PDO::FETCH_COLUMN) : [];
$cnt = q('SELECT role_id,COUNT(*) FROM users GROUP BY role_id')->fetchAll(PDO::FETCH_KEY_PAIR);
head('الأدوار والصلاحيات', ['manage_users', 'manage_roles']);
page_header('الأدوار والصلاحيات', 'النظام يعتمد دورين فقط: المدير (كل الصلاحيات) والكاشير (قابل للتخصيص)', '<a class="btn btn-outline-ink" href="' . BASE . '/users/"><i class="bi bi-people"></i> المستخدمون</a>');
echo $err ? '<div class="alert alert-danger">' . e($err) . '</div>' : '';
echo '<div class="row g-3 mb-3">';
foreach ($roles as $r) echo '<div class="col-md-6"><a href="?id=' . $r['id'] . '" class="kpi ' . ($r['id'] == $sel ? 't-ok' : '') . '"><span class="kpi-ico"><i class="bi bi-' . ($r['name'] === 'admin' ? 'shield-check' : 'person-badge') . '"></i></span><div class="kpi-body"><div class="kpi-label">' . num($cnt[$r['id']] ?? 0) . ' مستخدم</div><div class="kpi-value" style="font-size:1.15rem">' . e($r['label']) . '</div></div></a></div>';
echo '</div>';
if ($cur): $locked = $cur['name'] === 'admin'; ?>
<form method="post" class="panel panel-pad"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $sel ?>">
<h6 class="panel-title"><i class="bi bi-shield-lock"></i> صلاحيات: <?= e($cur['label']) ?></h6>
<?php if ($locked) echo '<div class="alert alert-info">دور المدير يملك كل الصلاحيات دائمًا.</div>'; ?>
<?php foreach ($GROUPS as $g => $codes): ?><div class="mb-3"><div class="fw-bold mb-2 text-muted small"><?= e($g) ?></div><div class="row g-2">
<?php foreach ($codes as $c) { if (!isset($byCode[$c])) continue; $pid = $byCode[$c]; echo '<div class="col-md-6 col-xl-4"><label class="form-check border rounded-3 p-2 pe-4 d-flex gap-2 align-items-center" style="background:#fff"><input class="form-check-input mt-0" type="checkbox" name="perms[]" value="' . $pid . '"' . (in_array($pid, $has) ? ' checked' : '') . ($locked ? ' disabled' : '') . '><span class="form-check-label">' . e($perms[$pid]['label']) . '</span></label></div>'; } ?>
</div></div><?php endforeach; ?>
<?php if (!$locked) echo '<button class="btn btn-gold"><i class="bi bi-check2"></i> حفظ الصلاحيات</button>'; ?></form>
<?php endif; foot();
