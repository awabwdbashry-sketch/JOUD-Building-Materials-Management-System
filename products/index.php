<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/accounting.php';
need('view_products');
$d = db(); $err = ''; $posted = null; $openId = null;
$canEdit = can('edit_product'); $canCreate = can('create_product'); $canDelete = can('delete_product'); $seeCost = $canEdit || $canCreate;

/** يقرأ حقول النموذج ويتحقق منها؛ يرمي DomainException برسالة عربية */
function product_input(): array {
    $f = ['name' => trim($_POST['name'] ?? ''), 'sku' => trim($_POST['sku'] ?? ''), 'category_id' => (int)($_POST['category_id'] ?? 0) ?: null, 'unit_id' => (int)($_POST['unit_id'] ?? 0),
        'cost_price' => $_POST['cost_price'] ?? 0, 'base_price' => $_POST['base_price'] ?? 0, 'min_price' => $_POST['min_price'] ?? 0, 'min_stock' => $_POST['min_stock'] ?? 0,
        'status' => ($_POST['status'] ?? '') === 'inactive' ? 'inactive' : 'active'];
    if ($f['name'] === '' || mb_strlen($f['name']) > 160) dom('اسم المنتج مطلوب (حتى 160 حرفًا)');
    if ($f['sku'] === '' || mb_strlen($f['sku']) > 60) dom('رمز SKU مطلوب (حتى 60 حرفًا)');
    foreach (['cost_price' => 'التكلفة', 'base_price' => 'سعر البيع', 'min_price' => 'الحد الأدنى للسعر', 'min_stock' => 'حد التنبيه'] as $k => $l) {
        if (!is_numeric($f[$k]) || $f[$k] < 0) dom($l . ' غير صالح');
        $f[$k] = round((float)$f[$k], $k === 'min_stock' ? 3 : 2);
    }
    if ($f['base_price'] <= 0) dom('سعر البيع يجب أن يكون أكبر من صفر');
    if ($f['min_price'] > $f['base_price']) dom('الحد الأدنى للسعر يجب ألا يتجاوز سعر البيع');
    if (!q('SELECT 1 FROM units WHERE id=?', [$f['unit_id']])->fetch()) dom('اختر وحدة القياس');
    if ($f['category_id'] && !q('SELECT 1 FROM categories WHERE id=?', [$f['category_id']])->fetch()) dom('التصنيف غير صالح');
    return $f;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    $act = $_POST['act'] ?? 'save'; $id = (int)($_POST['id'] ?? 0);
    try {
        if ($act === 'delete') {
            need('delete_product');
            $p = q('SELECT * FROM products WHERE id=?', [$id])->fetch(); if (!$p) dom('المنتج غير موجود');
            $used = (int)q('SELECT (SELECT COUNT(*) FROM sale_items WHERE product_id=?)+(SELECT COUNT(*) FROM purchase_items WHERE product_id=?)+(SELECT COUNT(*) FROM inventory_movements WHERE product_id=? AND NOT(type=\'adjustment\' AND note=\'رصيد افتتاحي\'))', [$id, $id, $id])->fetchColumn();
            if ($used > 0) {
                q("UPDATE products SET status='inactive' WHERE id=?", [$id]);
                audit('product_deactivated', 'product', $id, ['name' => $p['name'], 'reason' => 'مرتبط بحركات']);
                flash('warning', 'المنتج «' . $p['name'] . '» مرتبط بفواتير أو حركات مخزون فتم تعطيله بدل حذفه');
            } else {
                in_tx($d, function () use ($d, $id, $p) { $d->prepare('DELETE FROM inventory_movements WHERE product_id=?')->execute([$id]); $d->prepare('DELETE FROM products WHERE id=?')->execute([$id]); audit('product_deleted', 'product', $id, ['name' => $p['name'], 'sku' => $p['sku']]); });
                flash('success', 'تم حذف المنتج «' . $p['name'] . '»');
            }
            redirect(qs());
        } elseif ($act === 'toggle') {
            need('edit_product');
            $p = q('SELECT * FROM products WHERE id=?', [$id])->fetch(); if (!$p) dom('المنتج غير موجود');
            $new = $p['status'] === 'active' ? 'inactive' : 'active';
            q('UPDATE products SET status=? WHERE id=?', [$new, $id]);
            audit($new === 'active' ? 'product_activated' : 'product_deactivated', 'product', $id, ['name' => $p['name']]);
            flash('success', ($new === 'active' ? 'تم تفعيل ' : 'تم تعطيل ') . 'المنتج «' . $p['name'] . '»'); redirect(qs());
        }
        // حفظ (إضافة/تعديل)
        need($id ? 'edit_product' : 'create_product'); $posted = $_POST;
        $f = product_input();
        try {
            if ($id) {
                $o = q('SELECT * FROM products WHERE id=?', [$id])->fetch(); if (!$o) dom('المنتج غير موجود');
                q('UPDATE products SET name=?,sku=?,category_id=?,unit_id=?,cost_price=?,base_price=?,min_price=?,min_stock=?,status=? WHERE id=?', [...array_values($f), $id]);
                $chg = [];
                foreach (['name' => 'الاسم', 'sku' => 'SKU', 'cost_price' => 'التكلفة', 'base_price' => 'سعر البيع', 'min_price' => 'الحد الأدنى للسعر', 'min_stock' => 'حد التنبيه', 'status' => 'الحالة'] as $k => $l) if ((string)$o[$k] !== (string)$f[$k]) $chg[$l] = [$o[$k], $f[$k]];
                audit('product_updated', 'product', $id, ['name' => $f['name'], 'changes' => $chg]);
                if ((float)$o['base_price'] != $f['base_price'] || (float)$o['min_price'] != $f['min_price']) audit('price_changed', 'product', $id, ['name' => $f['name'], 'old_base' => (float)$o['base_price'], 'new_base' => $f['base_price'], 'old_min' => (float)$o['min_price'], 'new_min' => $f['min_price']]);
                flash('success', 'تم تحديث المنتج بنجاح');
            } else {
                $open = $_POST['stock'] ?? 0; if (!is_numeric($open) || $open < 0) dom('الرصيد الافتتاحي غير صالح');
                in_tx($d, function () use ($d, $f, $open) {
                    $d->prepare('INSERT INTO products(name,sku,category_id,unit_id,cost_price,base_price,min_price,min_stock,status,stock) VALUES(?,?,?,?,?,?,?,?,?,0)')->execute(array_values($f));
                    $nid = (int)$d->lastInsertId();
                    if ($open > 0) move_stock($d, $nid, 'adjustment', round((float)$open, 3), null, me()['id'], 'رصيد افتتاحي');
                    audit('product_created', 'product', $nid, ['name' => $f['name'], 'sku' => $f['sku'], 'base_price' => $f['base_price'], 'min_price' => $f['min_price'], 'opening_stock' => (float)$open]);
                });
                flash('success', 'تمت إضافة المنتج بنجاح');
            }
            redirect(qs([], ['edit']));
        } catch (PDOException $x) { throw new DomainException('رمز SKU «' . $f['sku'] . '» مستخدم لمنتج آخر'); }
    } catch (DomainException $x) { $err = $x->getMessage(); if (in_array($act, ['delete', 'toggle'])) { flash('danger', $err); redirect(qs()); } $openId = $id; }
}

/* ---- القائمة والفلاتر ---- */
$qv = trim($_GET['q'] ?? ''); $cat = (int)($_GET['cat'] ?? 0); $st = $_GET['st'] ?? ''; $low = isset($_GET['low']); $pg = page_no(); $per = 25;
$w = ['(p.name LIKE ? OR p.sku LIKE ?)']; $p = ["%$qv%", "%$qv%"];
if ($cat) { $w[] = 'p.category_id=?'; $p[] = $cat; }
if (in_array($st, ['active', 'inactive'], true)) { $w[] = 'p.status=?'; $p[] = $st; }
if ($low) $w[] = "p.status='active' AND p.stock<=p.min_stock";
$W = implode(' AND ', $w);
$total = (int)q("SELECT COUNT(*) FROM products p WHERE $W", $p)->fetchColumn();
$rows = q("SELECT p.*,c.name cat,u.name unit FROM products p LEFT JOIN categories c ON c.id=p.category_id JOIN units u ON u.id=p.unit_id WHERE $W ORDER BY p.id DESC LIMIT $per OFFSET " . (($pg - 1) * $per), $p)->fetchAll();
$cats = $d->query('SELECT id,name FROM categories WHERE is_active=1 ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);
$units = $d->query('SELECT id,name FROM units WHERE is_active=1 ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);
$edit = null;
if (isset($_GET['edit']) && $canEdit) { $edit = q('SELECT * FROM products WHERE id=?', [(int)$_GET['edit']])->fetch() ?: null; if ($edit) $openId = (int)$edit['id']; }
if ($posted !== null) $edit = ['id' => $openId] + $posted;
$openModal = $err !== '' || ($edit !== null && !empty($_GET['edit']));
$editing = !empty($edit['id']);
$v = fn($k, $dflt = '') => e($edit[$k] ?? $dflt);

head('المنتجات', 'view_products');
page_header('المنتجات', num($total) . ' منتج', ($canCreate ? '<button class="btn btn-gold" data-open-modal="productModal"><i class="bi bi-plus-lg"></i> إضافة منتج</button>' : ''));
?>
<form class="panel panel-pad mb-3 no-print"><div class="filters">
  <div class="grow"><label class="form-label">بحث</label><input name="q" class="form-control" placeholder="اسم المنتج أو SKU" value="<?= e($qv) ?>"></div>
  <div><label class="form-label">التصنيف</label><select name="cat" class="form-select"><?= opts($cats, $cat, 'الكل') ?></select></div>
  <div><label class="form-label">الحالة</label><select name="st" class="form-select"><?= opts(['active' => 'فعّال', 'inactive' => 'معطّل'], $st, 'الكل') ?></select></div>
  <div class="form-check form-switch pb-2"><input class="form-check-input" type="checkbox" name="low" id="lowChk" <?= $low ? 'checked' : '' ?>><label class="form-check-label" for="lowChk">المخزون المنخفض فقط</label></div>
  <div class="d-flex gap-2"><button class="btn btn-ink flex-fill"><i class="bi bi-funnel"></i> تصفية</button><?= ($qv !== '' || $cat || $st !== '' || $low) ? '<a class="btn btn-soft" href="./">مسح</a>' : '' ?></div>
</div></form>
<?php
$cols = [
    ['label' => 'المنتج', 'type' => 'html', 'f' => fn($r) => '<b>' . e($r['name']) . '</b>' . ($r['status'] === 'inactive' ? ' ' . badge('معطّل', 'secondary') : '')],
    ['label' => 'SKU', 'key' => 'sku'],
    ['label' => 'التصنيف', 'f' => fn($r) => $r['cat'] ?: '—'],
    ['label' => 'سعر البيع', 'key' => 'base_price', 'type' => 'money'],
];
if ($seeCost) { $cols[] = ['label' => 'الحد الأدنى', 'key' => 'min_price', 'type' => 'money']; $cols[] = ['label' => 'التكلفة', 'key' => 'cost_price', 'type' => 'money']; }
$cols[] = ['label' => 'المخزون', 'type' => 'html', 'f' => fn($r) => '<span class="' . ($r['status'] === 'active' && $r['stock'] <= $r['min_stock'] ? 'text-danger fw-bold' : '') . '">' . qty($r['stock']) . ' ' . e($r['unit']) . '</span>' . ($r['status'] === 'active' && $r['stock'] <= $r['min_stock'] ? ' ' . badge('منخفض', 'danger') : '')];
$cols[] = ['label' => '', 'type' => 'html', 'f' => function ($r) use ($canEdit, $canDelete) {
    $o = '<div class="row-actions">';
    if (can('view_inventory')) $o .= '<a class="btn-act btn" title="سجل حركة المخزون" href="' . BASE . '/inventory/?id=' . $r['id'] . '"><i class="bi bi-clock-history"></i></a>';
    if ($canEdit) $o .= '<a class="btn-act btn" title="تعديل" href="' . e(qs(['edit' => $r['id']])) . '"><i class="bi bi-pencil-square"></i></a>';
    if ($canEdit) $o .= '<form method="post" class="d-inline" data-confirm="' . ($r['status'] === 'active' ? 'تعطيل' : 'تفعيل') . ' المنتج «' . e($r['name']) . '»؟">' . csrf_field() . '<input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="' . $r['id'] . '"><button class="btn-act btn" title="' . ($r['status'] === 'active' ? 'تعطيل' : 'تفعيل') . '"><i class="bi bi-toggle-' . ($r['status'] === 'active' ? 'on' : 'off') . '"></i></button></form>';
    if ($canDelete) $o .= '<form method="post" class="d-inline" data-confirm="حذف المنتج «' . e($r['name']) . '»؟ إن كان مرتبطًا بحركات فسيتم تعطيله بدل الحذف." data-confirm-title="تأكيد الحذف">' . csrf_field() . '<input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="' . $r['id'] . '"><button class="btn-act btn danger" title="حذف"><i class="bi bi-trash3"></i></button></form>';
    return $o . '</div>';
}];
echo '<div class="panel">' . render_table($cols, $rows, ['empty' => empty_state('لا توجد منتجات', $qv !== '' || $cat || $low ? 'لا نتائج مطابقة للتصفية.' : 'أضف أول منتج لبدء البيع.', 'box-seam', $canCreate ? '<button class="btn btn-gold mt-2" data-open-modal="productModal"><i class="bi bi-plus-lg"></i> إضافة منتج</button>' : '')]) . '</div><div class="mt-3">' . pager($total, $pg, $per) . '</div>';

if ($canCreate || $canEdit) {
    modal_open('productModal', $editing ? 'تعديل منتج' : 'إضافة منتج', $openModal, 'modal-lg'); ?>
<form method="post" action="<?= e(qs([], ['edit'])) ?>"><div class="modal-body"><?= $err ? '<div class="alert alert-danger">' . e($err) . '</div>' : '' ?><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
<div class="row g-3">
  <div class="col-md-8"><label class="form-label">اسم المنتج <span class="text-danger">*</span></label><input name="name" class="form-control" required maxlength="160" value="<?= $v('name') ?>"></div>
  <div class="col-md-4"><label class="form-label">SKU <span class="text-danger">*</span></label><input name="sku" class="form-control" required maxlength="60" value="<?= $v('sku') ?>"></div>
  <div class="col-md-6"><label class="form-label">التصنيف</label><select name="category_id" class="form-select"><?= opts($cats, $edit['category_id'] ?? '', '— بدون —') ?></select></div>
  <div class="col-md-6"><label class="form-label">وحدة القياس <span class="text-danger">*</span></label><select name="unit_id" class="form-select" required><?= opts($units, $edit['unit_id'] ?? '', '— اختر —') ?></select></div>
  <div class="col-6 col-md-4"><label class="form-label">التكلفة</label><input type="number" step="0.01" min="0" name="cost_price" class="form-control" value="<?= $v('cost_price', 0) ?>"></div>
  <div class="col-6 col-md-4"><label class="form-label">سعر البيع <span class="text-danger">*</span></label><input type="number" step="0.01" min="0.01" name="base_price" class="form-control" required value="<?= $v('base_price') ?>"></div>
  <div class="col-6 col-md-4"><label class="form-label">أقل سعر مسموح <span class="text-danger">*</span></label><input type="number" step="0.01" min="0" name="min_price" class="form-control" required value="<?= $v('min_price') ?>"></div>
  <?php if (!$editing): ?><div class="col-6 col-md-4"><label class="form-label">رصيد افتتاحي</label><input type="number" step="0.001" min="0" name="stock" class="form-control" value="<?= $v('stock', 0) ?>"></div><?php endif; ?>
  <div class="col-6 col-md-4"><label class="form-label">حد التنبيه (مخزون منخفض)</label><input type="number" step="0.001" min="0" name="min_stock" class="form-control" value="<?= $v('min_stock', 0) ?>"></div>
  <div class="col-6 col-md-4"><label class="form-label">الحالة</label><select name="status" class="form-select"><?= opts(['active' => 'فعّال', 'inactive' => 'معطّل'], $edit['status'] ?? 'active') ?></select></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-gold"><i class="bi bi-check2"></i> حفظ</button></div></form>
<?php modal_close(); }
foot();
