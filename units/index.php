<?php require __DIR__.'/../includes/bootstrap.php'; require __DIR__.'/../includes/crud.php';
crud([
    'table' => 'units', 'title' => 'وحدات القياس', 'noun' => 'وحدة', 'view' => 'manage_catalog', 'edit' => 'manage_catalog', 'order' => 'name',
    'fields' => ['name' => ['اسم الوحدة', 'text', null, 1, 50], 'is_active' => ['الحالة', 'checkbox']],
    'cols' => ['name', 'is_active'], 'search' => ['name'],
    'deps' => [['SELECT COUNT(*) FROM products WHERE unit_id=?', 'منتج']],
]);
