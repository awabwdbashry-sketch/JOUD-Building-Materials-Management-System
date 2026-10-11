<?php require __DIR__.'/../includes/bootstrap.php'; require __DIR__.'/../includes/crud.php';
crud([
    'table' => 'suppliers', 'title' => 'الموردون', 'noun' => 'مورد', 'view' => 'manage_suppliers', 'edit' => 'manage_suppliers', 'order' => 'name',
    'fields' => ['name' => ['اسم المورد', 'text', null, 1, 120], 'phone' => ['الهاتف', 'text', null, 0, 30], 'address' => ['العنوان', 'text'], 'balance' => ['المستحق للمورد', 'money'], 'notes' => ['ملاحظات', 'textarea'], 'is_active' => ['الحالة', 'checkbox']],
    'cols' => ['name', 'phone', 'balance', 'is_active'], 'search' => ['name', 'phone', 'address'],
    'deps' => [['SELECT COUNT(*) FROM purchases WHERE supplier_id=?', 'فاتورة شراء'], ['SELECT COUNT(*) FROM supplier_payments WHERE supplier_id=?', 'دفعة']],
    'validate' => fn($v) => !empty($v['phone']) && !preg_match('/^[0-9+\-\s()]{5,30}$/', $v['phone']) ? 'رقم الهاتف غير صالح' : null,
    'guard' => fn($act, $row) => $act === 'toggle' && $row['is_active'] && abs((float)$row['balance']) > 0.004 ? 'لا يمكن تعطيل مورد له رصيد (' . cur($row['balance']) . ') — سدّد حسابه أولًا' : null,
    'actions' => fn($r) => '<a class="btn-act btn" title="تفاصيل المورد" href="view.php?id=' . $r['id'] . '"><i class="bi bi-eye"></i></a><a class="btn-act btn" title="كشف حساب" href="statement.php?id=' . $r['id'] . '"><i class="bi bi-journal-text"></i></a>' . (can('create_purchase') && $r['is_active'] ? '<a class="btn-act btn ok" title="شراء جديد" href="' . BASE . '/purchases/?new=1&supplier=' . $r['id'] . '"><i class="bi bi-cart-plus"></i></a>' : ''),
]);
