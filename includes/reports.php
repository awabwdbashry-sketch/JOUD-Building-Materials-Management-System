<?php
/**
 * JOUD — محرك التقارير. كل تقرير = تعريف (عنوان، صلاحية، فلاتر، دالة تشغيل).
 * دالة التشغيل ترجع: ['cols'=>[], 'rows'=>[], 'total'=>int, 'summary'=>[[label, نص, tone, رابط?]], 'foot'=>[]]
 * نفس النتيجة تُستخدم للعرض والطباعة وPDF وCSV (لا أرقام ثابتة: كل شيء من SQL).
 */
const SALES_PERMS = ['view_reports', 'view_sales_reports'];

/** إذا لم يملك المستخدم view_all_sales يرى بياناته فقط */
function scope_user(): int { return can('view_all_sales') ? 0 : (int)(me()['id'] ?? 0); }

function report_defs(): array {
    return [
        'daily'    => ['title' => 'المبيعات اليومية',   'icon' => 'calendar-day',   'perm' => SALES_PERMS,     'filters' => ['date', 'user'],                         'f' => 'rep_daily',     'range' => 'month'],
        'sales'    => ['title' => 'كل الفواتير',        'icon' => 'receipt',        'perm' => SALES_PERMS,     'filters' => ['date', 'q', 'user', 'customer', 'method', 'status', 'pay'], 'f' => 'rep_sales', 'range' => 'month', 'hidden' => true],
        'monthly'  => ['title' => 'المبيعات الشهرية',   'icon' => 'calendar3',      'perm' => SALES_PERMS,     'filters' => ['date', 'user'],                         'f' => 'rep_monthly',   'range' => 'year'],
        'cashier'  => ['title' => 'مبيعات الكاشير',     'icon' => 'person-badge',   'perm' => SALES_PERMS,     'filters' => ['date'],                                 'f' => 'rep_cashier',   'range' => 'month'],
        'methods'  => ['title' => 'طرق الدفع',          'icon' => 'credit-card',    'perm' => SALES_PERMS,     'filters' => ['date', 'method'],                       'f' => 'rep_methods',   'range' => 'month'],
        'product'  => ['title' => 'مبيعات المنتجات',    'icon' => 'box-seam',       'perm' => 'view_reports',  'filters' => ['date', 'q', 'category'],                'f' => 'rep_product',   'range' => 'month'],
        'product_lines' => ['title' => 'مبيعات منتج',   'icon' => 'box-seam',       'perm' => 'view_reports',  'filters' => ['date'],                                 'f' => 'rep_product_lines', 'range' => 'month', 'hidden' => true],
        'inventory' => ['title' => 'تقرير المخزون',     'icon' => 'boxes',          'perm' => 'view_reports',  'filters' => ['q', 'category'],                        'f' => 'rep_inventory', 'range' => 'none'],
        'low'      => ['title' => 'المخزون المنخفض',    'icon' => 'exclamation-triangle', 'perm' => 'view_reports', 'filters' => ['q', 'category'],                   'f' => 'rep_low',       'range' => 'none'],
        'purchases' => ['title' => 'المشتريات',         'icon' => 'truck',          'perm' => 'view_reports',  'filters' => ['date', 'q', 'supplier', 'pstatus'],     'f' => 'rep_purchases', 'range' => 'month'],
        'expenses' => ['title' => 'المصروفات',          'icon' => 'wallet2',        'perm' => 'view_reports',  'filters' => ['date', 'q', 'expcat', 'user', 'estatus'], 'f' => 'rep_expenses', 'range' => 'month'],
        'suppliers' => ['title' => 'أرصدة الموردين',    'icon' => 'shop',           'perm' => 'view_reports',  'filters' => ['q', 'debtors'],                         'f' => 'rep_suppliers', 'range' => 'none'],
        'supplier_payments' => ['title' => 'دفعات الموردين', 'icon' => 'cash-stack', 'perm' => 'view_reports', 'filters' => ['date', 'q', 'supplier', 'method'],       'f' => 'rep_supplier_payments', 'range' => 'month'],
        'customers' => ['title' => 'أرصدة العملاء',     'icon' => 'people',         'perm' => 'view_reports',  'filters' => ['q', 'debtors'],                         'f' => 'rep_customers', 'range' => 'none'],
        'payments' => ['title' => 'دفعات العملاء',      'icon' => 'cash-coin',      'perm' => SALES_PERMS,     'filters' => ['date', 'q', 'customer', 'method', 'user'], 'f' => 'rep_payments', 'range' => 'month'],
    ];
}
function report_allowed(array $def): bool { return is_array($def['perm']) ? can_any($def['perm']) : can($def['perm']); }

/** يجلب صفحة من استعلام + العدد الكلي */
function rep_fetch(string $sql, array $p, int $limit, int $offset): array {
    $total = (int)q("SELECT COUNT(*) FROM ($sql) x", $p)->fetchColumn();
    $rows = q($sql . ($limit ? " LIMIT $limit OFFSET $offset" : ''), $p)->fetchAll();
    return [$rows, $total];
}
function rep_dates(string $col, array $F, array &$w, array &$p): void {
    if (is_date($F['f'] ?? '')) { $w[] = "$col>=?"; $p[] = $F['f']; }
    if (is_date($F['t'] ?? '')) { $w[] = "$col<?"; $p[] = day_after($F['t']); }
}
function rep_date_only(string $col, array $F, array &$w, array &$p): void {
    if (is_date($F['f'] ?? '')) { $w[] = "$col>=?"; $p[] = $F['f']; }
    if (is_date($F['t'] ?? '')) { $w[] = "$col<=?"; $p[] = $F['t']; }
}
function rep_sales_where(array $F, array &$p, bool $withStatus = true): string {
    $w = ['1'];
    if ($u = scope_user()) { $w[] = 's.user_id=?'; $p[] = $u; } elseif (!empty($F['user'])) { $w[] = 's.user_id=?'; $p[] = (int)$F['user']; }
    rep_dates('s.created_at', $F, $w, $p);
    if ($withStatus && in_array($F['status'] ?? '', ['completed', 'cancelled'], true)) { $w[] = 's.status=?'; $p[] = $F['status']; }
    if (($F['pay'] ?? '') === 'due') { $w[] = "s.status='completed' AND s.due>0"; }
    elseif (in_array($F['pay'] ?? '', ['paid', 'partial', 'credit'], true)) { $w[] = "s.status='completed' AND s.pay_status=?"; $p[] = $F['pay']; }
    if (!empty($F['customer'])) { $w[] = 's.customer_id=?'; $p[] = (int)$F['customer']; }
    if (!empty($F['method'])) { $w[] = 'EXISTS(SELECT 1 FROM payment_allocations pa JOIN payments pp ON pp.id=pa.payment_id WHERE pa.sale_id=s.id AND pa.amount>0 AND pp.payment_method_id=?)'; $p[] = (int)$F['method']; }
    if (($F['q'] ?? '') !== '') { $w[] = '(s.invoice_no LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)'; array_push($p, '%' . $F['q'] . '%', '%' . $F['q'] . '%', '%' . $F['q'] . '%'); }
    return implode(' AND ', $w);
}
const SALES_FROM = 'sales s JOIN users u ON u.id=s.user_id LEFT JOIN customers c ON c.id=s.customer_id';
function sale_link(array $r): string { return can('view_sales') ? '<a href="' . BASE . '/sales/invoice.php?id=' . $r['id'] . '"><b>' . e($r['invoice_no']) . '</b></a>' : e($r['invoice_no']); }

/** ملخص مبيعات (للفواتير غير الملغاة) مع تفصيل طرق الدفع والآجل */
function rep_sales_summary(array $F): array {
    $p = []; $W = rep_sales_where($F, $p, false);
    $s = q("SELECT COUNT(*) n,COALESCE(SUM(s.total),0) t,COALESCE(SUM(s.paid),0) pd,COALESCE(SUM(s.due),0) du,COALESCE(SUM(s.discount_total),0) dc FROM " . SALES_FROM . " WHERE $W AND s.status='completed'", $p)->fetch();
    $p2 = []; $W2 = rep_sales_where($F, $p2, false);
    $m = q("SELECT pm.name,COALESCE(SUM(pa.amount),0) a FROM payment_allocations pa JOIN payments pp ON pp.id=pa.payment_id JOIN payment_methods pm ON pm.id=pp.payment_method_id JOIN sales s ON s.id=pa.sale_id JOIN users u ON u.id=s.user_id LEFT JOIN customers c ON c.id=s.customer_id WHERE $W2 AND s.status='completed' GROUP BY pm.id,pm.name ORDER BY pm.id", $p2)->fetchAll();
    $p3 = []; $W3 = rep_sales_where($F, $p3, false);
    $cn = (int)q("SELECT COUNT(*) FROM " . SALES_FROM . " WHERE $W3 AND s.status='cancelled'", $p3)->fetchColumn();
    $out = [['عدد الفواتير', num($s['n']), 'ink'], ['إجمالي المبيعات', cur($s['t']), 'gold'], ['المدفوع', cur($s['pd']), 'ok'], ['المتبقي (آجل)', cur($s['du']), 'bad']];
    foreach ($m as $x) $out[] = [$x['name'], cur($x['a']), 'info'];
    $out[] = ['الخصومات', cur($s['dc']), 'ink'];
    if ($cn) $out[] = ['فواتير ملغاة', num($cn), 'bad'];
    return $out;
}

function rep_sales(array $F, int $limit, int $off): array {
    $p = []; $W = rep_sales_where($F, $p);
    $meth = "(SELECT GROUP_CONCAT(DISTINCT pm.name ORDER BY pm.id SEPARATOR '، ') FROM payment_allocations pa JOIN payments pp ON pp.id=pa.payment_id JOIN payment_methods pm ON pm.id=pp.payment_method_id WHERE pa.sale_id=s.id AND pa.amount>0)";
    [$rows, $n] = rep_fetch("SELECT s.id,s.invoice_no,s.created_at,c.name customer,u.full_name cashier,s.total,s.paid,s.due,s.pay_status,s.status,$meth methods FROM " . SALES_FROM . " WHERE $W ORDER BY s.id DESC", $p, $limit, $off);
    $cols = [
        ['label' => 'رقم الفاتورة', 'type' => 'html', 'f' => 'sale_link', 'w' => 1.3], ['label' => 'التاريخ', 'key' => 'created_at', 'type' => 'datetime', 'w' => 1.3],
        ['label' => 'العميل', 'f' => fn($r) => $r['customer'] ?: 'نقدي', 'w' => 1.4], ['label' => 'الكاشير', 'key' => 'cashier'],
        ['label' => 'الإجمالي', 'key' => 'total', 'type' => 'money'], ['label' => 'المدفوع', 'key' => 'paid', 'type' => 'money'],
        ['label' => 'المتبقي', 'f' => fn($r) => $r['status'] === 'cancelled' ? 0 : $r['due'], 'type' => 'money'],
        ['label' => 'طريقة الدفع', 'f' => fn($r) => $r['methods'] ?: ($r['status'] === 'cancelled' ? '—' : 'آجل'), 'w' => 1.2],
        ['label' => 'الحالة', 'type' => 'html', 'f' => fn($r) => sale_status_badge($r)],
        ['label' => '', 'type' => 'html', 'f' => fn($r) => '<div class="row-actions no-print"><a class="btn-act btn" title="عرض" href="' . BASE . '/sales/invoice.php?id=' . $r['id'] . '"><i class="bi bi-eye"></i></a><a class="btn-act btn" title="طباعة A4" href="' . BASE . '/sales/invoice.php?id=' . $r['id'] . '&print=1" target="_blank"><i class="bi bi-printer"></i></a><a class="btn-act btn" title="PDF" href="' . BASE . '/sales/pdf.php?id=' . $r['id'] . '"><i class="bi bi-file-earmark-pdf"></i></a></div>', 'pdf' => false],
    ];
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => rep_sales_summary($F), 'row_class' => fn($r) => $r['status'] === 'cancelled' ? 'row-cancelled' : ''];
}

function rep_daily(array $F, int $limit, int $off): array {
    if (!empty($F['day'])) { $F['f'] = $F['t'] = $F['day']; return rep_sales($F, $limit, $off); }   // التفصيل: فواتير اليوم
    $p = []; $W = rep_sales_where($F, $p, false);
    $own = scope_user() ? ' AND pp.user_id=' . scope_user() : '';
    [$rows, $n] = rep_fetch("SELECT DATE(s.created_at) day,COUNT(*) n,SUM(s.total) total,SUM(s.paid) paid,SUM(s.due) due,SUM(s.discount_total) disc,(SELECT COUNT(*) FROM payments pp WHERE pp.payment_date=DATE(s.created_at) AND pp.amount>0$own) ops FROM " . SALES_FROM . " WHERE $W AND s.status='completed' GROUP BY DATE(s.created_at) ORDER BY day DESC", $p, $limit, $off);
    $lnk = fn($r) => '<a href="' . e(qs(['day' => $r['day'], 'p' => null])) . '"><b>' . d($r['day']) . '</b></a> <small class="text-muted">' . ar_day($r['day']) . '</small>';
    $cols = [['label' => 'التاريخ', 'type' => 'html', 'f' => $lnk, 'w' => 1.5], ['label' => 'عدد الفواتير', 'key' => 'n', 'type' => 'num'], ['label' => 'إجمالي المبيعات', 'key' => 'total', 'type' => 'money'], ['label' => 'المدفوع', 'key' => 'paid', 'type' => 'money'], ['label' => 'المتبقي', 'key' => 'due', 'type' => 'money'], ['label' => 'الخصومات', 'key' => 'disc', 'type' => 'money'], ['label' => 'عدد الدفعات', 'key' => 'ops', 'type' => 'num'],
        ['label' => '', 'type' => 'html', 'f' => fn($r) => '<a class="btn-act btn no-print" title="فواتير اليوم" href="' . e(qs(['day' => $r['day'], 'p' => null])) . '"><i class="bi bi-eye"></i></a>', 'pdf' => false]];
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => rep_sales_summary($F)];
}

function rep_monthly(array $F, int $limit, int $off): array {
    $p = []; $W = rep_sales_where($F, $p, false);
    [$rows, $n] = rep_fetch("SELECT DATE_FORMAT(s.created_at,'%Y-%m') mon,COUNT(*) n,SUM(s.total) total,SUM(s.paid) paid,SUM(s.due) due,SUM(s.discount_total) disc FROM " . SALES_FROM . " WHERE $W AND s.status='completed' GROUP BY mon ORDER BY mon DESC", $p, $limit, $off);
    $lnk = function ($r) { $a = $r['mon'] . '-01'; return '<a href="' . e(BASE . '/reports/?r=daily&f=' . $a . '&t=' . date('Y-m-t', strtotime($a))) . '"><b>' . AR_MONTHS[(int)substr($r['mon'], 5)] . ' ' . substr($r['mon'], 0, 4) . '</b></a>'; };
    $cols = [['label' => 'الشهر', 'type' => 'html', 'f' => $lnk, 'w' => 1.5], ['label' => 'عدد الفواتير', 'key' => 'n', 'type' => 'num'], ['label' => 'إجمالي المبيعات', 'key' => 'total', 'type' => 'money'], ['label' => 'المدفوع', 'key' => 'paid', 'type' => 'money'], ['label' => 'المتبقي', 'key' => 'due', 'type' => 'money'], ['label' => 'الخصومات', 'key' => 'disc', 'type' => 'money']];
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => rep_sales_summary($F)];
}

function rep_cashier(array $F, int $limit, int $off): array {
    $p = []; $W = rep_sales_where($F, $p, false);
    [$rows, $n] = rep_fetch("SELECT u.id uid,u.full_name,COUNT(*) n,SUM(s.total) total,SUM(s.paid) paid,SUM(s.due) due,SUM(s.discount_total) disc FROM " . SALES_FROM . " WHERE $W AND s.status='completed' GROUP BY u.id,u.full_name ORDER BY total DESC", $p, $limit, $off);
    $lnk = fn($r) => '<a href="' . e(BASE . '/reports/?r=sales&user=' . $r['uid'] . '&f=' . ($F['f'] ?? '') . '&t=' . ($F['t'] ?? '')) . '"><b>' . e($r['full_name']) . '</b></a>';
    $cols = [['label' => 'الكاشير', 'type' => 'html', 'f' => $lnk, 'w' => 1.5], ['label' => 'عدد الفواتير', 'key' => 'n', 'type' => 'num'], ['label' => 'إجمالي المبيعات', 'key' => 'total', 'type' => 'money'], ['label' => 'المدفوع', 'key' => 'paid', 'type' => 'money'], ['label' => 'المتبقي', 'key' => 'due', 'type' => 'money'], ['label' => 'الخصومات', 'key' => 'disc', 'type' => 'money']];
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => rep_sales_summary($F)];
}

function rep_methods(array $F, int $limit, int $off): array {
    $w = ['1']; $p = [];
    if ($u = scope_user()) { $w[] = 'p.user_id=?'; $p[] = $u; }
    rep_date_only('p.payment_date', $F, $w, $p);
    if (!empty($F['method'])) { $w[] = 'p.payment_method_id=?'; $p[] = (int)$F['method']; }
    $W = implode(' AND ', $w);
    [$rows, $n] = rep_fetch("SELECT pm.id mid,pm.name,SUM(p.amount>0) ops,COALESCE(SUM(IF(p.amount>0,p.amount,0)),0) recv,COALESCE(-SUM(IF(p.amount<0,p.amount,0)),0) rev,COALESCE(SUM(p.amount),0) net,COALESCE(SUM(IF(p.type='sale' AND p.amount>0,p.amount,0)),0) fromsales,COALESCE(SUM(IF(p.type='customer',p.amount,0)),0) fromcust,(SELECT COALESCE(SUM(sp.amount),0) FROM supplier_payments sp WHERE sp.payment_method_id=pm.id" . (is_date($F['f'] ?? '') ? ' AND sp.payment_date>=' . db()->quote($F['f']) : '') . (is_date($F['t'] ?? '') ? ' AND sp.payment_date<=' . db()->quote($F['t']) : '') . ") tosup FROM payments p JOIN payment_methods pm ON pm.id=p.payment_method_id WHERE $W GROUP BY pm.id,pm.name ORDER BY pm.id", $p, $limit, $off);
    $lnk = fn($r) => '<a href="' . e(BASE . '/reports/?r=payments&method=' . $r['mid'] . '&f=' . ($F['f'] ?? '') . '&t=' . ($F['t'] ?? '')) . '"><b>' . e($r['name']) . '</b></a>';
    $cols = [['label' => 'الطريقة', 'type' => 'html', 'f' => $lnk], ['label' => 'عدد العمليات', 'key' => 'ops', 'type' => 'num'], ['label' => 'من فواتير البيع', 'key' => 'fromsales', 'type' => 'money'], ['label' => 'من دفعات العملاء', 'key' => 'fromcust', 'type' => 'money'], ['label' => 'مردودات (عكس)', 'key' => 'rev', 'type' => 'money'], ['label' => 'الصافي', 'key' => 'net', 'type' => 'money'], ['label' => 'مدفوع للموردين', 'key' => 'tosup', 'type' => 'money']];
    $sum = [['إجمالي المقبوض (صافي)', cur(array_sum(array_column($rows, 'net'))), 'gold']];
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => $sum];
}

function rep_product(array $F, int $limit, int $off): array {
    $w = ["s.status='completed'"]; $p = [];
    if ($u = scope_user()) { $w[] = 's.user_id=?'; $p[] = $u; }
    rep_dates('s.created_at', $F, $w, $p);
    if (($F['q'] ?? '') !== '') { $w[] = '(i.name LIKE ? OR pr.sku LIKE ?)'; array_push($p, "%{$F['q']}%", "%{$F['q']}%"); }
    if (!empty($F['category'])) { $w[] = 'pr.category_id=?'; $p[] = (int)$F['category']; }
    $W = implode(' AND ', $w);
    [$rows, $n] = rep_fetch("SELECT i.product_id pid,i.name,pr.sku,COUNT(DISTINCT s.id) inv,SUM(i.qty) qty,SUM(i.line_total) total,SUM(i.discount) disc FROM sale_items i JOIN sales s ON s.id=i.sale_id JOIN products pr ON pr.id=i.product_id WHERE $W GROUP BY i.product_id,i.name,pr.sku ORDER BY total DESC", $p, $limit, $off);
    $lnk = fn($r) => '<a href="' . e(BASE . '/reports/?r=product_lines&pid=' . $r['pid'] . '&f=' . ($F['f'] ?? '') . '&t=' . ($F['t'] ?? '')) . '"><b>' . e($r['name']) . '</b></a>';
    $cols = [['label' => 'المنتج', 'type' => 'html', 'f' => $lnk, 'w' => 2], ['label' => 'SKU', 'key' => 'sku'], ['label' => 'عدد الفواتير', 'key' => 'inv', 'type' => 'num'], ['label' => 'الكمية المباعة', 'key' => 'qty', 'type' => 'qty'], ['label' => 'الخصومات', 'key' => 'disc', 'type' => 'money'], ['label' => 'إجمالي المبيعات', 'key' => 'total', 'type' => 'money']];
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => [['إجمالي المبيعات (كل المنتجات)', cur(q("SELECT COALESCE(SUM(i.line_total),0) FROM sale_items i JOIN sales s ON s.id=i.sale_id JOIN products pr ON pr.id=i.product_id WHERE $W", $p)->fetchColumn()), 'gold'], ['عدد الأصناف', num($n), 'ink']]];
}

function rep_product_lines(array $F, int $limit, int $off): array {
    $w = ["s.status='completed'", 'i.product_id=?']; $p = [(int)($F['pid'] ?? 0)];
    if ($u = scope_user()) { $w[] = 's.user_id=?'; $p[] = $u; }
    rep_dates('s.created_at', $F, $w, $p);
    $W = implode(' AND ', $w);
    [$rows, $n] = rep_fetch("SELECT s.id,s.invoice_no,s.created_at,c.name customer,i.name,i.qty,i.unit_price,i.discount,i.line_total FROM sale_items i JOIN sales s ON s.id=i.sale_id LEFT JOIN customers c ON c.id=s.customer_id WHERE $W ORDER BY s.id DESC", $p, $limit, $off);
    $cols = [['label' => 'الفاتورة', 'type' => 'html', 'f' => 'sale_link'], ['label' => 'التاريخ', 'key' => 'created_at', 'type' => 'datetime'], ['label' => 'العميل', 'f' => fn($r) => $r['customer'] ?: 'نقدي'], ['label' => 'الكمية', 'key' => 'qty', 'type' => 'qty'], ['label' => 'سعر الوحدة', 'key' => 'unit_price', 'type' => 'money'], ['label' => 'الخصم', 'key' => 'discount', 'type' => 'money'], ['label' => 'الإجمالي', 'key' => 'line_total', 'type' => 'money']];
    $nm = q('SELECT name FROM products WHERE id=?', [(int)($F['pid'] ?? 0)])->fetchColumn();
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => [['المنتج', (string)$nm, 'ink'], ['الكمية', qty(array_sum(array_column($rows, 'qty'))), 'gold']], 'back' => 'product'];
}

function rep_inventory_base(array $F, bool $lowOnly): array {
    $w = $lowOnly ? ["p.status='active' AND p.stock<=p.min_stock"] : ['1']; $p = [];
    if (($F['q'] ?? '') !== '') { $w[] = '(p.name LIKE ? OR p.sku LIKE ?)'; array_push($p, "%{$F['q']}%", "%{$F['q']}%"); }
    if (!empty($F['category'])) { $w[] = 'p.category_id=?'; $p[] = (int)$F['category']; }
    return [implode(' AND ', $w), $p];
}
function rep_inventory(array $F, int $limit, int $off): array {
    [$W, $p] = rep_inventory_base($F, false);
    [$rows, $n] = rep_fetch("SELECT p.id,p.name,p.sku,c.name cat,u.name unit,p.stock,p.min_stock,p.cost_price,p.base_price,ROUND(p.stock*p.cost_price,2) val,p.status FROM products p JOIN units u ON u.id=p.unit_id LEFT JOIN categories c ON c.id=p.category_id WHERE $W ORDER BY p.name", $p, $limit, $off);
    $lnk = fn($r) => can('view_inventory') ? '<a href="' . BASE . '/inventory/?id=' . $r['id'] . '"><b>' . e($r['name']) . '</b></a>' : e($r['name']);
    $cols = [['label' => 'المنتج', 'type' => 'html', 'f' => $lnk, 'w' => 2], ['label' => 'SKU', 'key' => 'sku'], ['label' => 'التصنيف', 'f' => fn($r) => $r['cat'] ?: '—'], ['label' => 'الرصيد', 'f' => fn($r) => qty($r['stock']) . ' ' . $r['unit']], ['label' => 'حد التنبيه', 'key' => 'min_stock', 'type' => 'qty'], ['label' => 'التكلفة', 'key' => 'cost_price', 'type' => 'money'], ['label' => 'قيمة المخزون', 'key' => 'val', 'type' => 'money']];
    $t = q("SELECT COALESCE(SUM(p.stock*p.cost_price),0) v,COUNT(*) n FROM products p WHERE $W", $p)->fetch();
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => [['عدد المنتجات', num($t['n']), 'ink'], ['قيمة المخزون (بالتكلفة)', cur($t['v']), 'gold']]];
}
function rep_low(array $F, int $limit, int $off): array {
    [$W, $p] = rep_inventory_base($F, true);
    [$rows, $n] = rep_fetch("SELECT p.id,p.name,p.sku,c.name cat,u.name unit,p.stock,p.min_stock,GREATEST(p.min_stock-p.stock,0) need FROM products p JOIN units u ON u.id=p.unit_id LEFT JOIN categories c ON c.id=p.category_id WHERE $W ORDER BY p.stock-p.min_stock", $p, $limit, $off);
    $lnk = fn($r) => can('view_inventory') ? '<a href="' . BASE . '/inventory/?id=' . $r['id'] . '"><b>' . e($r['name']) . '</b></a>' : e($r['name']);
    $cols = [['label' => 'المنتج', 'type' => 'html', 'f' => $lnk, 'w' => 2], ['label' => 'SKU', 'key' => 'sku'], ['label' => 'التصنيف', 'f' => fn($r) => $r['cat'] ?: '—'], ['label' => 'الرصيد', 'f' => fn($r) => qty($r['stock']) . ' ' . $r['unit']], ['label' => 'حد التنبيه', 'key' => 'min_stock', 'type' => 'qty'], ['label' => 'المطلوب للوصول للحد', 'key' => 'need', 'type' => 'qty']];
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => [['أصناف منخفضة', num($n), 'bad']]];
}

function rep_purchases(array $F, int $limit, int $off): array {
    $w = ['1']; $p = [];
    rep_dates('p.created_at', $F, $w, $p);
    if (!empty($F['supplier'])) { $w[] = 'p.supplier_id=?'; $p[] = (int)$F['supplier']; }
    if (in_array($F['pstatus'] ?? '', ['active', 'cancelled'], true)) { $w[] = 'p.status=?'; $p[] = $F['pstatus']; }
    elseif (in_array($F['pstatus'] ?? '', ['paid', 'partial', 'credit'], true)) { $w[] = "p.status='active' AND p.pay_status=?"; $p[] = $F['pstatus']; }
    if (($F['q'] ?? '') !== '') { $w[] = '(p.ref LIKE ? OR s.name LIKE ? OR p.id=?)'; array_push($p, "%{$F['q']}%", "%{$F['q']}%", (int)$F['q']); }
    $W = implode(' AND ', $w);
    [$rows, $n] = rep_fetch("SELECT p.id,p.created_at,s.name sn,p.ref,p.total,IF(p.status='cancelled',0,p.paid) paid,IF(p.status='cancelled',0,p.total-p.paid) due,p.status,p.pay_status,u.full_name FROM purchases p JOIN suppliers s ON s.id=p.supplier_id JOIN users u ON u.id=p.user_id WHERE $W ORDER BY p.id DESC", $p, $limit, $off);
    $lnk = fn($r) => '<a href="' . BASE . '/purchases/?id=' . $r['id'] . '"><b>' . e(purchase_no((int)$r['id'])) . '</b></a>';
    $cols = [['label' => 'الرقم', 'type' => 'html', 'f' => $lnk], ['label' => 'التاريخ', 'key' => 'created_at', 'type' => 'datetime', 'w' => 1.3], ['label' => 'المورد', 'key' => 'sn', 'w' => 1.4], ['label' => 'المرجع', 'f' => fn($r) => $r['ref'] ?: '—'], ['label' => 'الإجمالي', 'key' => 'total', 'type' => 'money'], ['label' => 'المدفوع', 'key' => 'paid', 'type' => 'money'], ['label' => 'المتبقي', 'key' => 'due', 'type' => 'money'], ['label' => 'الحالة', 'type' => 'html', 'f' => fn($r) => $r['status'] === 'cancelled' ? badge('ملغاة', 'danger') : match ($r['pay_status']) { 'paid' => badge('مدفوعة بالكامل', 'success'), 'partial' => badge('جزئية', 'warning'), default => badge('آجلة', 'info') }]];
    $t = q("SELECT COUNT(*) n,COALESCE(SUM(p.total),0) t,COALESCE(SUM(p.paid),0) pd FROM purchases p JOIN suppliers s ON s.id=p.supplier_id WHERE $W AND p.status='active'", $p)->fetch();
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => [['عدد الفواتير', num($t['n']), 'ink'], ['إجمالي المشتريات', cur($t['t']), 'gold'], ['المدفوع', cur($t['pd']), 'ok'], ['المتبقي للموردين', cur($t['t'] - $t['pd']), 'bad']], 'row_class' => fn($r) => $r['status'] === 'cancelled' ? 'row-cancelled' : ''];
}

function rep_expenses(array $F, int $limit, int $off): array {
    $w = ['1']; $p = [];
    rep_date_only('e.expense_date', $F, $w, $p);
    if (!empty($F['expcat'])) { $w[] = 'e.category_id=?'; $p[] = (int)$F['expcat']; }
    if (!empty($F['user'])) { $w[] = 'e.user_id=?'; $p[] = (int)$F['user']; }
    if (in_array($F['estatus'] ?? '', ['active', 'cancelled'], true)) { $w[] = 'e.status=?'; $p[] = $F['estatus']; }
    if (($F['q'] ?? '') !== '') { $w[] = '(e.description LIKE ? OR c.name LIKE ?)'; array_push($p, "%{$F['q']}%", "%{$F['q']}%"); }
    $W = implode(' AND ', $w);
    [$rows, $n] = rep_fetch("SELECT e.id,e.expense_date,c.name cat,e.amount,e.description,e.status,u.full_name FROM expenses e JOIN expense_categories c ON c.id=e.category_id JOIN users u ON u.id=e.user_id WHERE $W ORDER BY e.expense_date DESC,e.id DESC", $p, $limit, $off);
    $lnk = fn($r) => '<a href="' . BASE . '/expenses/?id=' . $r['id'] . '"><b>' . d($r['expense_date']) . '</b></a>';
    $cols = [['label' => 'التاريخ', 'type' => 'html', 'f' => $lnk], ['label' => 'التصنيف', 'key' => 'cat'], ['label' => 'الوصف', 'f' => fn($r) => $r['description'] ?: '—', 'w' => 2], ['label' => 'المسجِّل', 'key' => 'full_name'], ['label' => 'المبلغ', 'key' => 'amount', 'type' => 'money'], ['label' => 'الحالة', 'type' => 'html', 'f' => fn($r) => $r['status'] === 'cancelled' ? badge('ملغى', 'danger') : badge('نشط', 'success')]];
    $t = q("SELECT COUNT(*) n,COALESCE(SUM(e.amount),0) t FROM expenses e JOIN expense_categories c ON c.id=e.category_id WHERE $W AND e.status='active'", $p)->fetch();
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => [['عدد المصروفات (النشطة)', num($t['n']), 'ink'], ['إجمالي المصروفات', cur($t['t']), 'bad']], 'row_class' => fn($r) => $r['status'] === 'cancelled' ? 'row-cancelled' : ''];
}

function rep_customers(array $F, int $limit, int $off): array {
    $w = ['c.is_active=1']; $p = [];
    if (($F['q'] ?? '') !== '') { $w[] = '(c.name LIKE ? OR c.phone LIKE ?)'; array_push($p, "%{$F['q']}%", "%{$F['q']}%"); }
    if (!empty($F['debtors'])) $w[] = 'c.balance>0';
    $W = implode(' AND ', $w);
    [$rows, $n] = rep_fetch("SELECT c.id,c.name,c.phone,c.balance,(SELECT COUNT(*) FROM sales s WHERE s.customer_id=c.id AND s.status='completed') inv,(SELECT MAX(pp.payment_date) FROM payments pp WHERE pp.customer_id=c.id AND pp.amount>0 AND pp.type='customer') lastpay FROM customers c WHERE $W ORDER BY c.balance DESC,c.name", $p, $limit, $off);
    $lnk = fn($r) => '<a href="' . BASE . '/customers/statement.php?id=' . $r['id'] . '"><b>' . e($r['name']) . '</b></a>';
    $cols = [['label' => 'العميل', 'type' => 'html', 'f' => $lnk, 'w' => 1.6], ['label' => 'الهاتف', 'f' => fn($r) => $r['phone'] ?: '—'], ['label' => 'عدد الفواتير', 'key' => 'inv', 'type' => 'num'], ['label' => 'آخر دفعة', 'f' => fn($r) => $r['lastpay'] ? d($r['lastpay']) : '—'], ['label' => 'الرصيد المستحق', 'key' => 'balance', 'type' => 'money']];
    $t = q("SELECT COALESCE(SUM(IF(c.balance>0,c.balance,0)),0) debt,SUM(c.balance>0) n FROM customers c WHERE $W", $p)->fetch();
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => [['إجمالي ديون العملاء', cur($t['debt']), 'bad'], ['عملاء مدينون', num($t['n']), 'ink']]];
}

function rep_payments(array $F, int $limit, int $off): array {
    $w = ["p.type='customer'"]; $p = [];
    if ($u = scope_user()) { $w[] = 'p.user_id=?'; $p[] = $u; } elseif (!empty($F['user'])) { $w[] = 'p.user_id=?'; $p[] = (int)$F['user']; }
    rep_date_only('p.payment_date', $F, $w, $p);
    if (!empty($F['customer'])) { $w[] = 'p.customer_id=?'; $p[] = (int)$F['customer']; }
    if (!empty($F['method'])) { $w[] = 'p.payment_method_id=?'; $p[] = (int)$F['method']; }
    if (($F['q'] ?? '') !== '') { $w[] = '(c.name LIKE ? OR p.reference LIKE ?)'; array_push($p, "%{$F['q']}%", "%{$F['q']}%"); }
    $W = implode(' AND ', $w);
    $from = 'payments p JOIN customers c ON c.id=p.customer_id JOIN payment_methods pm ON pm.id=p.payment_method_id JOIN users u ON u.id=p.user_id';
    [$rows, $n] = rep_fetch("SELECT p.id,p.payment_date,p.created_at,c.id cid,c.name customer,pm.name method,p.amount,p.reference,p.notes,u.full_name FROM $from WHERE $W ORDER BY p.id DESC", $p, $limit, $off);
    $lnk = fn($r) => can('manage_customers') ? '<a href="' . BASE . '/customers/statement.php?id=' . $r['cid'] . '"><b>' . e($r['customer']) . '</b></a>' : e($r['customer']);
    $cols = [['label' => 'التاريخ', 'key' => 'payment_date', 'type' => 'date'], ['label' => 'العميل', 'type' => 'html', 'f' => $lnk, 'w' => 1.5], ['label' => 'طريقة الدفع', 'key' => 'method'], ['label' => 'المرجع', 'f' => fn($r) => $r['reference'] ?: '—'], ['label' => 'استلمها', 'key' => 'full_name'], ['label' => 'المبلغ', 'key' => 'amount', 'type' => 'money']];
    $t = q("SELECT COUNT(*) n,COALESCE(SUM(p.amount),0) t FROM $from WHERE $W", $p)->fetch();
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => [['عدد الدفعات', num($t['n']), 'ink'], ['إجمالي الدفعات', cur($t['t']), 'ok']]];
}

/** نطاق التاريخ الافتراضي لكل تقرير */
function report_default_range(string $kind): array {
    return match ($kind) { 'year' => [date('Y-01-01'), today()], 'month' => [date('Y-m-01'), today()], default => ['', ''] };
}

function rep_suppliers(array $F, int $limit, int $off): array {
    $w = ['s.is_active=1']; $p = [];
    if (($F['q'] ?? '') !== '') { $w[] = '(s.name LIKE ? OR s.phone LIKE ?)'; array_push($p, "%{$F['q']}%", "%{$F['q']}%"); }
    if (!empty($F['debtors'])) $w[] = 's.balance>0';
    $W = implode(' AND ', $w);
    [$rows, $n] = rep_fetch("SELECT s.id,s.name,s.phone,s.balance,(SELECT COUNT(*) FROM purchases p WHERE p.supplier_id=s.id AND p.status='active') inv,(SELECT COALESCE(SUM(p.total),0) FROM purchases p WHERE p.supplier_id=s.id AND p.status='active') tot,(SELECT MAX(sp.payment_date) FROM supplier_payments sp WHERE sp.supplier_id=s.id AND sp.amount>0) lastpay FROM suppliers s WHERE $W ORDER BY s.balance DESC,s.name", $p, $limit, $off);
    $lnk = fn($r) => '<a href="' . BASE . '/suppliers/statement.php?id=' . $r['id'] . '"><b>' . e($r['name']) . '</b></a>';
    $cols = [['label' => 'المورد', 'type' => 'html', 'f' => $lnk, 'w' => 1.6], ['label' => 'الهاتف', 'f' => fn($r) => $r['phone'] ?: '—'], ['label' => 'عدد الفواتير', 'key' => 'inv', 'type' => 'num'], ['label' => 'إجمالي المشتريات', 'key' => 'tot', 'type' => 'money'], ['label' => 'آخر دفعة', 'f' => fn($r) => $r['lastpay'] ? d($r['lastpay']) : '—'], ['label' => 'المستحق للمورد', 'key' => 'balance', 'type' => 'money']];
    $t = q("SELECT COALESCE(SUM(IF(s.balance>0,s.balance,0)),0) debt,SUM(s.balance>0) n FROM suppliers s WHERE $W", $p)->fetch();
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => [['إجمالي المستحق للموردين', cur($t['debt']), 'bad'], ['موردون لهم رصيد', num($t['n']), 'ink']]];
}

function rep_supplier_payments(array $F, int $limit, int $off): array {
    $w = ['1']; $p = [];
    rep_date_only('sp.payment_date', $F, $w, $p);
    if (!empty($F['supplier'])) { $w[] = 'sp.supplier_id=?'; $p[] = (int)$F['supplier']; }
    if (!empty($F['method'])) { $w[] = 'sp.payment_method_id=?'; $p[] = (int)$F['method']; }
    if (($F['q'] ?? '') !== '') { $w[] = '(s.name LIKE ? OR sp.reference LIKE ?)'; array_push($p, "%{$F['q']}%", "%{$F['q']}%"); }
    $W = implode(' AND ', $w);
    $from = 'supplier_payments sp JOIN suppliers s ON s.id=sp.supplier_id JOIN payment_methods pm ON pm.id=sp.payment_method_id JOIN users u ON u.id=sp.user_id';
    [$rows, $n] = rep_fetch("SELECT sp.id,sp.purchase_id,sp.type,sp.payment_date,s.id sid,s.name supplier,pm.name method,sp.amount,sp.reference,u.full_name FROM $from WHERE $W ORDER BY sp.id DESC", $p, $limit, $off);
    $cols = [['label' => 'التاريخ', 'key' => 'payment_date', 'type' => 'date'], ['label' => 'المورد', 'type' => 'html', 'f' => fn($r) => '<a href="' . BASE . '/suppliers/statement.php?id=' . $r['sid'] . '"><b>' . e($r['supplier']) . '</b></a>', 'w' => 1.5],
        ['label' => 'الفاتورة', 'type' => 'html', 'f' => fn($r) => '<a href="' . BASE . '/purchases/?id=' . $r['purchase_id'] . '">' . e(purchase_no((int)$r['purchase_id'])) . '</a>'], ['label' => 'النوع', 'f' => fn($r) => ['initial' => 'دفعة عند الشراء', 'payment' => 'دفعة لاحقة', 'reversal' => 'استرداد (عكس)'][$r['type']], 'w' => 1.2],
        ['label' => 'طريقة الدفع', 'key' => 'method'], ['label' => 'المرجع', 'f' => fn($r) => $r['reference'] ?: '—'], ['label' => 'سجّلها', 'key' => 'full_name'], ['label' => 'المبلغ', 'key' => 'amount', 'type' => 'money']];
    $t = q("SELECT COUNT(*) n,COALESCE(SUM(sp.amount),0) t FROM $from WHERE $W", $p)->fetch();
    return ['cols' => $cols, 'rows' => $rows, 'total' => $n, 'summary' => [['عدد الحركات', num($t['n']), 'ink'], ['صافي المدفوع للموردين', cur($t['t']), 'ok']]];
}
