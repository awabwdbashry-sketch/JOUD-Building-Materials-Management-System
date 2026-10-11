<?php
/** JOUD — صفحة التقارير التفاعلية المشتركة (تقارير + قائمة الفواتير): فلاتر، ملخص، جدول، ترقيم، طباعة، PDF، CSV */
require_once __DIR__ . '/reports.php';
require_once __DIR__ . '/pdf.php';

function report_filters_from_get(array $def): array {
    $F = [];
    foreach (['q', 'status', 'pay', 'pstatus', 'estatus'] as $k) $F[$k] = trim((string)($_GET[$k] ?? ''));
    foreach (['user', 'customer', 'method', 'supplier', 'category', 'expcat', 'pid'] as $k) $F[$k] = (int)($_GET[$k] ?? 0);
    $F['debtors'] = !empty($_GET['debtors']);
    if ($def['range'] !== 'none') {
        [$df, $dt] = report_default_range($def['range']);
        $F['f'] = array_key_exists('f', $_GET) ? (string)$_GET['f'] : $df;
        $F['t'] = array_key_exists('t', $_GET) ? (string)$_GET['t'] : $dt;
        if ($F['f'] !== '' && !is_date($F['f'])) $F['f'] = $df;
        if ($F['t'] !== '' && !is_date($F['t'])) $F['t'] = $dt;
        if ($F['f'] !== '' && $F['t'] !== '' && $F['f'] > $F['t']) [$F['f'], $F['t']] = [$F['t'], $F['f']];
    }
    $day = (string)($_GET['day'] ?? '');
    if ($day === 'today') $day = today();
    $F['day'] = is_date($day) ? $day : '';
    return $F;
}

function report_page(string $key, array $o = []): void {
    $defs = report_defs();
    if (!isset($defs[$key])) { $key = 'daily'; }
    $def = $defs[$key];
    $perm = $o['perm'] ?? $def['perm'];
    need($perm);
    $F = report_filters_from_get($def);
    $title = $o['title'] ?? $def['title'];
    $fmt = $_GET['fmt'] ?? '';
    $per = 25; $pg = page_no();
    $fn = $def['f'];

    /* ---- وصف الفترة والفلاتر (للطباعة وPDF) ---- */
    $dayMode = $key === 'daily' && $F['day'] !== '';
    if ($dayMode) { $title = ($F['day'] === today() ? 'تقرير مبيعات اليوم' : 'تقرير مبيعات يوم') . ' ' . d($F['day']); $period = ar_day($F['day']) . ' ' . d($F['day']); }
    elseif (($F['f'] ?? '') !== '' || ($F['t'] ?? '') !== '') $period = ($F['f'] ? 'من ' . d($F['f']) : '') . ' ' . ($F['t'] ? 'إلى ' . d($F['t']) : '');
    else $period = ($def['range'] === 'none') ? 'الوضع الحالي' : 'كل الفترات';
    $meta = [['الفترة', trim($period)]];
    if ($F['user'] && !scope_user()) $meta[] = ['الكاشير', users_list()[$F['user']] ?? ''];
    if ($F['q'] !== '') $meta[] = ['بحث', $F['q']];

    /* ---- التصدير ---- */
    if ($fmt === 'csv' || $fmt === 'pdf') {
        $R = $fn($F, 5000, 0);
        $cols = array_values(array_filter($R['cols'], fn($c) => ($c['pdf'] ?? true) !== false && $c['label'] !== ''));
        audit('report_exported', 'report', null, ['report' => $title, 'format' => strtoupper($fmt), 'rows' => count($R['rows'])]);
        if ($fmt === 'csv') csv_out($key . '-' . date('Ymd') . '.csv', $cols, $R['rows']);
        $sum = array_map(fn($s) => [$s[0], $s[1]], array_slice($R['summary'], 0, 8));
        pdf_send(pdf_table($title, $meta, $sum, $cols, $R['rows']), $key . '-' . date('Ymd') . '.pdf');
    }

    $R = $fn($F, $per, ($pg - 1) * $per);
    head($title, $perm);

    $acts = '<button class="btn btn-outline-ink" onclick="print()"><i class="bi bi-printer"></i> طباعة</button><a class="btn btn-outline-ink" href="' . e(qs(['fmt' => 'pdf'], ['p'])) . '"><i class="bi bi-file-earmark-pdf"></i> PDF</a><a class="btn btn-outline-ink" href="' . e(qs(['fmt' => 'csv'], ['p'])) . '"><i class="bi bi-filetype-csv"></i> CSV</a>';
    if ($dayMode) $acts = '<a class="btn btn-soft" href="' . e(qs([], ['day', 'p'])) . '"><i class="bi bi-arrow-right"></i> كل الأيام</a>' . $acts;
    if (!empty($R['back'])) $acts = '<a class="btn btn-soft" href="' . BASE . '/reports/?r=' . e($R['back']) . '&f=' . e($F['f'] ?? '') . '&t=' . e($F['t'] ?? '') . '"><i class="bi bi-arrow-right"></i> رجوع</a>' . $acts;
    page_header($title, trim($period), $acts);

    echo '<div class="print-head"><h3 class="mb-0">' . e(company()) . '</h3><div class="small">' . e(setting('phone')) . ' ' . e(setting('address')) . '</div><h4 class="mt-2">' . e($title) . '</h4><div class="small">' . e(trim($period)) . ' — طُبع ' . dt(date('Y-m-d H:i')) . '</div></div>';

    /* ---- شريط التقارير ---- */
    if (($o['nav'] ?? true)) {
        echo '<div class="chips mb-3 no-print">';
        foreach ($defs as $k => $dd) { if (!empty($dd['hidden']) || !report_allowed($dd)) continue; echo '<a class="chip' . ($k === $key || ($key === 'sales' && $k === 'daily') || ($key === 'product_lines' && $k === 'product') ? ' on' : '') . '" href="?r=' . $k . '"><i class="bi bi-' . $dd['icon'] . '"></i> ' . e($dd['title']) . '</a>'; }
        echo '</div>';
    }

    /* ---- الفلاتر ---- */
    $fl = $def['filters']; $inp = '';
    $sel = fn($name, $label, $list, $cur) => '<div><label class="form-label">' . $label . '</label><select name="' . $name . '" class="form-select">' . opts($list, $cur ?: null, 'الكل') . '</select></div>';
    if ($dayMode) $inp .= '<div><label class="form-label">اليوم</label><input type="date" name="day" class="form-control" value="' . e($F['day']) . '" max="' . today() . '"></div>';
    elseif (in_array('date', $fl)) {
        $inp .= '<div><label class="form-label">من تاريخ</label><input type="date" name="f" class="form-control" value="' . e($F['f']) . '"></div><div><label class="form-label">إلى تاريخ</label><input type="date" name="t" class="form-control" value="' . e($F['t']) . '"></div>';
    }
    if (in_array('q', $fl)) $inp .= '<div class="grow"><label class="form-label">بحث</label><input name="q" class="form-control" placeholder="ابحث..." value="' . e($F['q']) . '"></div>';
    if (in_array('user', $fl) && !scope_user()) $inp .= $sel('user', 'المستخدم / الكاشير', users_list(), $F['user']);
    if (in_array('customer', $fl)) $inp .= $sel('customer', 'العميل', db()->query('SELECT id,name FROM customers ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR), $F['customer']);
    if (in_array('method', $fl)) $inp .= $sel('method', 'طريقة الدفع', pm_list(false), $F['method']);
    if (in_array('status', $fl)) $inp .= '<div><label class="form-label">حالة الفاتورة</label><select name="status" class="form-select">' . opts(['completed' => 'مكتملة', 'cancelled' => 'ملغاة'], $F['status'], 'الكل') . '</select></div>';
    if (in_array('pay', $fl)) $inp .= '<div><label class="form-label">حالة الدفع</label><select name="pay" class="form-select">' . opts(['paid' => 'مدفوعة', 'partial' => 'جزئية', 'credit' => 'آجلة', 'due' => 'غير مسددة بالكامل'], $F['pay'], 'الكل') . '</select></div>';
    if (in_array('supplier', $fl)) $inp .= $sel('supplier', 'المورد', db()->query('SELECT id,name FROM suppliers ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR), $F['supplier']);
    if (in_array('category', $fl)) $inp .= $sel('category', 'التصنيف', db()->query('SELECT id,name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR), $F['category']);
    if (in_array('expcat', $fl)) $inp .= $sel('expcat', 'تصنيف المصروف', db()->query('SELECT id,name FROM expense_categories ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR), $F['expcat']);
    if (in_array('pstatus', $fl)) $inp .= '<div><label class="form-label">الحالة</label><select name="pstatus" class="form-select">' . opts(['paid' => 'مدفوعة بالكامل', 'partial' => 'مدفوعة جزئيًا', 'credit' => 'آجلة', 'cancelled' => 'ملغاة'], $F['pstatus'], 'الكل') . '</select></div>';
    if (in_array('estatus', $fl)) $inp .= '<div><label class="form-label">الحالة</label><select name="estatus" class="form-select">' . opts(['active' => 'نشط', 'cancelled' => 'ملغى'], $F['estatus'], 'الكل') . '</select></div>';
    if (in_array('debtors', $fl)) $inp .= '<div class="form-check form-switch pb-2"><input class="form-check-input" type="checkbox" name="debtors" value="1" id="debt"' . ($F['debtors'] ? ' checked' : '') . '><label class="form-check-label" for="debt">المدينون فقط</label></div>';
    $hidden = '<input type="hidden" name="r" value="' . e($key === 'sales' && ($o['keep_r'] ?? true) === false ? '' : $key) . '">' . ($key === 'product_lines' ? '<input type="hidden" name="pid" value="' . $F['pid'] . '">' : '');
    if (($o['keep_r'] ?? true) === false) $hidden = '';
    echo '<form class="panel panel-pad mb-3 no-print" method="get">' . $hidden . '<div class="filters">' . $inp . '<div class="d-flex gap-2"><button class="btn btn-ink flex-fill"><i class="bi bi-funnel"></i> عرض</button><a class="btn btn-soft" href="?' . (($o['keep_r'] ?? true) === false ? '' : 'r=' . $key . ($key === 'product_lines' ? '&pid=' . $F['pid'] : '') . '&') . 'f=&t=">مسح</a></div></div></form>';

    /* ---- الملخص ---- */
    if (!empty($R['summary'])) {
        $ic = ['gold' => ['', 'cash-stack'], 'ok' => ['t-ok', 'check2-circle'], 'bad' => ['t-bad', 'hourglass-split'], 'info' => ['t-info', 'credit-card'], 'ink' => ['t-ink', 'hash']];
        echo '<div class="row g-3 mb-3">';
        foreach ($R['summary'] as [$l, $v, $tone]) echo '<div class="col-6 col-lg-3 col-xl-' . (count($R['summary']) > 6 ? '2' : '3') . '"><div class="kpi ' . $ic[$tone][0] . '"><span class="kpi-ico"><i class="bi bi-' . $ic[$tone][1] . '"></i></span><div class="kpi-body"><div class="kpi-label">' . e($l) . '</div><div class="kpi-value" style="font-size:1.15rem">' . e($v) . '</div></div></div></div>';
        echo '</div>';
    }
    $cols = $R['cols'];
    echo '<div class="panel">' . render_table($cols, $R['rows'], ['row_class' => $R['row_class'] ?? null, 'empty' => empty_state('لا توجد بيانات', $o['empty'] ?? 'لا توجد سجلات ضمن الفترة والفلاتر المحددة.', 'bar-chart-line')]) . '</div><div class="mt-3">' . pager($R['total'], $pg, $per) . '</div>';
    foot();
}
