<?php
/** JOUD — تحميل بيانات فاتورة مع فحص الصلاحية والملكية (منع IDOR): الكاشير يرى فواتيره فقط ما لم يملك view_all_sales */
function invoice_load(int $id): array {
    need('view_sales');
    $s = q('SELECT s.*,u.full_name,c.name cn,c.phone cp,c.balance cbal FROM sales s JOIN users u ON u.id=s.user_id LEFT JOIN customers c ON c.id=s.customer_id WHERE s.id=?', [$id])->fetch();
    if (!$s) fail_page(404, 'الفاتورة غير موجودة', 'تحقق من رقم الفاتورة.');
    if (!can('view_all_sales') && (int)$s['user_id'] !== (int)me()['id']) fail_page(403, 'غير مصرح', 'لا تملك صلاحية عرض فواتير مستخدمين آخرين.');
    $items = q('SELECT * FROM sale_items WHERE sale_id=? ORDER BY id', [$id])->fetchAll();
    $pays = q('SELECT p.id,pa.amount,p.payment_date,p.created_at,p.type,p.notes,pm.name FROM payment_allocations pa JOIN payments p ON p.id=pa.payment_id JOIN payment_methods pm ON pm.id=p.payment_method_id WHERE pa.sale_id=? ORDER BY p.id', [$id])->fetchAll();
    return [$s, $items, $pays];
}
