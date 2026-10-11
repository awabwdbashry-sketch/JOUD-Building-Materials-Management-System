<?php
/**
 * JOUD — تحويل سجل العمليات إلى نص عربي مقروء (لا يُعرض JSON الخام للمستخدم؛ يبقى مخزنًا داخليًا للمطورين).
 * audit_describe($row) => ['title','icon','tone','lines'=>[[label,value],...],'summary','link']
 */
const AUDIT_MONEY_KEYS = ['total', 'paid', 'due', 'amount', 'old_balance', 'new_balance', 'customer_balance', 'refunded', 'old', 'new', 'base_price', 'min_price', 'old_base', 'new_base', 'old_min', 'new_min', 'base', 'final', 'min', 'before_money', 'remaining_before', 'remaining_after', 'supplier_balance'];
const AUDIT_LABELS = [
    'invoice' => 'رقم الفاتورة', 'customer' => 'العميل', 'total' => 'الإجمالي', 'paid' => 'المدفوع', 'due' => 'المتبقي', 'method' => 'طريقة الدفع', 'items' => 'عدد البنود',
    'cashier' => 'الكاشير', 'by' => 'المستخدم', 'reason' => 'السبب', 'refunded' => 'مبلغ معكوس للعميل', 'customer_balance' => 'رصيد العميل بعد العملية', 'amount' => 'المبلغ',
    'old_balance' => 'الرصيد السابق', 'new_balance' => 'الرصيد الجديد', 'reference' => 'المرجع', 'received_by' => 'استلمها', 'supplier' => 'المورد', 'ref' => 'مرجع المورد',
    'name' => 'الاسم', 'sku' => 'SKU', 'base_price' => 'سعر البيع', 'min_price' => 'الحد الأدنى للسعر', 'opening_stock' => 'رصيد افتتاحي', 'product' => 'المنتج', 'qty' => 'الكمية',
    'before' => 'الرصيد قبل', 'after' => 'الرصيد بعد', 'note' => 'السبب', 'category' => 'التصنيف', 'date' => 'التاريخ', 'description' => 'الوصف', 'user' => 'المستخدم', 'username' => 'اسم الدخول',
    'role' => 'الدور', 'active' => 'الحالة', 'format' => 'الصيغة', 'report' => 'التقرير', 'rows' => 'عدد الصفوف', 'old_base' => 'سعر البيع السابق', 'new_base' => 'سعر البيع الجديد',
    'old_min' => 'الأدنى السابق', 'new_min' => 'الأدنى الجديد', 'old_val' => 'القيمة السابقة', 'cancelled_by' => 'ألغاها',
    'purchase_no' => 'رقم فاتورة الشراء', 'remaining_before' => 'المتبقي قبل الدفعة', 'remaining_after' => 'المتبقي بعد الدفعة', 'supplier_balance' => 'رصيد المورد بعد العملية',
];
const AUDIT_ACTIONS = [
    'login' => ['تسجيل دخول', 'box-arrow-in-right', 'info', ['user']], 'logout' => ['تسجيل خروج', 'box-arrow-left', 'ink', ['user']],
    'login_failed' => ['محاولة دخول فاشلة', 'shield-exclamation', 'bad', ['username']],
    'sale_created' => ['إنشاء فاتورة بيع', 'receipt', 'ok', ['invoice', 'customer', 'total', 'paid', 'due', 'method', 'items', 'cashier']],
    'sale_cancelled' => ['إلغاء فاتورة بيع', 'x-octagon', 'bad', ['invoice', 'customer', 'total', 'refunded', 'customer_balance', 'reason', 'cancelled_by']],
    'payment_created' => ['تسجيل دفعة على فاتورة', 'cash', 'ok', ['amount']],
    'customer_payment' => ['دفعة عميل', 'cash-coin', 'ok', ['customer', 'amount', 'method', 'old_balance', 'new_balance', 'reference', 'received_by']],
    'price_override' => ['بيع بسعر أقل من الحد الأدنى', 'exclamation-triangle', 'warn', ['invoice', 'by', 'reason', '@price_items']],
    'price_changed' => ['تغيير سعر منتج', 'tag', 'warn', ['name', 'old_base', 'new_base', 'old_min', 'new_min']],
    'purchase_created' => ['تسجيل فاتورة شراء', 'truck', 'ok', ['purchase_no', 'supplier', 'ref', 'total', 'paid', 'due', 'method', 'items', 'supplier_balance', 'by']],
    'purchase_cancelled' => ['إلغاء فاتورة شراء', 'x-octagon', 'bad', ['purchase_no', 'supplier', 'total', 'refunded', 'supplier_balance', 'reason', 'by']],
    'supplier_payment' => ['دفعة لمورد', 'cash-coin', 'ok', ['purchase_no', 'supplier', 'amount', 'method', 'remaining_before', 'remaining_after', 'supplier_balance', 'reference', 'by']],
    'purchase_pdf' => ['تنزيل فاتورة شراء PDF', 'file-earmark-pdf', 'ink', ['purchase_no', 'format']],
    'product_created' => ['إضافة منتج', 'box-seam', 'ok', ['name', 'sku', 'base_price', 'min_price', 'opening_stock']],
    'product_updated' => ['تعديل منتج', 'pencil-square', 'info', ['name', '@changes']], 'product_deleted' => ['حذف منتج', 'trash3', 'bad', ['name', 'sku']],
    'product_deactivated' => ['تعطيل منتج', 'toggle-off', 'warn', ['name', 'reason']], 'product_activated' => ['تفعيل منتج', 'toggle-on', 'ok', ['name']],
    'expense_created' => ['إضافة مصروف', 'wallet2', 'warn', ['category', 'amount', 'date', 'description', 'by']],
    'expense_updated' => ['تعديل مصروف', 'pencil-square', 'info', ['category', 'amount', '@changes', 'by']],
    'expense_cancelled' => ['إلغاء مصروف', 'x-octagon', 'bad', ['category', 'amount', 'date', 'reason', 'by']],
    'inventory_adjustment' => ['تسوية مخزون', 'sliders', 'info', ['product', 'qty', 'before', 'after', 'note', 'by']],
    'user_created' => ['إضافة مستخدم', 'person-plus', 'ok', ['user', 'username', 'role']], 'user_updated' => ['تعديل مستخدم', 'person-gear', 'info', ['user', 'username', 'role', 'active']],
    'password_reset' => ['إعادة تعيين كلمة مرور', 'key', 'warn', ['user']],
    'settings_changed' => ['تعديل الإعدادات', 'gear', 'info', ['@changes']],
    'permission_changed' => ['تعديل صلاحيات دور', 'shield-lock', 'warn', ['role', '@added', '@removed']], 'role_updated' => ['تعديل دور', 'shield-lock', 'info', []],
    'balance_recalculated' => ['تصحيح رصيد عميل', 'arrow-repeat', 'warn', ['customer', 'old', 'new']],
    'report_exported' => ['تصدير تقرير', 'file-earmark-arrow-down', 'ink', ['report', 'format', 'rows']], 'statement_exported' => ['تصدير كشف حساب', 'file-earmark-arrow-down', 'ink', ['customer', 'format']],
    'invoice_pdf' => ['تنزيل فاتورة PDF', 'file-earmark-pdf', 'ink', ['invoice', 'format']],
    'schema_upgraded' => ['ترقية قاعدة البيانات', 'database-up', 'info', []], 'roles_migrated' => ['دمج الأدوار القديمة', 'diagram-2', 'warn', ['role', 'users', 'moved_to']],
];
const AUDIT_ENTITY_NOUN = ['categories' => 'تصنيف', 'units' => 'وحدة قياس', 'customers' => 'عميل', 'suppliers' => 'مورد', 'payment_methods' => 'طريقة دفع', 'expense_categories' => 'تصنيف مصروف', 'expenses' => 'مصروف', 'products' => 'منتج'];

function audit_title_for(string $action): string {
    if (isset(AUDIT_ACTIONS[$action])) return AUDIT_ACTIONS[$action][0];
    if (preg_match('/^(\w+?)_(created|updated|deleted|activated|deactivated)$/', $action, $m) && isset(AUDIT_ENTITY_NOUN[$m[1]])) {
        return ['created' => 'إضافة', 'updated' => 'تعديل', 'deleted' => 'حذف', 'activated' => 'تفعيل', 'deactivated' => 'تعطيل'][$m[2]] . ' ' . AUDIT_ENTITY_NOUN[$m[1]];
    }
    return 'عملية في النظام';
}
function audit_value(string $k, $v): string {
    if (is_bool($v)) return $v ? 'نعم' : 'لا';
    if (is_array($v)) return implode('، ', array_map(fn($x) => is_array($x) ? '' : (string)$x, $v));
    if (in_array($k, AUDIT_MONEY_KEYS, true) && is_numeric($v)) return cur($v);
    if (in_array($k, ['qty', 'before', 'after', 'opening_stock'], true) && is_numeric($v)) return qty($v);
    if ($k === 'date' && is_date((string)$v)) return d($v);
    if ($k === 'format') return strtoupper((string)$v);
    return (string)$v;
}
function audit_changes_lines(array $ch): array {
    $o = [];
    foreach ($ch as $label => $pair) {
        if (!is_array($pair)) continue;
        $num = fn($x) => is_numeric($x) && in_array($label, ['المبلغ', 'التكلفة', 'سعر البيع', 'الحد الأدنى للسعر']) ? cur($x) : (string)$x;
        $o[] = [$label, ($pair[0] === '' || $pair[0] === null ? '—' : $num($pair[0])) . '  ←  ' . ($pair[1] === '' || $pair[1] === null ? '—' : $num($pair[1]))];
    }
    return $o;
}

function audit_describe(array $r): array {
    $act = $r['action']; $D = json_decode((string)($r['details'] ?? ''), true); if (!is_array($D)) $D = ($r['details'] !== null && $r['details'] !== '' && !str_starts_with(trim((string)$r['details']), '{') ? ['note' => (string)$r['details']] : []);
    $meta = AUDIT_ACTIONS[$act] ?? null; $title = audit_title_for($act);
    $icon = $meta[1] ?? (str_ends_with($act, '_deleted') ? 'trash3' : (str_ends_with($act, '_created') ? 'plus-circle' : (str_ends_with($act, '_updated') ? 'pencil-square' : 'activity')));
    $tone = $meta[2] ?? (str_ends_with($act, '_deleted') || str_ends_with($act, '_deactivated') ? 'bad' : (str_ends_with($act, '_created') || str_ends_with($act, '_activated') ? 'ok' : 'info'));
    $fields = $meta[3] ?? ['name', '@changes'];
    $link = '';
    // سجلات قديمة: نكمل اسم/رقم الفاتورة من الجداول عند غيابه
    if ($r['entity'] === 'sale' && empty($D['invoice']) && $r['entity_id']) { $inv = q('SELECT invoice_no FROM sales WHERE id=?', [$r['entity_id']])->fetchColumn(); if ($inv) $D['invoice'] = $inv; }
    if ($r['entity'] === 'customer' && empty($D['customer']) && $r['entity_id']) { $n = q('SELECT name FROM customers WHERE id=?', [$r['entity_id']])->fetchColumn(); if ($n) $D['customer'] = $n; }
    if ($r['entity'] === 'product' && empty($D['name']) && empty($D['product']) && $r['entity_id']) { $n = q('SELECT name FROM products WHERE id=?', [$r['entity_id']])->fetchColumn(); if ($n) $D['name'] = $n; }
    $lines = [];
    foreach ($fields as $f) {
        if ($f === '@changes') { $lines = array_merge($lines, audit_changes_lines((array)($D['changes'] ?? []))); continue; }
        if ($f === '@added' || $f === '@removed') { $x = $D[substr($f, 1) === 'added' ? 'added' : 'removed'] ?? []; if ($x) $lines[] = [$f === '@added' ? 'صلاحيات أُضيفت' : 'صلاحيات أُزيلت', implode('، ', $x)]; continue; }
        if ($f === '@price_items') { foreach ((array)($D['items'] ?? []) as $it) if (is_array($it)) $lines[] = ['المنتج: ' . ($it['product'] ?? ''), 'السعر الأساسي ' . cur($it['base'] ?? 0) . ' ← بيع بـ ' . cur($it['final'] ?? 0) . ' (الأدنى المسموح ' . cur($it['min'] ?? 0) . ')']; continue; }
        if (!isset($D[$f]) || $D[$f] === '' || $D[$f] === null) continue;
        $lines[] = [AUDIT_LABELS[$f] ?? $f, audit_value($f, $D[$f])];
    }
    if (!$meta) {                                   // عمليات CRUD العامة: الاسم + التغييرات
        if (!empty($D['name'])) array_unshift($lines, ['الاسم', (string)$D['name']]);
        if (!empty($D['note'])) $lines[] = ['ملاحظة', (string)$D['note']];
    }
    $id = $r['entity_id'];
    if ($id) $link = match ($r['entity']) { 'sale' => BASE . '/sales/invoice.php?id=' . $id, 'customer' => BASE . '/customers/statement.php?id=' . $id, 'purchase' => BASE . '/purchases/?id=' . $id, 'supplier' => BASE . '/suppliers/view.php?id=' . $id, 'expense' => BASE . '/expenses/?id=' . $id, 'product' => BASE . '/inventory/?id=' . $id, default => '' };
    if ($r['entity'] === 'sale' && !can('view_sales')) $link = '';
    $summary = implode(' — ', array_map(fn($l) => $l[0] . ': ' . $l[1], array_slice($lines, 0, 3)));
    return ['title' => $title, 'icon' => $icon, 'tone' => $tone, 'lines' => $lines, 'summary' => $summary, 'link' => $link];
}
