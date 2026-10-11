<?php require __DIR__.'/../includes/bootstrap.php'; require __DIR__.'/../includes/crud.php';
crud([
    'table' => 'payment_methods', 'title' => 'طرق الدفع', 'noun' => 'طريقة دفع', 'view' => 'manage_payment_methods', 'edit' => 'manage_payment_methods', 'order' => 'id',
    'fields' => ['name' => ['الاسم', 'text', null, 1, 60], 'code' => ['الرمز (حروف إنجليزية)', 'text', null, 1, 30], 'is_active' => ['الحالة', 'checkbox']],
    'cols' => ['name', 'code', 'is_active'], 'search' => ['name', 'code'],
    'deps' => [['SELECT COUNT(*) FROM payments WHERE payment_method_id=?', 'دفعة']],
    'validate' => fn($v) => !preg_match('/^[a-z0-9_]{2,30}$/', (string)$v['code']) ? 'الرمز: حروف إنجليزية صغيرة وأرقام و _ فقط (2–30)' : null,
    // لا نسمح بتعطيل آخر طريقة دفع فعّالة حتى لا يتوقف البيع
    'guard' => function ($act, $row, $v = null) {
        $willBeOff = $act === 'delete' || $act === 'toggle' && $row['is_active'] || $act === 'save' && isset($v['is_active']) && !$v['is_active'];
        if ($row['is_active'] && $willBeOff && (int)q('SELECT COUNT(*) FROM payment_methods WHERE is_active=1 AND id<>?', [$row['id']])->fetchColumn() === 0) return 'يجب أن تبقى طريقة دفع واحدة فعّالة على الأقل';
        return null;
    },
]);
