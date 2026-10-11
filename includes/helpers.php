<?php
/**
 * JOUD — دوال مساعدة مشتركة: تنسيق الأرقام والتواريخ، الترقيم، الجداول، الفلاتر.
 */

/* ---------- تنسيق الأرقام ---------- */
/** رقم بفواصل آلاف؛ الكسور تظهر فقط عند وجودها */
function num($n, int $dec = 2): string {
    $n = (float)$n;
    return number_format($n, abs($n - round($n)) < 0.005 ? 0 : $dec, '.', ',');
}
function money($n): string { return num($n); }
/** مبلغ مع العملة من الإعدادات */
function cur($n): string { return num($n) . ' ' . currency(); }
/** كمية (تحذف الأصفار الزائدة) */
function qty($n): string {
    $s = rtrim(rtrim(number_format((float)$n, 3, '.', ','), '0'), '.');
    return $s === '' ? '0' : $s;
}

/* ---------- التواريخ ---------- */
const AR_MONTHS = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
const AR_DAYS = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
function d($ts): string { return $ts ? date('d/m/Y', strtotime((string)$ts)) : ''; }
function dt($ts): string { return $ts ? date('d/m/Y H:i', strtotime((string)$ts)) : ''; }
function ar_day(string $date): string { return AR_DAYS[(int)date('w', strtotime($date))]; }
function is_date($s): bool { return is_string($s) && preg_match('/^\d{4}-\d\d-\d\d$/', $s) && checkdate((int)substr($s, 5, 2), (int)substr($s, 8, 2), (int)substr($s, 0, 4)); }
function today(): string { return date('Y-m-d'); }
function day_after(string $d): string { return date('Y-m-d', strtotime("$d +1 day")); }
/** بداية الأسبوع = السبت */
function week_start(string $date): string {
    $w = (int)date('w', strtotime($date));          // 0=الأحد ... 6=السبت
    $back = ($w + 1) % 7;                           // السبت => 0
    return date('Y-m-d', strtotime("$date -$back day"));
}
/**
 * يحوّل اختيار الفترة إلى مدى تواريخ: [من, إلى, عنوان, نوع]
 * النوع: day | week | month | year | custom
 */
function period_range(string $p, ?string $f = null, ?string $t = null): array {
    $now = today();
    switch ($p) {
        case 'week':  $a = week_start($now); return [$a, date('Y-m-d', strtotime("$a +6 day")), 'هذا الأسبوع', 'week'];
        case 'month': return [date('Y-m-01'), date('Y-m-t'), 'هذا الشهر', 'month'];
        case 'year':  return [date('Y-01-01'), date('Y-12-31'), 'هذه السنة', 'year'];
        case 'custom':
            if (is_date($f) && is_date($t)) { if ($f > $t) [$f, $t] = [$t, $f]; return [$f, $t, 'من ' . d($f) . ' إلى ' . d($t), 'custom']; }
            // لا break: عند التاريخ غير الصالح نرجع لليوم
        default: return [$now, $now, 'اليوم', 'day'];
    }
}

/* ---------- عناوين وروابط ---------- */
/** يدمج معاملات GET الحالية مع تغييرات ويبني query string */
function qs(array $over = [], array $drop = []): string {
    $q = array_merge($_GET, $over);
    foreach ($drop as $k) unset($q[$k]);
    foreach ($q as $k => $v) if ($v === '' || $v === null) unset($q[$k]);
    return $q ? '?' . http_build_query($q) : '?';
}

/* ---------- الترقيم ---------- */
function page_no(): int { return max(1, (int)($_GET['p'] ?? 1)); }
function pager(int $total, int $page, int $per = 25): string {
    $pages = max(1, (int)ceil($total / $per));
    if ($pages <= 1) return '<div class="pager-info small text-muted">' . num($total) . ' سجل</div>';
    $o = '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 no-print"><div class="pager-info small text-muted">' . num($total) . ' سجل — صفحة ' . $page . ' من ' . $pages . '</div><nav><ul class="pagination pagination-sm mb-0">';
    $link = fn($i, $label, $dis = false, $act = false) => '<li class="page-item' . ($dis ? ' disabled' : '') . ($act ? ' active' : '') . '"><a class="page-link" href="' . e(qs(['p' => $i])) . '">' . $label . '</a></li>';
    $o .= $link(max(1, $page - 1), '<i class="bi bi-chevron-right"></i>', $page <= 1);
    for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++) $o .= $link($i, $i, false, $i === $page);
    $o .= $link(min($pages, $page + 1), '<i class="bi bi-chevron-left"></i>', $page >= $pages);
    return $o . '</ul></nav></div>';
}

/* ---------- شارات الحالة ---------- */
function badge(string $text, string $tone = 'secondary'): string { return '<span class="badge badge-soft bg-' . e($tone) . '-subtle text-' . e($tone) . '-emphasis">' . e($text) . '</span>'; }
function sale_status_badge(array $s): string {
    if (($s['status'] ?? '') === 'cancelled') return badge('ملغاة', 'danger');
    return match ($s['pay_status'] ?? '') { 'paid' => badge('مدفوعة', 'success'), 'partial' => badge('جزئية', 'warning'), 'credit' => badge('آجلة', 'info'), default => badge('—') };
}
function active_badge($v): string { return $v ? badge('فعّال', 'success') : badge('معطّل', 'secondary'); }

/* ---------- الحالة الفارغة ---------- */
function empty_state(string $title, string $msg = '', string $icon = 'inbox', string $action = ''): string {
    return '<div class="empty-state"><i class="bi bi-' . e($icon) . '"></i><h5>' . e($title) . '</h5>' . ($msg ? '<p>' . e($msg) . '</p>' : '') . $action . '</div>';
}

/* ---------- الجداول المتجاوبة ---------- */
/**
 * يرسم جدولًا متجاوبًا (يتحول لبطاقات على الهاتف).
 * $cols: [['label'=>'..','key'=>'col' | 'f'=>fn($row)=>html, 'type'=>'text|money|qty|date|datetime|html|num', 'class'=>'']]
 */
function render_table(array $cols, array $rows, array $o = []): string {
    if (!$rows) return $o['empty'] ?? empty_state('لا توجد بيانات', 'لا توجد سجلات مطابقة.');
    $o2 = '<div class="table-wrap"><table class="table table-mobile align-middle mb-0"><thead><tr>';
    foreach ($cols as $c) $o2 .= '<th class="' . e($c['class'] ?? '') . '">' . e($c['label']) . '</th>';
    $o2 .= '</tr></thead><tbody>';
    foreach ($rows as $r) {
        $o2 .= '<tr' . (isset($o['row_class']) ? ' class="' . e($o['row_class']($r)) . '"' : '') . '>';
        foreach ($cols as $c) {
            $v = isset($c['f']) ? $c['f']($r) : ($r[$c['key']] ?? '');
            $type = $c['type'] ?? 'text';
            $cls = $c['class'] ?? '';
            $out = match ($type) {
                'money' => '<span class="amt">' . money($v) . '</span>',
                'qty' => qty($v),
                'num' => num($v),
                'date' => d($v),
                'datetime' => dt($v),
                'html' => (string)$v,
                default => e($v),
            };
            if (in_array($type, ['money', 'qty', 'num'])) $cls .= ' text-end-num';
            $o2 .= '<td class="' . e(trim($cls)) . '" data-label="' . e($c['label']) . '">' . $out . '</td>';
        }
        $o2 .= '</tr>';
    }
    return $o2 . '</tbody></table></div>';
}

/** قيمة خلية لأغراض CSV/PDF (نص بسيط) */
function cell_text(array $c, array $r): string {
    $v = isset($c['f']) ? $c['f']($r) : ($r[$c['key']] ?? '');
    if (($c['type'] ?? 'text') === 'html') $v = trim(html_entity_decode(strip_tags((string)$v), ENT_QUOTES, 'UTF-8'));
    return match ($c['type'] ?? 'text') { 'money' => money($v), 'qty' => qty($v), 'num' => num($v), 'date' => d($v), 'datetime' => dt($v), default => (string)$v };
}

/** إخراج ملف CSV (UTF-8 مع BOM ليفتح بالعربية في Excel) */
function csv_out(string $file, array $cols, array $rows): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $file) . '"');
    echo "\xEF\xBB\xBF";
    $o = fopen('php://output', 'w');
    fputcsv($o, array_map(fn($c) => $c['label'], $cols));
    foreach ($rows as $r) fputcsv($o, array_map(fn($c) => cell_text($c, $r), $cols));
    exit;
}

/* ---------- عناصر نماذج ---------- */
function opts(array $list, $sel = null, string $first = ''): string {
    $o = $first !== '' ? '<option value="">' . e($first) . '</option>' : '';
    foreach ($list as $k => $v) $o .= '<option value="' . e($k) . '"' . ((string)$sel === (string)$k && $sel !== null && $sel !== '' ? ' selected' : '') . '>' . e($v) . '</option>';
    return $o;
}
function pm_list(bool $activeOnly = true): array {
    return db()->query('SELECT id,name FROM payment_methods' . ($activeOnly ? ' WHERE is_active=1' : '') . ' ORDER BY id')->fetchAll(PDO::FETCH_KEY_PAIR);
}
function users_list(): array { return db()->query('SELECT id,full_name FROM users ORDER BY full_name')->fetchAll(PDO::FETCH_KEY_PAIR); }

/* ---------- رفع الملفات (الشعار) ---------- */
/** يرفع صورة بعد التحقق من النوع الحقيقي والحجم؛ يرجع اسم الملف الجديد أو يرمي استثناء */
function save_uploaded_image(array $f, string $dir, int $maxBytes = 1048576): string {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('فشل رفع الملف');
    if ($f['size'] > $maxBytes) throw new RuntimeException('حجم الصورة يجب ألا يتجاوز 1 ميجابايت');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/png' => 'png', 'image/jpeg' => 'jpg'][$mime] ?? null;
    if (!$ext || !@getimagesize($f['tmp_name'])) throw new RuntimeException('نوع الصورة غير مسموح (PNG أو JPG فقط)');
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = 'logo_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('تعذر حفظ الصورة');
    return $name;
}
function logo_url(): string { $l = setting('logo'); return $l && is_file(__DIR__ . '/../uploads/' . $l) ? BASE . '/uploads/' . $l : ''; }

/* ---------- أخطاء الأعمال والمعاملات ---------- */
/** يرمي خطأ أعمال برسالة عربية جاهزة للعرض */
function dom(string $m): never { throw new DomainException($m); }
function r2($n): float { return round((float)$n, 2); }
/** تشغيل دالة داخل Transaction مع إرجاع النتيجة */
function in_tx(PDO $d, callable $fn) {
    $own = !$d->inTransaction();
    if ($own) $d->beginTransaction();
    try { $r = $fn(); if ($own) $d->commit(); return $r; }
    catch (Throwable $x) { if ($own && $d->inTransaction()) $d->rollBack(); throw $x; }
}


function user_name(PDO $d, ?int $id): string {
    if (!$id) return '—';
    $s = $d->prepare('SELECT full_name FROM users WHERE id=?'); $s->execute([$id]); return (string)($s->fetchColumn() ?: '—');
}

/** رقم فاتورة الشراء المعروض (PUR-00012) */
function purchase_no(int $id): string { return 'PUR-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT); }
