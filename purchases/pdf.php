<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/accounting.php';
require __DIR__ . '/../includes/pdf.php';
need('view_purchases');
$id = (int)($_GET['id'] ?? 0);
$p = q('SELECT p.*,s.name sn,s.phone sp,u.full_name FROM purchases p JOIN suppliers s ON s.id=p.supplier_id JOIN users u ON u.id=p.user_id WHERE p.id=?', [$id])->fetch();
if (!$p) fail_page(404, 'فاتورة الشراء غير موجودة', 'تحقق من الرابط.');
$items = q('SELECT i.*,pr.name,pr.sku FROM purchase_items i JOIN products pr ON pr.id=i.product_id WHERE purchase_id=? ORDER BY i.id', [$id])->fetchAll();
$pays = q('SELECT sp.*,pm.name FROM supplier_payments sp JOIN payment_methods pm ON pm.id=sp.payment_method_id WHERE sp.purchase_id=? ORDER BY sp.id', [$id])->fetchAll();
audit('purchase_pdf', 'purchase', $id, ['purchase_no' => purchase_no($id), 'format' => 'PDF']);
pdf_send(pdf_purchase($p, $items, $pays), purchase_no($id) . '.pdf', isset($_GET['view']));
