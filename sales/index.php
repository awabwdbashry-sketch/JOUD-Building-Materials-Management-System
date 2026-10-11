<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/report_page.php';
need('view_sales');
$_GET['r'] = 'sales';
report_page('sales', ['perm' => 'view_sales', 'title' => 'الفواتير', 'nav' => false, 'keep_r' => false, 'empty' => 'لا توجد فواتير ضمن هذه التصفية.']);
