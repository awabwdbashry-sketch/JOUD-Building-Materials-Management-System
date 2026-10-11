<?php
/**
 * JOUD — الهيكل المشترك للصفحات: الشريط الجانبي، الشريط العلوي، قائمة الموبايل (Offcanvas).
 * الاستخدام: head('العنوان','الصلاحية'); ... foot();
 */

/** عناصر القائمة: [المسار, العنوان, أيقونة, الصلاحية] — تُجمّع في أقسام */
function nav_items(): array {
    return [
        ['', [['index.php', 'لوحة التحكم', 'speedometer2', null]]],
        ['المبيعات', [
            ['pos/', 'نقطة البيع', 'cart-check', 'create_sale'],
            ['sales/', 'الفواتير', 'receipt', 'view_sales'],
            ['customers/', 'العملاء', 'people', 'manage_customers'],
            ['customers/payment.php', 'دفعة عميل', 'cash-coin', 'receive_customer_payment'],
        ]],
        ['المخزون والمشتريات', [
            ['products/', 'المنتجات', 'box-seam', 'view_products'],
            ['inventory/', 'المخزون', 'boxes', 'view_inventory'],
            ['purchases/', 'المشتريات', 'truck', 'view_purchases'],
            ['suppliers/', 'الموردون', 'shop', 'manage_suppliers'],
        ]],
        ['المالية', [
            ['expenses/', 'المصروفات', 'wallet2', 'manage_expenses'],
            ['reports/', 'التقارير', 'bar-chart-line', ['view_reports', 'view_sales_reports']],
        ]],
        ['الإعداد', [
            ['categories/', 'التصنيفات', 'tags', 'manage_catalog'],
            ['units/', 'الوحدات', 'rulers', 'manage_catalog'],
            ['payment_methods/', 'طرق الدفع', 'credit-card-2-front', 'manage_payment_methods'],
            ['expense_categories/', 'تصنيفات المصروفات', 'diagram-3', 'manage_expenses'],
        ]],
        ['الإدارة', [
            ['users/', 'المستخدمون', 'person-gear', 'manage_users'],
            ['roles/', 'الأدوار والصلاحيات', 'shield-lock', ['manage_users', 'manage_roles']],
            ['audit/', 'سجل العمليات', 'clock-history', 'view_audit'],
            ['settings/', 'الإعدادات', 'gear', 'manage_settings'],
        ]],
    ];
}

function nav_html(): string {
    $cur = ltrim(substr(parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH), strlen(BASE)), '/');
    $all = []; foreach (nav_items() as [, $its]) foreach ($its as $i) $all[] = $i[0];
    $exact = in_array($cur, $all, true);
    $o = '';
    foreach (nav_items() as [$grp, $its]) {
        $rows = '';
        foreach ($its as [$path, $label, $icon, $perm]) {
            if ($perm !== null && !(is_array($perm) ? can_any($perm) : can($perm))) continue;
            $act = $exact ? ($cur === $path) : ($path !== 'index.php' && str_ends_with($path, '/') && dirname($cur) . '/' === $path);
            if ($path === 'index.php' && $cur === 'index.php') $act = true;
            $rows .= '<a class="nav-link-j' . ($act ? ' active' : '') . '" href="' . BASE . '/' . $path . '"><i class="bi bi-' . $icon . '"></i><span>' . e($label) . '</span></a>';
        }
        if ($rows) $o .= ($grp ? '<div class="nav-group">' . e($grp) . '</div>' : '') . $rows;
    }
    return $o;
}

function brand_html(): string {
    $logo = logo_url();
    $mark = $logo ? '<img src="' . e($logo) . '" alt="" class="brand-logo">' : '<span class="brand-mark">J</span>';
    return '<a class="brand" href="' . BASE . '/">' . $mark . '<span class="brand-text"><b>' . e(company()) . '</b><small>JOUD</small></span></a>';
}

function head(string $title, $perm = null): void {
    need($perm);
    $u = me();
    ?><!doctype html>
<html lang="ar" dir="rtl"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= e($title) ?> | <?= e(company()) ?></title>
<link href="<?= BASE ?>/assets/vendor/bootstrap/bootstrap.rtl.min.css" rel="stylesheet">
<link href="<?= BASE ?>/assets/vendor/icons/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE ?>/assets/css/joud.css" rel="stylesheet">
</head><body class="app">
<aside class="sidebar d-none d-lg-flex" id="sidebar">
  <div class="sidebar-top"><?= brand_html() ?></div>
  <nav class="sidebar-nav"><?= nav_html() ?></nav>
  <div class="sidebar-user"><span class="avatar"><?= e(mb_substr($u['full_name'], 0, 1)) ?></span><div class="lh-sm"><div class="fw-semibold"><?= e($u['full_name']) ?></div><small><?= e($u['role_label']) ?></small></div><a class="ms-auto logout" href="<?= BASE ?>/logout.php" title="تسجيل الخروج"><i class="bi bi-box-arrow-left"></i></a></div>
</aside>

<div class="offcanvas offcanvas-end offcanvas-joud d-lg-none" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
  <div class="offcanvas-header"><div id="mobileNavLabel"><?= brand_html() ?></div><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="إغلاق"></button></div>
  <div class="offcanvas-body d-flex flex-column p-0">
    <nav class="sidebar-nav flex-grow-1"><?= nav_html() ?></nav>
    <div class="sidebar-user"><span class="avatar"><?= e(mb_substr($u['full_name'], 0, 1)) ?></span><div class="lh-sm"><div class="fw-semibold"><?= e($u['full_name']) ?></div><small><?= e($u['role_label']) ?></small></div><a class="ms-auto logout" href="<?= BASE ?>/logout.php"><i class="bi bi-box-arrow-left"></i></a></div>
  </div>
</div>

<div class="app-main">
  <header class="topbar no-print">
    <button class="btn btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="القائمة"><i class="bi bi-list"></i></button>
    <div class="topbar-title"><?= e($title) ?></div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <span class="topbar-date d-none d-md-inline"><i class="bi bi-calendar3"></i> <?= ar_day(today()) ?> <?= d(today()) ?></span>
      <a class="btn btn-icon d-lg-none" href="<?= BASE ?>/logout.php" aria-label="خروج"><i class="bi bi-box-arrow-left"></i></a>
    </div>
  </header>
  <main class="content"><?= flash_render() ?>
<?php }

/** عنوان الصفحة + أزرار الإجراءات */
function page_header(string $title, string $sub = '', string $actions = ''): void {
    echo '<div class="page-head"><div><h1>' . e($title) . '</h1>' . ($sub ? '<p>' . e($sub) . '</p>' : '') . '</div>' . ($actions ? '<div class="page-actions no-print">' . $actions . '</div>' : '') . '</div>';
}

function foot(string $scripts = ''): void { ?>
  </main>
  <footer class="app-footer no-print"><?= e(company()) ?> © <?= date('Y') ?> — نظام JOUD لإدارة مواد البناء</footer>
</div>
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-body text-center p-4"><div class="confirm-icon"><i class="bi bi-exclamation-triangle"></i></div><h5 class="mb-2" id="confirmTitle">تأكيد الإجراء</h5><p class="text-muted mb-4" id="confirmText"></p>
  <div class="d-flex gap-2 justify-content-center"><button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">إلغاء</button><button type="button" class="btn btn-danger px-4" id="confirmOk">تأكيد</button></div></div></div></div></div>
<script src="<?= BASE ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="<?= BASE ?>/assets/js/joud.js"></script>
<?= $scripts ?>
</body></html>
<?php }

/** هيكل نموذج داخل نافذة (Modal) — يُفتح تلقائيًا عند $open=true */
function modal_open(string $id, string $title, bool $open = false, string $size = ''): void {
    echo '<div class="modal fade" id="' . e($id) . '" tabindex="-1" aria-hidden="true"' . ($open ? ' data-auto-open="1"' : '') . '><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable ' . e($size) . '"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">' . e($title) . '</h5><button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>';
}
function modal_close(): void { echo '</div></div></div>'; }
