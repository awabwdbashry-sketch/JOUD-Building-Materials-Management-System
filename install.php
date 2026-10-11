<?php
/** إنشاء أول مدير — يعمل مرة واحدة فقط ثم يُقفل. احذف هذا الملف بعد التثبيت. */
define('JOUD_NO_MIGRATE', true);
require __DIR__ . '/includes/bootstrap.php';
ensure_schema();
$lock = __DIR__ . '/config/installed.lock';
if (is_file($lock) || (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) { http_response_code(403); exit('التثبيت مكتمل مسبقًا. احذف ملف install.php من الخادم.'); }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? ''); $n = trim($_POST['full_name'] ?? ''); $p = $_POST['password'] ?? '';
    if (!csrf_ok()) $err = 'انتهت الجلسة';
    elseif (!preg_match('/^[A-Za-z0-9_.]{3,40}$/', $u) || $n === '') $err = 'اسم المستخدم (حروف إنجليزية/أرقام 3-40) والاسم مطلوبان';
    elseif (strlen($p) < 10 || $p !== ($_POST['password2'] ?? '')) $err = 'كلمة المرور 10 أحرف على الأقل ويجب أن تتطابق';
    else {
        $rid = (int)q("SELECT id FROM roles WHERE name='admin'")->fetchColumn();
        q('INSERT INTO users(username,full_name,password_hash,role_id) VALUES(?,?,?,?)', [$u, $n, password_hash($p, PASSWORD_DEFAULT), $rid]);
        @file_put_contents($lock, date('c'));
        exit('<!doctype html><meta charset="utf-8"><body dir="rtl" style="font-family:Tahoma;padding:2rem">تم إنشاء المدير. احذف install.php الآن ثم <a href="login.php">سجّل الدخول</a>.</body>');
    }
}
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>تثبيت JOUD</title>
<link href="assets/vendor/bootstrap/bootstrap.rtl.min.css" rel="stylesheet"><link href="assets/vendor/icons/bootstrap-icons.min.css" rel="stylesheet"><link href="assets/css/joud.css" rel="stylesheet"></head>
<body class="login-wrap"><form method="post" class="login-card"><span class="brand-mark">J</span><h1>إنشاء أول مدير</h1><p class="sub">خطوة التثبيت الأخيرة</p>
<?php if ($err) echo '<div class="alert alert-danger">' . e($err) . '</div>'; ?><?= csrf_field() ?>
<input name="username" class="form-control mb-2" placeholder="اسم المستخدم" required><input name="full_name" class="form-control mb-2" placeholder="الاسم الكامل" required>
<input type="password" name="password" class="form-control mb-2" placeholder="كلمة المرور (10 أحرف+)" required autocomplete="new-password"><input type="password" name="password2" class="form-control mb-3" placeholder="تأكيد كلمة المرور" required autocomplete="new-password"><button class="btn btn-gold w-100">تثبيت</button></form></body></html>
