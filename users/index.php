<?php
require __DIR__ . '/../includes/bootstrap.php';
need('manage_users');
$d = db(); $err = ''; $posted = null;
$roles = $d->query("SELECT id,label FROM roles WHERE is_active=1 AND name IN ('admin','cashier') ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);   // الأدوار: مدير وكاشير فقط

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post(); $posted = $_POST;
    $id = (int)($_POST['id'] ?? 0); $un = trim($_POST['username'] ?? ''); $fn = trim($_POST['full_name'] ?? ''); $rid = (int)($_POST['role_id'] ?? 0); $act = isset($_POST['is_active']) ? 1 : 0; $pw = $_POST['password'] ?? '';
    try {
        $o = $id ? q('SELECT u.*,r.name rn FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?', [$id])->fetch() : null;
        if ($id && !$o) dom('المستخدم غير موجود');
        if (!preg_match('/^[A-Za-z0-9_.]{3,40}$/', $un)) dom('اسم المستخدم: حروف إنجليزية وأرقام (3–40)');
        if ($fn === '' || mb_strlen($fn) > 120) dom('الاسم الكامل مطلوب');
        if (!isset($roles[$rid])) dom('اختر دورًا صالحًا (مدير أو كاشير)');
        if (($pw !== '' || !$id) && (strlen($pw) < 10)) dom('كلمة المرور 10 أحرف على الأقل');
        if ($id === (int)me()['id']) { $act = 1; if ($rid !== (int)me()['role_id']) dom('لا يمكنك تغيير دور حسابك الحالي'); }
        // لا نسمح بإزالة آخر مدير فعّال
        if ($o && $o['rn'] === 'admin' && ($act === 0 || $roles[$rid] !== 'المدير') && (int)q("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin' AND u.is_active=1 AND u.id<>?", [$id])->fetchColumn() === 0) dom('لا يمكن تعطيل أو تخفيض آخر مدير في النظام');
        $rl = $roles[$rid];
        if ($id) {
            q('UPDATE users SET username=?,full_name=?,role_id=?,is_active=? WHERE id=?', [$un, $fn, $rid, $act, $id]);
            if ($pw !== '') { q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $id]); audit('password_reset', 'user', $id, ['user' => $fn]); }
            audit('user_updated', 'user', $id, ['user' => $fn, 'username' => $un, 'role' => $rl, 'active' => $act ? 'فعّال' : 'معطّل']);
            flash('success', 'تم تحديث المستخدم');
        } else {
            q('INSERT INTO users(username,full_name,password_hash,role_id,is_active) VALUES(?,?,?,?,?)', [$un, $fn, password_hash($pw, PASSWORD_DEFAULT), $rid, $act]);
            audit('user_created', 'user', $d->lastInsertId(), ['user' => $fn, 'username' => $un, 'role' => $rl]);
            flash('success', 'تمت إضافة المستخدم');
        }
        redirect(qs([], ['edit']));
    } catch (DomainException $x) { $err = $x->getMessage(); }
    catch (PDOException $x) { $err = 'اسم المستخدم مستخدم مسبقًا'; }
}
$qv = trim($_GET['q'] ?? ''); $st = $_GET['st'] ?? ''; $rf = (int)($_GET['role'] ?? 0);
$w = ['(u.username LIKE ? OR u.full_name LIKE ?)']; $p = ["%$qv%", "%$qv%"];
if ($st !== '') { $w[] = 'u.is_active=?'; $p[] = (int)$st; } if ($rf) { $w[] = 'u.role_id=?'; $p[] = $rf; }
$rows = q('SELECT u.*,r.label rl,r.name rn FROM users u JOIN roles r ON r.id=u.role_id WHERE ' . implode(' AND ', $w) . ' ORDER BY u.id', $p)->fetchAll();
$edit = null;
if (isset($_GET['edit'])) $edit = q('SELECT * FROM users WHERE id=?', [(int)$_GET['edit']])->fetch() ?: null;
if ($posted !== null) $edit = ['id' => (int)($posted['id'] ?? 0)] + $posted + ['is_active' => 0];
$open = $err !== '' || isset($_GET['edit']);
head('المستخدمون', 'manage_users');
page_header('المستخدمون', count($rows) . ' مستخدم', '<button class="btn btn-gold" data-open-modal="userModal"><i class="bi bi-person-plus"></i> إضافة مستخدم</button><a class="btn btn-outline-ink" href="' . BASE . '/roles/"><i class="bi bi-shield-lock"></i> الأدوار</a>');
?>
<form class="panel panel-pad mb-3"><div class="filters"><div class="grow"><label class="form-label">بحث</label><input name="q" class="form-control" placeholder="الاسم أو اسم المستخدم" value="<?= e($qv) ?>"></div>
<div><label class="form-label">الدور</label><select name="role" class="form-select"><?= opts($roles, $rf ?: null, 'الكل') ?></select></div>
<div><label class="form-label">الحالة</label><select name="st" class="form-select"><?= opts(['1' => 'فعّال', '0' => 'معطّل'], $st, 'الكل') ?></select></div>
<div class="d-flex gap-2"><button class="btn btn-ink flex-fill"><i class="bi bi-search"></i> بحث</button><?= ($qv !== '' || $st !== '' || $rf) ? '<a class="btn btn-soft" href="./">مسح</a>' : '' ?></div></div></form>
<?php
$cols = [['label' => 'المستخدم', 'type' => 'html', 'f' => fn($r) => '<b>' . e($r['full_name']) . '</b><div class="small text-muted" dir="ltr">' . e($r['username']) . '</div>'],
    ['label' => 'الدور', 'type' => 'html', 'f' => fn($r) => badge($r['rl'], $r['rn'] === 'admin' ? 'warning' : 'info')], ['label' => 'آخر دخول', 'f' => fn($r) => $r['last_login_at'] ? dt($r['last_login_at']) : 'لم يدخل بعد'],
    ['label' => 'الحالة', 'type' => 'html', 'f' => fn($r) => active_badge($r['is_active'])],
    ['label' => '', 'type' => 'html', 'f' => fn($r) => '<div class="row-actions"><a class="btn-act btn" title="تعديل" href="' . e(qs(['edit' => $r['id']])) . '"><i class="bi bi-pencil-square"></i></a></div>']];
echo '<div class="panel">' . render_table($cols, $rows, ['empty' => empty_state('لا يوجد مستخدمون', 'لا نتائج مطابقة.', 'people')]) . '</div>';
$editing = !empty($edit['id']);
modal_open('userModal', $editing ? 'تعديل مستخدم' : 'إضافة مستخدم', $open); ?>
<form method="post" action="<?= e(qs([], ['edit'])) ?>"><div class="modal-body"><?= $err ? '<div class="alert alert-danger">' . e($err) . '</div>' : '' ?><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
<div class="row g-3"><div class="col-md-6"><label class="form-label">اسم المستخدم <span class="text-danger">*</span></label><input name="username" class="form-control" dir="ltr" required value="<?= e($edit['username'] ?? '') ?>" autocomplete="off"></div>
<div class="col-md-6"><label class="form-label">الاسم الكامل <span class="text-danger">*</span></label><input name="full_name" class="form-control" required value="<?= e($edit['full_name'] ?? '') ?>"></div>
<div class="col-md-6"><label class="form-label">الدور</label><select name="role_id" class="form-select"><?= opts($roles, $edit['role_id'] ?? array_key_last($roles)) ?></select></div>
<div class="col-md-6"><label class="form-label"><?= $editing ? 'كلمة مرور جديدة (اختياري)' : 'كلمة المرور (10 أحرف+)' ?></label><input type="password" name="password" class="form-control" autocomplete="new-password" <?= $editing ? '' : 'required' ?>></div>
<div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="ua" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label" for="ua">حساب فعّال</label></div></div></div></div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-gold"><i class="bi bi-check2"></i> حفظ</button></div></form>
<?php modal_close(); foot();
