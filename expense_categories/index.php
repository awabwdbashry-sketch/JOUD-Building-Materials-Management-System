<?php require __DIR__.'/../includes/bootstrap.php'; require __DIR__.'/../includes/crud.php';
crud([
    'table' => 'expense_categories', 'title' => 'تصنيفات المصروفات', 'noun' => 'تصنيف مصروف', 'view' => 'manage_expenses', 'edit' => 'manage_expenses', 'order' => 'name',
    'fields' => ['name' => ['اسم التصنيف', 'text', null, 1, 100], 'is_active' => ['الحالة', 'checkbox']],
    'cols' => ['name', 'is_active'], 'search' => ['name'],
    'deps' => [['SELECT COUNT(*) FROM expenses WHERE category_id=?', 'مصروف']],
]);
