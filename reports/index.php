<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/report_page.php';
need(['view_reports', 'view_sales_reports']);
$k = $_GET['r'] ?? 'daily';
$defs = report_defs();
if (isset($defs[$k]) && !report_allowed($defs[$k])) fail_page(403, 'غير مصرح', 'لا تملك صلاحية عرض هذا التقرير.');   // طلب مباشر لتقرير غير مسموح
if (!isset($defs[$k])) $k = 'daily';
report_page($k);
