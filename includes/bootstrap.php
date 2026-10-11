<?php
/**
 * JOUD — النواة: الإعدادات، الجلسة، قاعدة البيانات، الصلاحيات، سجل العمليات.
 */
require __DIR__ . '/../config/config.php';

if (!APP_DEBUG) { ini_set('display_errors', '0'); }
error_reporting(E_ALL);

/* ---------- الجلسة ---------- */
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('JOUDSESS');
session_set_cookie_params(['path' => '/', 'httponly' => true, 'secure' => $https, 'samesite' => 'Lax']);
session_start();

if (PHP_SAPI !== 'cli') {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

/* ---------- قاعدة البيانات ---------- */
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            // توحيد المنطقة الزمنية بين PHP وMySQL حتى تتطابق "اليوم/الأسبوع" في التقارير
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone='" . date('P') . "'",
        ]);
    }
    return $pdo;
}

/** تنفيذ استعلام وإرجاع الـ statement */
function q(string $sql, array $p = []): PDOStatement {
    $s = db()->prepare($sql);
    $s->execute($p);
    return $s;
}

/* ---------- أدوات أساسية ---------- */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_ok(): bool {
    return hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? ''));
}
function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { fail_page(419, 'انتهت صلاحية الجلسة', 'أعد تحميل الصفحة وحاول مرة أخرى.'); }
}
function redirect(string $to): void {
    if ($to === '' || $to[0] === '?') $to = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') . $to;   // رابط نسبي بالـ query فقط
    header('Location: ' . $to); exit;
}
function url(string $path = ''): string { return BASE . '/' . ltrim($path, '/'); }

/* ---------- الإعدادات (من قاعدة البيانات) ---------- */
function settings(bool $refresh = false): array {
    static $S;
    if ($S === null || $refresh) {
        try { $S = db()->query('SELECT k,v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR); } catch (Throwable $x) { $S = []; }
    }
    return $S;
}
function setting(string $k, string $default = ''): string {
    $v = settings()[$k] ?? '';
    return $v === '' ? $default : $v;
}
function company(): string { return setting('company_name', 'JOUD'); }
function currency(): string { return setting('currency', 'ج.س'); }

/* ---------- المستخدم والصلاحيات (تُقرأ من القاعدة في كل طلب) ---------- */
function load_identity(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    if (empty($_SESSION['uid'])) return;
    $idle = SESSION_IDLE_MINUTES * 60;
    if (time() - ($_SESSION['last'] ?? 0) > $idle) { session_unset(); return; }
    $s = q('SELECT u.id,u.full_name,u.username,u.role_id,r.name role_name,r.label role_label FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.is_active=1 AND r.is_active=1', [$_SESSION['uid']]);
    $u = $s->fetch();
    if (!$u) { session_unset(); return; }
    $_SESSION['last'] = time();
    $_SESSION['user'] = $u;
    $_SESSION['perms'] = q('SELECT p.code FROM role_permissions rp JOIN permissions p ON p.id=rp.permission_id WHERE rp.role_id=?', [$u['role_id']])->fetchAll(PDO::FETCH_COLUMN);
}
function me(): ?array { load_identity(); return !empty($_SESSION['uid']) ? ($_SESSION['user'] ?? null) : null; }
function can(string $p): bool { load_identity(); return !empty($_SESSION['uid']) && in_array($p, $_SESSION['perms'] ?? [], true); }
function can_any(array $ps): bool { foreach ($ps as $p) if (can($p)) return true; return false; }
/** سياق الصلاحيات الذي تستخدمه خدمات المحاسبة (يسمح بالاختبار خارج المتصفح) */
function ctx(): array { return ['user_id' => me()['id'] ?? 0, 'perms' => $_SESSION['perms'] ?? []]; }

function wants_json(): bool {
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'api');
}
function fail_page(int $code, string $title, string $msg): void {
    http_response_code($code);
    if (wants_json()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['error' => $title . ' — ' . $msg], JSON_UNESCAPED_UNICODE); exit; }
    require_once __DIR__ . '/layout.php';
    if (me()) { head($title); } else { echo '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($title) . '</title><link href="' . BASE . '/assets/vendor/bootstrap/bootstrap.rtl.min.css" rel="stylesheet"><link href="' . BASE . '/assets/css/joud.css" rel="stylesheet"></head><body><main class="container p-4">'; }
    echo '<div class="empty-state"><i class="bi bi-shield-exclamation"></i><h4>' . e($title) . '</h4><p>' . e($msg) . '</p><a class="btn btn-gold" href="' . BASE . '/">العودة للرئيسية</a></div>';
    if (me()) foot(); else echo '</main></body></html>';
    exit;
}
/** يتطلب تسجيل الدخول، وصلاحية واحدة (أو أكثر — يكفي واحدة منها) */
function need($perm = null): void {
    if (!me()) {
        if (wants_json()) { http_response_code(401); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['error' => 'سجّل الدخول أولًا']); exit; }
        redirect(BASE . '/login.php');
    }
    if ($perm !== null) {
        $ok = is_array($perm) ? can_any($perm) : can($perm);
        if (!$ok) fail_page(403, 'غير مصرح', 'لا تملك صلاحية الوصول إلى هذه الصفحة أو تنفيذ هذا الإجراء.');
    }
}

/* ---------- سجل العمليات ---------- */
function audit_for(?int $uid, string $action, ?string $entity = null, $id = null, $details = null): void {
    q('INSERT INTO audit_logs(user_id,action,entity,entity_id,details,ip) VALUES(?,?,?,?,?,?)', [
        $uid, $action, $entity, $id, is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $details, $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
function audit(string $action, ?string $entity = null, $id = null, $details = null): void { audit_for(me()['id'] ?? null, $action, $entity, $id, $details); }

/* ---------- رسائل فورية (Flash) ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flash_render(): string {
    $o = '';
    foreach ($_SESSION['flash'] ?? [] as [$t, $m]) {
        $icon = $t === 'success' ? 'check-circle-fill' : ($t === 'danger' ? 'exclamation-octagon-fill' : 'info-circle-fill');
        $o .= '<div class="alert alert-' . e($t) . ' alert-dismissible fade show d-flex align-items-center gap-2" role="alert"><i class="bi bi-' . $icon . '"></i><div>' . e($m) . '</div><button type="button" class="btn-close me-auto ms-0" data-bs-dismiss="alert" aria-label="إغلاق"></button></div>';
    }
    unset($_SESSION['flash']);
    return $o;
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/migrate.php';
require_once __DIR__ . '/layout.php';
if (PHP_SAPI !== 'cli' && !defined('JOUD_NO_MIGRATE')) { ensure_schema(); }
