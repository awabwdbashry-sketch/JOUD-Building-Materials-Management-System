<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/accounting.php';
need('cancel_sale');
require_post();
$id = (int)($_POST['id'] ?? 0);
try {
    $r = cancel_sale(db(), ctx(), $id, $_POST['reason'] ?? '');
    $msg = 'تم إلغاء الفاتورة وإرجاع المخزون.';
    if ($r['refunded'] > 0) $msg .= ' عُكست دفعات بقيمة ' . cur($r['refunded']) . '.';
    if ($r['customer_balance'] !== null) $msg .= ' رصيد العميل الآن: ' . cur($r['customer_balance']) . '.';
    flash('success', $msg);
} catch (DomainException $x) { flash('danger', $x->getMessage()); }
redirect(BASE . '/sales/invoice.php?id=' . $id);
