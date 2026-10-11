<?php require __DIR__.'/../includes/bootstrap.php'; require __DIR__.'/../includes/crud.php';
crud([
    'table' => 'customers', 'title' => 'العملاء', 'noun' => 'عميل', 'view' => 'manage_customers', 'edit' => 'manage_customers', 'order' => 'name',
    'fields' => ['name' => ['اسم العميل', 'text', null, 1, 120], 'phone' => ['الهاتف', 'text', null, 0, 30], 'address' => ['العنوان', 'text'], 'balance' => ['الرصيد المستحق', 'money'], 'notes' => ['ملاحظات', 'textarea'], 'is_active' => ['الحالة', 'checkbox']],
    'cols' => ['name', 'phone', 'balance', 'is_active'], 'search' => ['name', 'phone'],
    'deps' => [['SELECT COUNT(*) FROM sales WHERE customer_id=?', 'فاتورة'], ['SELECT COUNT(*) FROM payments WHERE customer_id=?', 'دفعة']],
    'validate' => fn($v) => !empty($v['phone']) && !preg_match('/^[0-9+\-\s()]{5,30}$/', $v['phone']) ? 'رقم الهاتف غير صالح' : null,
    'guard' => fn($act, $row) => $act === 'toggle' && $row['is_active'] && abs((float)$row['balance']) > 0.004 ? 'لا يمكن تعطيل عميل عليه رصيد (' . cur($row['balance']) . ') — سوِّ حسابه أولًا' : null,
    'actions' => fn($r) => '<a class="btn-act btn" title="كشف حساب" href="statement.php?id=' . $r['id'] . '"><i class="bi bi-journal-text"></i></a>' . (can('receive_customer_payment') && $r['balance'] > 0 ? '<a class="btn-act btn ok" title="تسجيل دفعة" href="payment.php?customer=' . $r['id'] . '"><i class="bi bi-cash-coin"></i></a>' : ''),
]);
