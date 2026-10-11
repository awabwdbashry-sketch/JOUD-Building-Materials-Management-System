<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/accounting.php';
require __DIR__ . '/../includes/reports.php';
need('manage_expenses');
$d = db(); $err = ''; $posted = null; $cats = $d->query('SELECT id,name FROM expense_categories WHERE is_active=1 ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post(); $posted = $_POST; $id = (int)($_POST['id'] ?? 0);
    try {
        save_expense($d, ctx(), $id, (int)($_POST['category_id'] ?? 0), $_POST['amount'] ?? 0, $_POST['expense_date'] ?? '', $_POST['description'] ?? '');
        flash('success', $id ? 'تم تحديث المصروف' : 'تمت إضافة المصروف'); redirect(qs([], ['edit', 'p']));
    } catch (DomainException $x) { $err = $x->getMessage(); }
}
/* ---------- عرض مصروف ---------- */
if (isset($_GET['id'])) {
    $e = q('SELECT e.*,c.name cn,u.full_name un,cb.full_name cbn,ub.full_name ubn FROM expenses e JOIN expense_categories c ON c.id=e.category_id JOIN users u ON u.id=e.user_id LEFT JOIN users cb ON cb.id=e.cancelled_by LEFT JOIN users ub ON ub.id=e.updated_by WHERE e.id=?', [(int)$_GET['id']])->fetch();
    if (!$e) fail_page(404, 'المصروف غير موجود', 'تحقق من الرابط.');
    head('مصروف #' . $e['id'], 'manage_expenses');
    $can = $e['status'] === 'active';
    page_header('مصروف #' . $e['id'], $e['cn'], '<a class="btn btn-soft" href="./"><i class="bi bi-arrow-right"></i> رجوع</a>' . ($can ? '<a class="btn btn-gold" href="./?edit=' . $e['id'] . '"><i class="bi bi-pencil-square"></i> تعديل</a>' : ''));
    echo '<div class="panel panel-pad"><dl class="kv mb-0"><dt>الحالة</dt><dd>' . ($can ? badge('نشط', 'success') : badge('ملغى', 'danger')) . '</dd><dt>التصنيف</dt><dd>' . e($e['cn']) . '</dd><dt>المبلغ</dt><dd>' . cur($e['amount']) . '</dd><dt>التاريخ</dt><dd>' . d($e['expense_date']) . '</dd><dt>الوصف</dt><dd>' . e($e['description'] ?: '—') . '</dd><dt>سجّله</dt><dd>' . e($e['un']) . ' — ' . dt($e['created_at']) . '</dd>'
        . ($e['updated_at'] ? '<dt>آخر تعديل</dt><dd>' . e($e['ubn']) . ' — ' . dt($e['updated_at']) . '</dd>' : '')
        . (!$can ? '<dt>سبب الإلغاء</dt><dd class="text-danger">' . e($e['cancel_reason']) . '</dd><dt>ألغاه</dt><dd>' . e($e['cbn']) . ' — ' . dt($e['cancelled_at']) . '</dd>' : '') . '</dl></div>';
    if ($can && can('cancel_expense')) echo '<form method="post" action="cancel.php" class="panel panel-pad mt-3 d-flex flex-wrap gap-2" data-confirm="إلغاء المصروف؟ لن يدخل في أي إجمالي بعد ذلك." data-confirm-title="إلغاء المصروف">' . csrf_field() . '<input type="hidden" name="id" value="' . $e['id'] . '"><input type="hidden" name="back_id" value="' . $e['id'] . '"><input name="reason" class="form-control" style="max-width:420px" placeholder="سبب الإلغاء (مطلوب)" required maxlength="250"><button class="btn btn-outline-danger"><i class="bi bi-x-octagon"></i> إلغاء المصروف</button></form>';
    foot(); exit;
}
/* ---------- القائمة ---------- */
$F = ['f' => array_key_exists('f', $_GET) ? $_GET['f'] : date('Y-m-01'), 't' => array_key_exists('t', $_GET) ? $_GET['t'] : today(), 'expcat' => (int)($_GET['cat'] ?? 0), 'user' => (int)($_GET['user'] ?? 0), 'estatus' => $_GET['st'] ?? '', 'q' => trim($_GET['q'] ?? '')];
foreach (['f', 't'] as $k) if ($F[$k] !== '' && !is_date($F[$k])) $F[$k] = '';
$w = ['1']; $p = [];
rep_date_only('e.expense_date', $F, $w, $p);
if ($F['expcat']) { $w[] = 'e.category_id=?'; $p[] = $F['expcat']; } if ($F['user']) { $w[] = 'e.user_id=?'; $p[] = $F['user']; }
if (in_array($F['estatus'], ['active', 'cancelled'], true)) { $w[] = 'e.status=?'; $p[] = $F['estatus']; }
if ($F['q'] !== '') { $w[] = '(e.description LIKE ? OR c.name LIKE ?)'; array_push($p, "%{$F['q']}%", "%{$F['q']}%"); }
$W = implode(' AND ', $w); $pg = page_no(); $per = 25;
$from = 'expenses e JOIN expense_categories c ON c.id=e.category_id JOIN users u ON u.id=e.user_id';
$tot = q("SELECT COUNT(*) n,COALESCE(SUM(IF(e.status='active',e.amount,0)),0) t,SUM(e.status='cancelled') cn FROM $from WHERE $W", $p)->fetch();
$rows = q("SELECT e.*,c.name cn,u.full_name un FROM $from WHERE $W ORDER BY e.expense_date DESC,e.id DESC LIMIT $per OFFSET " . (($pg - 1) * $per), $p)->fetchAll();
$edit = null;
if (isset($_GET['edit'])) { $edit = q('SELECT * FROM expenses WHERE id=?', [(int)$_GET['edit']])->fetch() ?: null; if ($edit && $edit['status'] === 'cancelled') { flash('warning', 'المصروف الملغى لا يمكن تعديله'); redirect(qs([], ['edit'])); } }
if ($posted !== null) $edit = ['id' => (int)($posted['id'] ?? 0)] + $posted;
$open = $err !== '' || isset($_GET['edit']);
head('المصروفات', 'manage_expenses');
page_header('المصروفات', num($tot['n']) . ' سجل', '<button class="btn btn-gold" data-open-modal="expModal"><i class="bi bi-plus-lg"></i> إضافة مصروف</button><a class="btn btn-outline-ink" href="' . BASE . '/expense_categories/"><i class="bi bi-diagram-3"></i> التصنيفات</a>' . (can('view_reports') ? '<a class="btn btn-outline-ink" href="' . e(BASE . '/reports/?r=expenses&f=' . $F['f'] . '&t=' . $F['t'] . '&fmt=pdf') . '"><i class="bi bi-file-earmark-pdf"></i> PDF</a>' : ''));
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><div class="kpi t-bad"><span class="kpi-ico"><i class="bi bi-wallet2"></i></span><div class="kpi-body"><div class="kpi-label">إجمالي المصروفات النشطة</div><div class="kpi-value"><?= num($tot['t']) ?><small><?= e(currency()) ?></small></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="kpi t-ink"><span class="kpi-ico"><i class="bi bi-slash-circle"></i></span><div class="kpi-body"><div class="kpi-label">ملغاة (غير محتسبة)</div><div class="kpi-value"><?= num($tot['cn'] ?? 0) ?></div></div></div></div>
</div>
<form class="panel panel-pad mb-3 no-print"><div class="filters">
  <div><label class="form-label">من</label><input type="date" name="f" class="form-control" value="<?= e($F['f']) ?>"></div><div><label class="form-label">إلى</label><input type="date" name="t" class="form-control" value="<?= e($F['t']) ?>"></div>
  <div><label class="form-label">التصنيف</label><select name="cat" class="form-select"><?= opts(db()->query('SELECT id,name FROM expense_categories ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR), $F['expcat'] ?: null, 'الكل') ?></select></div>
  <div><label class="form-label">المسجِّل</label><select name="user" class="form-select"><?= opts(users_list(), $F['user'] ?: null, 'الكل') ?></select></div>
  <div><label class="form-label">الحالة</label><select name="st" class="form-select"><?= opts(['active' => 'نشط', 'cancelled' => 'ملغى'], $F['estatus'], 'الكل') ?></select></div>
  <div class="grow"><label class="form-label">بحث</label><input name="q" class="form-control" placeholder="الوصف أو التصنيف" value="<?= e($F['q']) ?>"></div>
  <div class="d-flex gap-2"><button class="btn btn-ink flex-fill"><i class="bi bi-funnel"></i> تصفية</button><a class="btn btn-soft" href="?f=&t=">مسح</a></div></div></form>
<?php
$cols = [
    ['label' => 'التاريخ', 'type' => 'html', 'f' => fn($r) => '<a href="?id=' . $r['id'] . '"><b>' . d($r['expense_date']) . '</b></a>'],
    ['label' => 'التصنيف', 'key' => 'cn'], ['label' => 'الوصف', 'f' => fn($r) => $r['description'] ?: '—'], ['label' => 'المسجِّل', 'key' => 'un'],
    ['label' => 'المبلغ', 'key' => 'amount', 'type' => 'money'],
    ['label' => 'الحالة', 'type' => 'html', 'f' => fn($r) => $r['status'] === 'cancelled' ? badge('ملغى', 'danger') : badge('نشط', 'success')],
    ['label' => '', 'type' => 'html', 'f' => fn($r) => '<div class="row-actions"><a class="btn-act btn" title="عرض" href="?id=' . $r['id'] . '"><i class="bi bi-eye"></i></a>' . ($r['status'] === 'active' ? '<a class="btn-act btn" title="تعديل" href="' . e(qs(['edit' => $r['id']])) . '"><i class="bi bi-pencil-square"></i></a>' : '') . '</div>'],
];
echo '<div class="panel">' . render_table($cols, $rows, ['row_class' => fn($r) => $r['status'] === 'cancelled' ? 'row-cancelled' : '', 'empty' => empty_state('لا توجد مصروفات', 'لم تُسجَّل مصروفات ضمن هذه التصفية.', 'wallet2', '<button class="btn btn-gold mt-2" data-open-modal="expModal"><i class="bi bi-plus-lg"></i> إضافة مصروف</button>')]) . '</div><div class="mt-3">' . pager((int)$tot['n'], $pg, $per) . '</div>';
$editing = !empty($edit['id']);
modal_open('expModal', $editing ? 'تعديل مصروف' : 'إضافة مصروف', $open); ?>
<form method="post" action="<?= e(qs([], ['edit'])) ?>"><div class="modal-body"><?= $err ? '<div class="alert alert-danger">' . e($err) . '</div>' : '' ?><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
<div class="row g-3"><div class="col-12 col-md-6"><label class="form-label">التصنيف <span class="text-danger">*</span></label><select name="category_id" class="form-select" required><?= opts($cats, $edit['category_id'] ?? null, '— اختر —') ?></select></div>
<div class="col-6 col-md-3"><label class="form-label">المبلغ <span class="text-danger">*</span></label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required value="<?= e($edit['amount'] ?? '') ?>"></div>
<div class="col-6 col-md-3"><label class="form-label">التاريخ</label><input type="date" name="expense_date" class="form-control" required max="<?= today() ?>" value="<?= e($edit['expense_date'] ?? today()) ?>"></div>
<div class="col-12"><label class="form-label">الوصف / السبب</label><input name="description" class="form-control" maxlength="255" value="<?= e($edit['description'] ?? '') ?>"></div></div></div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-gold"><i class="bi bi-check2"></i> حفظ</button></div></form>
<?php modal_close(); foot();
