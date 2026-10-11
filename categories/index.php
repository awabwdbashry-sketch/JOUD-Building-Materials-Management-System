<?php require __DIR__.'/../includes/bootstrap.php'; require __DIR__.'/../includes/crud.php';
crud([
    'table' => 'categories', 'title' => 'التصنيفات', 'noun' => 'تصنيف', 'view' => 'manage_catalog', 'edit' => 'manage_catalog', 'order' => 'name',
    'fields' => ['name' => ['اسم التصنيف', 'text', null, 1, 100], 'is_active' => ['الحالة', 'checkbox']],
    'cols' => ['name', 'is_active'], 'search' => ['name'],
    'deps' => [['SELECT COUNT(*) FROM products WHERE category_id=?', 'منتج']],
]);
