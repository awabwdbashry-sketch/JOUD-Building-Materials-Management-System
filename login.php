<?php
require __DIR__ . '/includes/bootstrap.php';
if (me()) redirect(BASE . '/');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $fails = (int)q("SELECT COUNT(*) FROM audit_logs WHERE action='login_failed' AND ip=? AND created_at>NOW()-INTERVAL 15 MINUTE", [$ip])->fetchColumn();
    if (!csrf_ok()) $err = 'انتهت الجلسة، أعد المحاولة';
    elseif ($fails >= 8) $err = 'محاولات كثيرة خاطئة. انتظر 15 دقيقة ثم حاول مجددًا.';
    else {
        $un = trim($_POST['username'] ?? '');
        $u = q('SELECT u.* FROM users u JOIN roles r ON r.id=u.role_id WHERE u.username=? AND u.is_active=1 AND r.is_active=1', [$un])->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION = ['uid' => (int)$u['id'], 'last' => time(), 'csrf' => bin2hex(random_bytes(32))];
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($_POST['password'], PASSWORD_DEFAULT), $u['id']]);
            q('UPDATE users SET last_login_at=NOW() WHERE id=?', [$u['id']]);
            audit_for((int)$u['id'], 'login', 'user', $u['id'], ['user' => $u['full_name']]);
            redirect(BASE . '/');
        }
        audit_for(null, 'login_failed', 'user', null, ['username' => mb_substr($un, 0, 60)]);
        $err = 'اسم المستخدم أو كلمة المرور غير صحيحة';
    }
}
$logo = logo_url();
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>تسجيل الدخول | <?= e(company()) ?></title>
<link href="<?= BASE ?>/assets/vendor/bootstrap/bootstrap.rtl.min.css" rel="stylesheet"><link href="<?= BASE ?>/assets/vendor/icons/bootstrap-icons.min.css" rel="stylesheet"><link href="<?= BASE ?>/assets/css/joud.css" rel="stylesheet"></head>
<body class="login-wrap"><div class="login-card">
  <?= $logo ? '<img src="' . e($logo) . '" class="brand-logo" alt="">' : '<span class="brand-mark">J</span>' ?>
  <h1><?= e(company()) ?></h1><p class="sub">سجّل الدخول لإدارة المبيعات والمخزون</p>
  <?php if ($err) echo '<div class="alert alert-danger d-flex gap-2 align-items-center"><i class="bi bi-exclamation-octagon-fill"></i><div>' . e($err) . '</div></div>'; ?>
  <form method="post" autocomplete="on"><?= csrf_field() ?>
    <label class="form-label" for="username">اسم المستخدم</label>
    <div class="input-group mb-3"><span class="input-group-text"><i class="bi bi-person"></i></span><input id="username" name="username" class="form-control" required autofocus autocomplete="username"></div>
    <label class="form-label" for="password">كلمة المرور</label>
    <div class="input-group mb-4"><span class="input-group-text"><i class="bi bi-lock"></i></span><input id="password" type="password" name="password" class="form-control" required autocomplete="current-password"><button class="btn btn-soft" type="button" onclick="const i=document.getElementById('password');i.type=i.type==='password'?'text':'password'" aria-label="إظهار كلمة المرور"><i class="bi bi-eye"></i></button></div>
    <button class="btn btn-gold w-100 py-2">تسجيل الدخول</button>
  </form></div></body></html>
