<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/accounting.php';
need('view_inventory');
$d = db(); $err = ''; $canAdj = can('manage_inventory');
const MOVE_TYPES = ['sale' => ['بيع', 'danger'], 'purchase' => ['شراء', 'success'], 'adjustment' => ['تسوية', 'info'], 'return' => ['إرجاع (إلغاء بيع)', 'warning'], 'purchase_cancel' => ['إلغاء شراء', 'secondary']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post(); need('manage_inventory');
    try {
        $r = adjust_stock($d, ctx(), (int)($_POST['product_id'] ?? 0), $_POST['dir'] ?? 'in', $_POST['qty'] ?? 0, $_POST['note'] ?? '');
        flash('success', 'تمت التسوية: الرصيد من ' . qty($r['before']) . ' إلى ' . qty($r['after']));
        redirect(qs([], ['adjust']));
    } catch (DomainException $x) { $err = $x->getMessage(); }
}

$pid = (int)($_GET['id'] ?? 0);
if ($pid) {                                   // ----- سجل حركة منتج
    $p = q('SELECT p.*,u.name unit FROM products p JOIN units u ON u.id=p.unit_id WHERE p.id=?', [$pid])->fetch();
    if (!$p) fail_page(404, 'المنتج غير موجود', 'تحقق من الرابط.');
    $type = $_GET['type'] ?? ''; $f = $_GET['f'] ?? ''; $t = $_GET['t'] ?? ''; $pg = page_no(); $per = 30;
    $w = ['m.product_id=?']; $pr = [$pid];
    if (isset(MOVE_TYPES[$type])) { $w[] = 'm.type=?'; $pr[] = $type; }
    if (is_date($f)) { $w[] = 'DATE(m.created_at)>=?'; $pr[] = $f; }
    if (is_date($t)) { $w[] = 'DATE(m.created_at)<=?'; $pr[] = $t; }
    $W = implode(' AND ', $w);
    $total = (int)q("SELECT COUNT(*) FROM inventory_movements m WHERE $W", $pr)->fetchColumn();
    $rows = q("SELECT m.*,u.full_name FROM inventory_movements m JOIN users u ON u.id=m.user_id WHERE $W ORDER BY m.id DESC LIMIT $per OFFSET " . (($pg - 1) * $per), $pr)->fetchAll();
    head('سجل المخزون', 'view_inventory');
    page_header($p['name'], 'الرصيد الحالي: ' . qty($p['stock']) . ' ' . $p['unit'] . ' — SKU: ' . $p['sku'], '<a class="btn btn-soft" href="./"><i class="bi bi-arrow-right"></i> رجوع للمخزون</a>' . ($canAdj ? '<a class="btn btn-gold" href="./?adjust=' . $pid . '"><i class="bi bi-sliders"></i> تسوية</a>' : ''));
    echo '<form class="panel panel-pad mb-3"><input type="hidden" name="id" value="' . $pid . '"><div class="filters"><div><label class="form-label">نوع الحركة</label><select name="type" class="form-select">' . opts(array_map(fn($x) => $x[0], MOVE_TYPES), $type, 'الكل') . '</select></div><div><label class="form-label">من</label><input type="date" name="f" class="form-control" value="' . e($f) . '"></div><div><label class="form-label">إلى</label><input type="date" name="t" class="form-control" value="' . e($t) . '"></div><div><button class="btn btn-ink w-100"><i class="bi bi-funnel"></i> تصفية</button></div></div></form>';
    $cols = [
        ['label' => 'التاريخ', 'key' => 'created_at', 'type' => 'datetime'],
        ['label' => 'النوع', 'type' => 'html', 'f' => fn($r) => badge(MOVE_TYPES[$r['type']][0] ?? $r['type'], MOVE_TYPES[$r['type']][1] ?? 'secondary')],
        ['label' => 'الكمية', 'type' => 'html', 'f' => fn($r) => '<b dir="ltr" class="' . ($r['qty'] < 0 ? 'text-danger' : 'text-success') . '">' . ($r['qty'] > 0 ? '+' : '') . qty($r['qty']) . '</b>'],
        ['label' => 'قبل', 'f' => fn($r) => $r['qty_before'] === null ? '—' : qty($r['qty_before'])],
        ['label' => 'بعد', 'f' => fn($r) => $r['qty_after'] === null ? '—' : qty($r['qty_after'])],
        ['label' => 'المستخدم', 'key' => 'full_name'],
        ['label' => 'المرجع', 'type' => 'html', 'f' => function ($r) {
            $l = $r['type'] === 'sale' || $r['type'] === 'return' ? BASE . '/sales/invoice.php?id=' . $r['ref_id'] : (str_starts_with($r['type'], 'purchase') ? BASE . '/purchases/?id=' . $r['ref_id'] : '');
            return ($l && can($r['type'] === 'sale' || $r['type'] === 'return' ? 'view_sales' : 'view_purchases') ? '<a href="' . e($l) . '">' . e($r['note']) . '</a>' : e($r['note']));
        }],
    ];
    echo '<div class="panel">' . render_table($cols, $rows, ['empty' => empty_state('لا توجد حركات', 'لم تُسجَّل حركات لهذا المنتج ضمن التصفية.', 'clock-history')]) . '</div><div class="mt-3">' . pager($total, $pg, $per) . '</div>';
    foot(); exit;
}

// ----- قائمة المخزون
$qv = trim($_GET['q'] ?? ''); $cat = (int)($_GET['cat'] ?? 0); $low = isset($_GET['low']); $pg = page_no(); $per = 30;
$w = ['(p.name LIKE ? OR p.sku LIKE ?)']; $pr = ["%$qv%", "%$qv%"];
if ($cat) { $w[] = 'p.category_id=?'; $pr[] = $cat; }
if ($low) $w[] = "p.status='active' AND p.stock<=p.min_stock";
$W = implode(' AND ', $w);
$total = (int)q("SELECT COUNT(*) FROM products p WHERE $W", $pr)->fetchColumn();
$sum = q("SELECT COALESCE(SUM(p.stock*p.cost_price),0) val,SUM(p.status='active' AND p.stock<=p.min_stock) lowc FROM products p WHERE $W", $pr)->fetch();
$rows = q("SELECT p.*,u.name unit,c.name cat FROM products p JOIN units u ON u.id=p.unit_id LEFT JOIN categories c ON c.id=p.category_id WHERE $W ORDER BY p.name LIMIT $per OFFSET " . (($pg - 1) * $per), $pr)->fetchAll();
$cats = $d->query('SELECT id,name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);
$adj = (int)($_GET['adjust'] ?? 0);
head('المخزون', 'view_inventory');
page_header('المخزون', 'أرصدة المنتجات وحركاتها', $canAdj ? '<button class="btn btn-gold" data-open-modal="adjModal"><i class="bi bi-sliders"></i> تسوية مخزون</button>' : '');
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><div class="kpi"><span class="kpi-ico"><i class="bi bi-box-seam"></i></span><div class="kpi-body"><div class="kpi-label">عدد المنتجات</div><div class="kpi-value"><?= num($total) ?></div></div></div></div>
  <div class="col-6 col-lg-3"><a class="kpi t-bad" href="<?= e(qs(['low' => 1])) ?>"><span class="kpi-ico"><i class="bi bi-exclamation-triangle"></i></span><div class="kpi-body"><div class="kpi-label">منخفضة المخزون</div><div class="kpi-value"><?= num($sum['lowc'] ?? 0) ?></div></div></a></div>
  <?php if (can('edit_product')): ?><div class="col-12 col-lg-6"><div class="kpi t-ok"><span class="kpi-ico"><i class="bi bi-cash-stack"></i></span><div class="kpi-body"><div class="kpi-label">قيمة المخزون بسعر التكلفة</div><div class="kpi-value"><?= num($sum['val']) ?><small><?= e(currency()) ?></small></div></div></div></div><?php endif; ?>
</div>
<form class="panel panel-pad mb-3"><div class="filters">
  <div class="grow"><label class="form-label">بحث</label><input name="q" class="form-control" placeholder="اسم المنتج أو SKU" value="<?= e($qv) ?>"></div>
  <div><label class="form-label">التصنيف</label><select name="cat" class="form-select"><?= opts($cats, $cat, 'الكل') ?></select></div>
  <div class="form-check form-switch pb-2"><input class="form-check-input" type="checkbox" name="low" id="lowChk" <?= $low ? 'checked' : '' ?>><label class="form-check-label" for="lowChk">المنخفض فقط</label></div>
  <div class="d-flex gap-2"><button class="btn btn-ink flex-fill"><i class="bi bi-funnel"></i> تصفية</button><?= ($qv !== '' || $cat || $low) ? '<a class="btn btn-soft" href="./">مسح</a>' : '' ?></div>
</div></form>
<?php
$cols = [
    ['label' => 'المنتج', 'type' => 'html', 'f' => fn($r) => '<b>' . e($r['name']) . '</b>' . ($r['status'] === 'inactive' ? ' ' . badge('معطّل') : '')],
    ['label' => 'SKU', 'key' => 'sku'], ['label' => 'التصنيف', 'f' => fn($r) => $r['cat'] ?: '—'],
    ['label' => 'الرصيد', 'type' => 'html', 'f' => fn($r) => '<span class="' . ($r['stock'] <= $r['min_stock'] ? 'text-danger fw-bold' : '') . '">' . qty($r['stock']) . ' ' . e($r['unit']) . '</span>'],
    ['label' => 'حد التنبيه', 'key' => 'min_stock', 'type' => 'qty'],
    ['label' => '', 'type' => 'html', 'f' => fn($r) => '<div class="row-actions"><a class="btn-act btn" title="سجل الحركات" href="?id=' . $r['id'] . '"><i class="bi bi-clock-history"></i></a>' . ($GLOBALS['canAdj'] ? '<a class="btn-act btn" title="تسوية" href="?adjust=' . $r['id'] . '"><i class="bi bi-sliders"></i></a>' : '') . '</div>'],
];
echo '<div class="panel">' . render_table($cols, $rows, ['empty' => empty_state('لا توجد منتجات', 'لا نتائج مطابقة.', 'boxes')]) . '</div><div class="mt-3">' . pager($total, $pg, $per) . '</div>';
if ($canAdj) {
    $all = $d->query("SELECT id,CONCAT(name,' — ',sku) n FROM products ORDER BY name")->fetchAll(PDO::FETCH_KEY_PAIR);
    modal_open('adjModal', 'تسوية مخزون', $err !== '' || $adj > 0); ?>
<form method="post" action="<?= e(qs([], ['adjust'])) ?>"><div class="modal-body"><?= $err ? '<div class="alert alert-danger">' . e($err) . '</div>' : '' ?><?= csrf_field() ?>
<div class="row g-3"><div class="col-12"><label class="form-label">المنتج</label><select name="product_id" class="form-select" required><?= opts($all, $_POST['product_id'] ?? $adj, '— اختر —') ?></select></div>
<div class="col-6"><label class="form-label">نوع التسوية</label><select name="dir" class="form-select"><?= opts(['in' => 'إدخال (زيادة)', 'out' => 'إخراج (نقص)'], $_POST['dir'] ?? 'in') ?></select></div>
<div class="col-6"><label class="form-label">الكمية</label><input type="number" step="0.001" min="0.001" name="qty" class="form-control" required value="<?= e($_POST['qty'] ?? '') ?>"></div>
<div class="col-12"><label class="form-label">السبب <span class="text-danger">*</span></label><input name="note" class="form-control" required maxlength="250" placeholder="مثال: جرد، تلف، هدية" value="<?= e($_POST['note'] ?? '') ?>"></div></div></div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-gold"><i class="bi bi-check2"></i> تنفيذ التسوية</button></div></form>
<?php modal_close(); }
foot();
