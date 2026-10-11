<?php
/**
 * JOUD — خدمات المحاسبة والمخزون (كل عملية مالية/مخزنية داخل Transaction واحدة).
 * تُستدعى من الصفحات ومن اختبارات CLI. تتوقع $ctx = ['user_id'=>int,'perms'=>string[]].
 * الأخطاء المتوقعة تُرمى كـ DomainException برسالة عربية جاهزة للعرض.
 */

function ctx_can(array $ctx, string $p): bool { return in_array($p, $ctx['perms'] ?? [], true); }

/* =============== أرقام الفواتير (متسلسلة بلا فجوات) =============== */
function next_invoice_no(PDO $d): string {
    $n = (int)$d->query("SELECT v FROM settings WHERE k='invoice_seq' FOR UPDATE")->fetchColumn() + 1;
    $d->prepare("UPDATE settings SET v=? WHERE k='invoice_seq'")->execute([$n]);
    $pre = (string)$d->query("SELECT v FROM settings WHERE k='invoice_prefix'")->fetchColumn();
    if (!preg_match('/^[A-Za-z0-9]{1,10}$/', $pre)) $pre = 'INV';
    return $pre . '-' . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
}

/* =============== حساب الفواتير والأرصدة (المصدر: التوزيعات + الدفتر) =============== */
/** يعيد احتساب المدفوع/المتبقي/حالة الدفع للفاتورة من جدول التوزيعات */
function recalc_sale(PDO $d, int $sid): void {
    $s = $d->prepare('SELECT total,status FROM sales WHERE id=?'); $s->execute([$sid]); $sale = $s->fetch();
    if (!$sale) return;
    $p = $d->prepare('SELECT COALESCE(SUM(amount),0) FROM payment_allocations WHERE sale_id=?'); $p->execute([$sid]);
    $paid = r2($p->fetchColumn());
    if ($sale['status'] === 'cancelled') { $d->prepare('UPDATE sales SET paid=?,due=0 WHERE id=?')->execute([$paid, $sid]); return; }
    $due = max(0, r2($sale['total'] - $paid));
    $st = $due <= 0.004 ? 'paid' : ($paid > 0.004 ? 'partial' : 'credit');
    $d->prepare('UPDATE sales SET paid=?,due=?,pay_status=? WHERE id=?')->execute([$paid, $due, $st, $sid]);
}
/** رصيد العميل من الدفتر = إجمالي الفواتير غير الملغاة − كل الدفعات (الموجبة والمعكوسة) */
function customer_balance_calc(PDO $d, int $cid): float {
    $a = $d->prepare("SELECT COALESCE(SUM(total),0) FROM sales WHERE customer_id=? AND status='completed'"); $a->execute([$cid]);
    $b = $d->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE customer_id=?'); $b->execute([$cid]);
    return r2($a->fetchColumn() - $b->fetchColumn());
}
function recalc_customer_balance(PDO $d, int $cid): float {
    $bal = customer_balance_calc($d, $cid);
    $d->prepare('UPDATE customers SET balance=? WHERE id=?')->execute([$bal, $cid]);
    return $bal;
}

/* =============== المخزون =============== */
/** حركة مخزون موقّعة (+ إدخال / − إخراج) مع منع الرصيد السالب وتسجيل قبل/بعد */
function move_stock(PDO $d, int $pid, string $type, float $q, ?int $ref, int $uid, string $note): array {
    $s = $d->prepare('SELECT name,stock FROM products WHERE id=? FOR UPDATE'); $s->execute([$pid]); $p = $s->fetch();
    if (!$p) dom('منتج غير موجود');
    $before = (float)$p['stock']; $after = round($before + $q, 3);
    if ($after < -0.0004) dom('المخزون غير كافٍ للمنتج: ' . $p['name'] . ' (المتاح ' . qty($before) . ')');
    $d->prepare('UPDATE products SET stock=? WHERE id=?')->execute([$after, $pid]);
    $d->prepare('INSERT INTO inventory_movements(product_id,type,qty,ref_id,user_id,note,qty_before,qty_after) VALUES(?,?,?,?,?,?,?,?)')->execute([$pid, $type, $q, $ref, $uid, $note, $before, $after]);
    return [$before, $after];
}

/* =============== إنشاء فاتورة بيع =============== */
function create_sale(PDO $d, array $ctx, array $in): array {
    $uid = (int)$ctx['user_id'];
    $items = $in['items'] ?? []; $tok = (string)($in['token'] ?? '');
    $mid = (int)($in['method_id'] ?? 0); $cid = (int)($in['customer_id'] ?? 0) ?: null;
    $why = mb_substr(trim((string)($in['reason'] ?? '')), 0, 250);
    if (!is_array($items) || !$items) dom('الفاتورة فارغة: أضف منتجًا واحدًا على الأقل');
    if (!preg_match('/^[0-9a-f-]{36}$/', $tok)) dom('رمز الفاتورة غير صالح');
    $dup = $d->prepare('SELECT id,invoice_no FROM sales WHERE client_token=?'); $dup->execute([$tok]);
    if ($r = $dup->fetch()) return ['id' => (int)$r['id'], 'invoice' => $r['invoice_no'], 'dup' => true];

    return in_tx($d, function () use ($d, $ctx, $in, $uid, $items, $tok, $mid, $cid, $why) {
        $cust = null;
        if ($cid) {
            $c = $d->prepare('SELECT id,name FROM customers WHERE id=? AND is_active=1 FOR UPDATE'); $c->execute([$cid]);
            if (!($cust = $c->fetch())) dom('العميل غير موجود أو معطّل');
        }
        $lines = []; $seen = []; $ovr = []; $sub = 0; $disc = 0;
        foreach ($items as $it) {
            $pid = (int)($it['id'] ?? 0); $q = round((float)($it['qty'] ?? 0), 3);
            $price = isset($it['price']) && is_numeric($it['price']) ? r2($it['price']) : -1;
            if ($pid <= 0 || $q <= 0 || $price < 0) dom('كمية أو سعر غير صالح');
            if (isset($seen[$pid])) dom('منتج مكرر في الفاتورة');
            $seen[$pid] = 1;
            $p = $d->prepare("SELECT * FROM products WHERE id=? AND status='active' FOR UPDATE"); $p->execute([$pid]); $p = $p->fetch();
            if (!$p) dom('المنتج غير موجود أو معطّل');
            if ((float)$p['stock'] + 0.0004 < $q) dom('المخزون غير كافٍ: ' . $p['name'] . ' (المتاح ' . qty($p['stock']) . ')');
            $below = $price + 0.004 < (float)$p['min_price'];
            if ($below) {
                if (!ctx_can($ctx, 'override_min_price')) dom('السعر أقل من الحد الأدنى للمنتج «' . $p['name'] . '» (' . money($p['min_price']) . ') ولا تملك صلاحية التجاوز');
                if ($why === '') dom('سبب البيع تحت الحد الأدنى مطلوب');
            }
            $ref = max((float)$p['base_price'], $price);
            $lineDisc = r2(($ref - $price) * $q); $sub += $ref * $q; $disc += $lineDisc;
            if (abs($price - (float)$p['base_price']) > 0.004) $ovr[] = ['product' => $p['name'], 'base' => (float)$p['base_price'], 'final' => $price, 'min' => (float)$p['min_price'], 'below_min' => $below];
            $lines[] = [$p, $q, $price, $lineDisc, r2($price * $q), $below];
        }
        $total = r2(array_sum(array_column($lines, 4)));
        if ($total <= 0) dom('إجمالي الفاتورة يجب أن يكون أكبر من صفر');
        $paidIn = $in['paid'] ?? null;
        if ($paidIn === null || $paidIn === '') $paid = $total;
        elseif (!is_numeric($paidIn)) dom('المبلغ المدفوع غير صالح');
        else $paid = r2($paidIn);
        if ($paid < 0) dom('المبلغ المدفوع لا يمكن أن يكون سالبًا');
        if ($paid > $total + 0.004) dom('المبلغ المدفوع (' . money($paid) . ') أكبر من إجمالي الفاتورة (' . money($total) . ')');
        $due = max(0, r2($total - $paid));
        if ($due > 0.004) {
            if (!$cid) dom('اختر العميل للبيع الآجل أو الدفع الجزئي');
            if (!ctx_can($ctx, 'credit_sale')) dom('لا تملك صلاحية البيع الآجل');
        }
        $method = null;
        if ($paid > 0.004) {
            $m = $d->prepare('SELECT id,name FROM payment_methods WHERE id=? AND is_active=1'); $m->execute([$mid]);
            if (!($method = $m->fetch())) dom('اختر طريقة دفع صالحة');
        }
        $ps = $due <= 0.004 ? 'paid' : ($paid > 0.004 ? 'partial' : 'credit');
        $inv = next_invoice_no($d);
        $d->prepare('INSERT INTO sales(invoice_no,client_token,user_id,customer_id,subtotal,discount_total,total,paid,due,pay_status) VALUES(?,?,?,?,?,?,?,?,?,?)')
          ->execute([$inv, $tok, $uid, $cid, r2($sub), r2($disc), $total, 0, $total, 'credit']);
        $sid = (int)$d->lastInsertId();
        foreach ($lines as [$p, $q, $price, $lineDisc, $lt, $below]) {
            $d->prepare('INSERT INTO sale_items(sale_id,product_id,name,qty,base_price,unit_price,discount,unit_cost,line_total,override_by,override_reason) VALUES(?,?,?,?,?,?,?,?,?,?,?)')
              ->execute([$sid, $p['id'], $p['name'], $q, $p['base_price'], $price, $lineDisc, $p['cost_price'], $lt, $below ? $uid : null, $below ? $why : null]);
            move_stock($d, (int)$p['id'], 'sale', -$q, $sid, $uid, $inv);
        }
        if ($paid > 0.004) {
            $d->prepare("INSERT INTO payments(sale_id,customer_id,user_id,payment_method_id,type,amount,payment_date) VALUES(?,?,?,?, 'sale',?,?)")->execute([$sid, $cid, $uid, $method['id'], $paid, today()]);
            $d->prepare('INSERT INTO payment_allocations(payment_id,sale_id,amount) VALUES(?,?,?)')->execute([$d->lastInsertId(), $sid, $paid]);
        }
        recalc_sale($d, $sid);
        if ($cid) recalc_customer_balance($d, $cid);
        audit_for($uid, 'sale_created', 'sale', $sid, ['invoice' => $inv, 'customer' => $cust['name'] ?? null, 'total' => $total, 'paid' => $paid, 'due' => $due, 'method' => $method['name'] ?? null, 'items' => count($lines), 'cashier' => user_name($d, $uid)]);
        if ($ovr) audit_for($uid, 'price_override', 'sale', $sid, ['invoice' => $inv, 'items' => $ovr, 'reason' => $why, 'by' => user_name($d, $uid)]);
        return ['id' => $sid, 'invoice' => $inv, 'total' => $total, 'paid' => $paid, 'due' => $due, 'status' => $ps];
    });
}

/* =============== إلغاء فاتورة بيع =============== */
function cancel_sale(PDO $d, array $ctx, int $id, string $reason): array {
    $uid = (int)$ctx['user_id']; $reason = trim($reason);
    if (!ctx_can($ctx, 'cancel_sale')) dom('لا تملك صلاحية إلغاء الفواتير');
    if ($reason === '') dom('سبب الإلغاء مطلوب');
    return in_tx($d, function () use ($d, $uid, $id, $reason) {
        $s = $d->prepare('SELECT * FROM sales WHERE id=? FOR UPDATE'); $s->execute([$id]); $sale = $s->fetch();
        if (!$sale || $sale['status'] !== 'completed') dom('لا يمكن إلغاء هذه الفاتورة (غير موجودة أو ملغاة سابقًا)');
        // 1) إرجاع المخزون
        $i = $d->prepare('SELECT product_id,qty FROM sale_items WHERE sale_id=?'); $i->execute([$id]);
        foreach ($i->fetchAll() as $r) move_stock($d, (int)$r['product_id'], 'return', (float)$r['qty'], $id, $uid, 'إلغاء ' . $sale['invoice_no']);
        // 2) عكس كل الدفعات الموزعة على الفاتورة (سواء عند البيع أو لاحقًا) — يُردّ المبلغ للعميل
        $g = $d->prepare('SELECT pa.payment_id,SUM(pa.amount) a,p.payment_method_id m FROM payment_allocations pa JOIN payments p ON p.id=pa.payment_id WHERE pa.sale_id=? GROUP BY pa.payment_id,p.payment_method_id HAVING SUM(pa.amount)>0.004');
        $g->execute([$id]); $refund = 0;
        foreach ($g->fetchAll() as $r) {
            $d->prepare("INSERT INTO payments(sale_id,customer_id,user_id,payment_method_id,type,reversal_of,amount,payment_date,notes) VALUES(?,?,?,?, 'reversal',?,?,?,?)")
              ->execute([$id, $sale['customer_id'], $uid, $r['m'], $r['payment_id'], -$r['a'], today(), 'عكس دفعة بسبب إلغاء الفاتورة ' . $sale['invoice_no']]);
            $d->prepare('INSERT INTO payment_allocations(payment_id,sale_id,amount) VALUES(?,?,?)')->execute([$d->lastInsertId(), $id, -$r['a']]);
            $refund += (float)$r['a'];
        }
        $d->prepare("UPDATE sales SET status='cancelled',cancelled_by=?,cancelled_at=NOW(),cancel_reason=?,refunded=? WHERE id=?")->execute([$uid, $reason, r2($refund), $id]);
        recalc_sale($d, $id);
        // 3) تصحيح رصيد العميل من الدفتر
        $bal = null; $name = null;
        if ($sale['customer_id']) {
            $bal = recalc_customer_balance($d, (int)$sale['customer_id']);
            $n = $d->prepare('SELECT name FROM customers WHERE id=?'); $n->execute([$sale['customer_id']]); $name = $n->fetchColumn();
        }
        audit_for($uid, 'sale_cancelled', 'sale', $id, ['invoice' => $sale['invoice_no'], 'customer' => $name, 'total' => (float)$sale['total'], 'refunded' => r2($refund), 'customer_balance' => $bal, 'reason' => $reason, 'cancelled_by' => user_name($d, $uid)]);
        return ['refunded' => r2($refund), 'customer_balance' => $bal];
    });
}

/* =============== دفعة عميل (تُوزَّع على أقدم الفواتير) =============== */
function receive_customer_payment(PDO $d, array $ctx, int $cid, int $mid, $amount, string $date, string $ref = '', string $notes = ''): array {
    $uid = (int)$ctx['user_id'];
    if (!ctx_can($ctx, 'receive_customer_payment')) dom('لا تملك صلاحية تسجيل دفعات العملاء');
    $amt = r2($amount);
    if (!is_date($date)) dom('تاريخ غير صالح');
    if ($date > today()) dom('لا يمكن تسجيل دفعة بتاريخ مستقبلي');
    if ($amt <= 0) dom('المبلغ يجب أن يكون أكبر من صفر');
    return in_tx($d, function () use ($d, $uid, $cid, $mid, $amt, $date, $ref, $notes) {
        $c = $d->prepare('SELECT id,name FROM customers WHERE id=? AND is_active=1 FOR UPDATE'); $c->execute([$cid]);
        if (!($cust = $c->fetch())) dom('العميل غير موجود أو معطّل');
        $m = $d->prepare('SELECT id,name FROM payment_methods WHERE id=? AND is_active=1'); $m->execute([$mid]);
        if (!($meth = $m->fetch())) dom('طريقة دفع غير صالحة');
        $old = customer_balance_calc($d, $cid);
        if ($old <= 0.004) dom('لا يوجد رصيد مستحق على هذا العميل');
        if ($amt > $old + 0.004) dom('المبلغ (' . money($amt) . ') أكبر من الرصيد المستحق (' . money($old) . ')');
        $d->prepare("INSERT INTO payments(customer_id,user_id,payment_method_id,type,amount,payment_date,reference,notes) VALUES(?,?,?, 'customer',?,?,?,?)")
          ->execute([$cid, $uid, $mid, $amt, $date, $ref !== '' ? mb_substr($ref, 0, 100) : null, $notes !== '' ? mb_substr($notes, 0, 250) : null]);
        $pid = (int)$d->lastInsertId(); $left = $amt;
        $q = $d->prepare("SELECT s.id,s.total-COALESCE((SELECT SUM(amount) FROM payment_allocations WHERE sale_id=s.id),0) room FROM sales s WHERE s.customer_id=? AND s.status='completed' ORDER BY s.id FOR UPDATE");
        $q->execute([$cid]);
        foreach ($q->fetchAll() as $r) {
            if ($left <= 0.004) break;
            $room = r2($r['room']); if ($room <= 0.004) continue;
            $a = r2(min($left, $room));
            $d->prepare('INSERT INTO payment_allocations(payment_id,sale_id,amount) VALUES(?,?,?)')->execute([$pid, $r['id'], $a]);
            recalc_sale($d, (int)$r['id']); $left = r2($left - $a);
        }
        $new = recalc_customer_balance($d, $cid);
        audit_for($uid, 'customer_payment', 'customer', $cid, ['customer' => $cust['name'], 'amount' => $amt, 'method' => $meth['name'], 'old_balance' => $old, 'new_balance' => $new, 'reference' => $ref, 'received_by' => user_name($d, $uid)]);
        return ['payment_id' => $pid, 'old_balance' => $old, 'new_balance' => $new];
    });
}

/* =============== المشتريات ودفعات الموردين =============== */
/** المدفوع/المتبقي/حالة الدفع لفاتورة الشراء من سجل الدفعات (المصدر الوحيد للحقيقة) */
function recalc_purchase(PDO $d, int $pid): void {
    $s = $d->prepare('SELECT total FROM purchases WHERE id=?'); $s->execute([$pid]); $total = $s->fetchColumn();
    if ($total === false) return;
    $p = $d->prepare('SELECT COALESCE(SUM(amount),0) FROM supplier_payments WHERE purchase_id=?'); $p->execute([$pid]);
    $paid = r2($p->fetchColumn());
    $st = $paid >= r2($total) - 0.004 ? 'paid' : ($paid > 0.004 ? 'partial' : 'credit');
    $d->prepare('UPDATE purchases SET paid=?,pay_status=? WHERE id=?')->execute([$paid, $st, $pid]);
}
/** رصيد المورد (ما علينا له) = إجمالي الفواتير غير الملغاة − كل الدفعات (الموجبة والمعكوسة) */
function supplier_balance_calc(PDO $d, int $sid): float {
    $a = $d->prepare("SELECT COALESCE(SUM(total),0) FROM purchases WHERE supplier_id=? AND status='active'"); $a->execute([$sid]);
    $b = $d->prepare('SELECT COALESCE(SUM(amount),0) FROM supplier_payments WHERE supplier_id=?'); $b->execute([$sid]);
    return r2($a->fetchColumn() - $b->fetchColumn());
}
function recalc_supplier_balance(PDO $d, int $sid): float {
    $bal = supplier_balance_calc($d, $sid);
    $d->prepare('UPDATE suppliers SET balance=? WHERE id=?')->execute([$bal, $sid]);
    return $bal;
}
function purchase_pay_label(array $p): string {
    if (($p['status'] ?? '') === 'cancelled') return 'ملغاة';
    return ['paid' => 'مدفوعة بالكامل', 'partial' => 'مدفوعة جزئيًا', 'credit' => 'آجلة (غير مدفوعة)'][$p['pay_status']] ?? '—';
}

function create_purchase(PDO $d, array $ctx, int $supplierId, string $ref, array $lines, $paidIn, int $methodId = 0, string $payRef = ''): array {
    $uid = (int)$ctx['user_id'];
    if (!ctx_can($ctx, 'create_purchase')) dom('لا تملك صلاحية تسجيل المشتريات');
    $L = []; $tot = 0;
    foreach ($lines as $l) {
        $p = (int)($l['product_id'] ?? 0); $q = round((float)($l['qty'] ?? 0), 3); $c = isset($l['cost']) && is_numeric($l['cost']) ? r2($l['cost']) : -1;
        if ($p <= 0 && $q == 0 && ($l['cost'] ?? '') === '') continue;
        if ($p <= 0 || $q <= 0 || $c < 0) dom('بند شراء غير صالح (المنتج والكمية والتكلفة مطلوبة)');
        $L[] = [$p, $q, $c]; $tot += r2($q * $c);
    }
    if (!$L) dom('أضف بندًا واحدًا على الأقل');
    $tot = r2($tot);
    if ($tot <= 0) dom('إجمالي الشراء يجب أن يكون أكبر من صفر');
    if ($paidIn === '' || $paidIn === null) $paid = 0.0; elseif (!is_numeric($paidIn)) dom('المبلغ المدفوع غير صالح'); else $paid = r2($paidIn);
    if ($paid < 0) dom('المبلغ المدفوع لا يمكن أن يكون سالبًا');
    if ($paid > $tot + 0.004) dom('المبلغ المدفوع (' . money($paid) . ') أكبر من إجمالي الشراء (' . money($tot) . ')');
    return in_tx($d, function () use ($d, $uid, $supplierId, $ref, $L, $tot, $paid, $methodId, $payRef) {
        $s = $d->prepare('SELECT id,name FROM suppliers WHERE id=? AND is_active=1 FOR UPDATE'); $s->execute([$supplierId]);
        if (!($sup = $s->fetch())) dom('المورد غير موجود أو معطّل');
        $method = null;
        if ($paid > 0.004) {
            $m = $d->prepare('SELECT id,name FROM payment_methods WHERE id=? AND is_active=1'); $m->execute([$methodId]);
            if (!($method = $m->fetch())) dom('اختر طريقة دفع صالحة للمبلغ المدفوع');
        }
        $d->prepare('INSERT INTO purchases(supplier_id,ref,total,paid,pay_status,user_id) VALUES(?,?,?,0,\'credit\',?)')->execute([$supplierId, mb_substr($ref, 0, 60) ?: null, $tot, $uid]);
        $pid = (int)$d->lastInsertId();
        foreach ($L as [$p, $q, $c]) {
            $pr = $d->prepare('SELECT id FROM products WHERE id=? FOR UPDATE'); $pr->execute([$p]); if (!$pr->fetch()) dom('منتج غير موجود في البنود');
            $d->prepare('INSERT INTO purchase_items(purchase_id,product_id,qty,cost,line_total) VALUES(?,?,?,?,?)')->execute([$pid, $p, $q, $c, r2($q * $c)]);
            move_stock($d, $p, 'purchase', $q, $pid, $uid, 'شراء ' . purchase_no($pid));
            $d->prepare('UPDATE products SET cost_price=? WHERE id=?')->execute([$c, $p]);
        }
        if ($paid > 0.004) $d->prepare("INSERT INTO supplier_payments(supplier_id,purchase_id,user_id,payment_method_id,type,amount,payment_date,reference,notes) VALUES(?,?,?,?, 'initial',?,?,?,?)")
            ->execute([$supplierId, $pid, $uid, $method['id'], $paid, today(), $payRef !== '' ? mb_substr($payRef, 0, 100) : null, 'دفعة عند الشراء']);
        recalc_purchase($d, $pid);
        $bal = recalc_supplier_balance($d, $supplierId);
        audit_for($uid, 'purchase_created', 'purchase', $pid, ['purchase_no' => purchase_no($pid), 'supplier' => $sup['name'], 'ref' => $ref, 'total' => $tot, 'paid' => $paid, 'due' => r2($tot - $paid), 'method' => $method['name'] ?? null, 'items' => count($L), 'supplier_balance' => $bal, 'by' => user_name($d, $uid)]);
        return ['id' => $pid, 'total' => $tot, 'paid' => $paid, 'due' => r2($tot - $paid), 'supplier_balance' => $bal];
    });
}

/** دفعة جديدة لفاتورة شراء قائمة — ذرّية: تحقق ← حفظ الدفعة ← تحديث الفاتورة والمورد ← تدقيق */
function pay_purchase(PDO $d, array $ctx, int $purchaseId, int $methodId, $amount, string $date, string $ref = '', string $notes = ''): array {
    $uid = (int)$ctx['user_id'];
    if (!ctx_can($ctx, 'pay_supplier')) dom('لا تملك صلاحية تسجيل دفعات للموردين');
    if (!is_numeric($amount)) dom('المبلغ غير صالح');
    $amt = r2($amount);
    if ($amt <= 0) dom('المبلغ يجب أن يكون أكبر من صفر');
    if (!is_date($date)) dom('تاريخ الدفعة غير صالح');
    if ($date > today()) dom('لا يمكن تسجيل دفعة بتاريخ مستقبلي');
    if (mb_strlen($ref) > 100 || mb_strlen($notes) > 250) dom('المرجع أو الملاحظات أطول من المسموح');
    return in_tx($d, function () use ($d, $uid, $purchaseId, $methodId, $amt, $date, $ref, $notes) {
        $s = $d->prepare('SELECT p.*,s.name sn FROM purchases p JOIN suppliers s ON s.id=p.supplier_id WHERE p.id=? FOR UPDATE'); $s->execute([$purchaseId]); $p = $s->fetch();
        if (!$p) dom('فاتورة الشراء غير موجودة');
        if ($p['status'] === 'cancelled') dom('لا يمكن تسجيل دفعة على فاتورة شراء ملغاة');
        $paidNow = $d->prepare('SELECT COALESCE(SUM(amount),0) FROM supplier_payments WHERE purchase_id=?'); $paidNow->execute([$purchaseId]);
        $before = r2($p['total'] - $paidNow->fetchColumn());
        if ($before <= 0.004) dom('فاتورة الشراء مدفوعة بالكامل ولا يوجد مبلغ متبقٍ');
        if ($amt > $before + 0.004) dom('المبلغ (' . money($amt) . ') أكبر من المتبقي على الفاتورة (' . money($before) . ')');
        if (substr($p['created_at'], 0, 10) > $date) dom('تاريخ الدفعة لا يمكن أن يسبق تاريخ الفاتورة (' . d($p['created_at']) . ')');
        $m = $d->prepare('SELECT id,name FROM payment_methods WHERE id=? AND is_active=1'); $m->execute([$methodId]);
        if (!($meth = $m->fetch())) dom('طريقة الدفع غير صالحة');
        $d->prepare("INSERT INTO supplier_payments(supplier_id,purchase_id,user_id,payment_method_id,type,amount,payment_date,reference,notes) VALUES(?,?,?,?, 'payment',?,?,?,?)")
          ->execute([$p['supplier_id'], $purchaseId, $uid, $meth['id'], $amt, $date, $ref !== '' ? $ref : null, $notes !== '' ? $notes : null]);
        $pay = (int)$d->lastInsertId();
        recalc_purchase($d, $purchaseId);
        $bal = recalc_supplier_balance($d, (int)$p['supplier_id']);
        $after = r2($before - $amt);
        audit_for($uid, 'supplier_payment', 'purchase', $purchaseId, ['purchase_no' => purchase_no($purchaseId), 'supplier' => $p['sn'], 'amount' => $amt, 'method' => $meth['name'], 'remaining_before' => $before, 'remaining_after' => $after, 'supplier_balance' => $bal, 'reference' => $ref, 'by' => user_name($d, $uid)]);
        return ['payment_id' => $pay, 'remaining_before' => $before, 'remaining' => $after, 'supplier_balance' => $bal, 'status' => $after <= 0.004 ? 'paid' : 'partial'];
    });
}

/** إلغاء فاتورة شراء: يعكس المخزون وكل الدفعات (تُسجَّل كاسترداد من المورد) ويُبقي السجل التاريخي كاملًا */
function cancel_purchase(PDO $d, array $ctx, int $id, string $reason): array {
    $uid = (int)$ctx['user_id']; $reason = trim($reason);
    if (!ctx_can($ctx, 'cancel_purchase')) dom('لا تملك صلاحية إلغاء المشتريات');
    if ($reason === '') dom('سبب الإلغاء مطلوب');
    return in_tx($d, function () use ($d, $uid, $id, $reason) {
        $s = $d->prepare('SELECT p.*,s.name sn FROM purchases p JOIN suppliers s ON s.id=p.supplier_id WHERE p.id=? FOR UPDATE'); $s->execute([$id]); $p = $s->fetch();
        if (!$p || $p['status'] !== 'active') dom('لا يمكن إلغاء هذه الفاتورة (غير موجودة أو ملغاة سابقًا)');
        $i = $d->prepare('SELECT product_id,qty FROM purchase_items WHERE purchase_id=?'); $i->execute([$id]);
        foreach ($i->fetchAll() as $r) move_stock($d, (int)$r['product_id'], 'purchase_cancel', -(float)$r['qty'], $id, $uid, 'إلغاء ' . purchase_no($id));
        $g = $d->prepare('SELECT payment_method_id m,SUM(amount) a FROM supplier_payments WHERE purchase_id=? GROUP BY payment_method_id HAVING SUM(amount)>0.004');
        $g->execute([$id]); $refund = 0;
        foreach ($g->fetchAll() as $r) {
            $d->prepare("INSERT INTO supplier_payments(supplier_id,purchase_id,user_id,payment_method_id,type,amount,payment_date,notes) VALUES(?,?,?,?, 'reversal',?,?,?)")
              ->execute([$p['supplier_id'], $id, $uid, $r['m'], -$r['a'], today(), 'عكس دفعة بسبب إلغاء الفاتورة ' . purchase_no($id)]);
            $refund += (float)$r['a'];
        }
        $d->prepare("UPDATE purchases SET status='cancelled',cancel_reason=?,cancelled_by=?,cancelled_at=NOW(),refunded=? WHERE id=?")->execute([$reason, $uid, r2($refund), $id]);
        recalc_purchase($d, $id);
        $bal = recalc_supplier_balance($d, (int)$p['supplier_id']);
        audit_for($uid, 'purchase_cancelled', 'purchase', $id, ['purchase_no' => purchase_no($id), 'supplier' => $p['sn'], 'total' => (float)$p['total'], 'refunded' => r2($refund), 'supplier_balance' => $bal, 'reason' => $reason, 'by' => user_name($d, $uid)]);
        return ['refunded' => r2($refund), 'supplier_balance' => $bal];
    });
}

/* =============== تسوية المخزون =============== */
function adjust_stock(PDO $d, array $ctx, int $pid, string $dir, $qty, string $note): array {
    $uid = (int)$ctx['user_id'];
    if (!ctx_can($ctx, 'manage_inventory')) dom('لا تملك صلاحية تسوية المخزون');
    $q = round((float)$qty, 3); $note = trim($note);
    if ($q <= 0) dom('الكمية يجب أن تكون أكبر من صفر');
    if ($note === '') dom('سبب التسوية مطلوب');
    return in_tx($d, function () use ($d, $uid, $pid, $dir, $q, $note) {
        [$b, $a] = move_stock($d, $pid, 'adjustment', $dir === 'out' ? -$q : $q, null, $uid, $note);
        $n = $d->prepare('SELECT name FROM products WHERE id=?'); $n->execute([$pid]);
        audit_for($uid, 'inventory_adjustment', 'product', $pid, ['product' => $n->fetchColumn(), 'qty' => $dir === 'out' ? -$q : $q, 'before' => $b, 'after' => $a, 'note' => $note, 'by' => user_name($d, $uid)]);
        return ['before' => $b, 'after' => $a];
    });
}

/* =============== فحص سلامة البيانات =============== */
function integrity_report(PDO $d): array {
    $issues = [];
    foreach ($d->query('SELECT id,name,balance FROM customers')->fetchAll() as $c) {
        $calc = customer_balance_calc($d, (int)$c['id']);
        if (abs($calc - (float)$c['balance']) > 0.004) $issues[] = ['type' => 'رصيد عميل', 'ref' => $c['name'], 'msg' => 'المخزَّن ' . money($c['balance']) . ' ≠ المحسوب من الدفتر ' . money($calc), 'fixable' => true, 'cid' => $c['id']];
    }
    $q = $d->query("SELECT s.id,s.invoice_no,s.total,s.paid,s.due,COALESCE((SELECT SUM(amount) FROM payment_allocations WHERE sale_id=s.id),0) alloc,COALESCE((SELECT SUM(line_total) FROM sale_items WHERE sale_id=s.id),0) lsum FROM sales s WHERE s.status='completed'");
    foreach ($q->fetchAll() as $s) {
        if (abs($s['alloc'] - $s['paid']) > 0.004 || abs($s['total'] - $s['paid'] - $s['due']) > 0.004) $issues[] = ['type' => 'فاتورة', 'ref' => $s['invoice_no'], 'msg' => 'المدفوع/المتبقي لا يطابق الدفعات الموزعة', 'fixable' => true, 'sid' => $s['id']];
        if (abs($s["lsum"] - $s["total"]) > 0.009) $issues[] = ['type' => 'فاتورة', 'ref' => $s['invoice_no'], 'msg' => 'إجمالي البنود (' . money($s["lsum"]) . ') ≠ إجمالي الفاتورة (' . money($s['total']) . ')', 'fixable' => false];
    }
    $q = $d->query('SELECT p.id,p.name,p.stock,COALESCE((SELECT SUM(qty) FROM inventory_movements WHERE product_id=p.id),0) mv FROM products p');
    foreach ($q->fetchAll() as $p) if (abs($p['stock'] - $p['mv']) > 0.0009) $issues[] = ['type' => 'مخزون', 'ref' => $p['name'], 'msg' => 'الرصيد ' . qty($p['stock']) . ' ≠ مجموع الحركات ' . qty($p['mv']), 'fixable' => false];
    $q = $d->query('SELECT p.id,p.amount,COALESCE((SELECT SUM(amount) FROM payment_allocations WHERE payment_id=p.id),0) a FROM payments p WHERE p.amount>0 AND p.type<>\'reversal\'');
    foreach ($q->fetchAll() as $p) if ($p['a'] > $p['amount'] + 0.004) $issues[] = ['type' => 'دفعة', 'ref' => '#' . $p['id'], 'msg' => 'الموزَّع أكبر من مبلغ الدفعة', 'fixable' => false];
    foreach ($d->query('SELECT id,name,balance FROM suppliers')->fetchAll() as $c) {
        $calc = supplier_balance_calc($d, (int)$c['id']);
        if (abs($calc - (float)$c['balance']) > 0.004) $issues[] = ['type' => 'رصيد مورد', 'ref' => $c['name'], 'msg' => 'المخزَّن ' . money($c['balance']) . ' ≠ المحسوب ' . money($calc), 'fixable' => true];
    }
    foreach ($d->query("SELECT p.id,p.total,p.paid,COALESCE((SELECT SUM(amount) FROM supplier_payments WHERE purchase_id=p.id),0) a FROM purchases p")->fetchAll() as $p) {
        if (abs($p['a'] - $p['paid']) > 0.004) $issues[] = ['type' => 'فاتورة شراء', 'ref' => purchase_no((int)$p['id']), 'msg' => 'المدفوع لا يطابق سجل الدفعات', 'fixable' => true];
        if ($p['a'] > $p['total'] + 0.004) $issues[] = ['type' => 'فاتورة شراء', 'ref' => purchase_no((int)$p['id']), 'msg' => 'المدفوع أكبر من إجمالي الفاتورة', 'fixable' => false];
    }
    return $issues;
}

/* =============== المصروفات =============== */
function save_expense(PDO $d, array $ctx, int $id, int $cat, $amount, string $date, string $desc): int {
    $uid = (int)$ctx['user_id'];
    if (!ctx_can($ctx, 'manage_expenses')) dom('لا تملك صلاحية إدارة المصروفات');
    $amt = r2($amount); $desc = trim($desc);
    if ($amt <= 0) dom('المبلغ يجب أن يكون أكبر من صفر');
    if (!is_date($date)) dom('التاريخ غير صالح');
    if ($date > today()) dom('لا يمكن تسجيل مصروف بتاريخ مستقبلي');
    if (mb_strlen($desc) > 255) dom('الوصف طويل جدًا');
    return in_tx($d, function () use ($d, $uid, $id, $cat, $amt, $date, $desc) {
        $c = $d->prepare('SELECT name FROM expense_categories WHERE id=? AND is_active=1'); $c->execute([$cat]);
        if (!($cn = $c->fetchColumn())) dom('اختر تصنيف مصروف فعّالًا');
        if ($id) {
            $s = $d->prepare('SELECT e.*,c.name cn FROM expenses e JOIN expense_categories c ON c.id=e.category_id WHERE e.id=? FOR UPDATE'); $s->execute([$id]); $o = $s->fetch();
            if (!$o) dom('المصروف غير موجود');
            if ($o['status'] === 'cancelled') dom('لا يمكن تعديل مصروف ملغى (يحافظ ذلك على سجل التدقيق)');
            $d->prepare('UPDATE expenses SET category_id=?,amount=?,description=?,expense_date=?,updated_by=?,updated_at=NOW() WHERE id=?')->execute([$cat, $amt, $desc ?: null, $date, $uid, $id]);
            $chg = [];
            if ((int)$o['category_id'] !== $cat) $chg['التصنيف'] = [$o['cn'], $cn];
            if (abs($o['amount'] - $amt) > 0.004) $chg['المبلغ'] = [(float)$o['amount'], $amt];
            if ($o['expense_date'] !== $date) $chg['التاريخ'] = [$o['expense_date'], $date];
            if ((string)$o['description'] !== $desc) $chg['الوصف'] = [$o['description'], $desc];
            audit_for($uid, 'expense_updated', 'expense', $id, ['category' => $cn, 'amount' => $amt, 'changes' => $chg, 'by' => user_name($d, $uid)]);
            return $id;
        }
        $d->prepare('INSERT INTO expenses(category_id,amount,description,expense_date,user_id) VALUES(?,?,?,?,?)')->execute([$cat, $amt, $desc ?: null, $date, $uid]);
        $nid = (int)$d->lastInsertId();
        audit_for($uid, 'expense_created', 'expense', $nid, ['category' => $cn, 'amount' => $amt, 'date' => $date, 'description' => $desc, 'by' => user_name($d, $uid)]);
        return $nid;
    });
}
function cancel_expense(PDO $d, array $ctx, int $id, string $reason): void {
    $uid = (int)$ctx['user_id']; $reason = trim($reason);
    if (!ctx_can($ctx, 'cancel_expense')) dom('لا تملك صلاحية إلغاء المصروفات');
    if ($reason === '') dom('سبب الإلغاء مطلوب');
    in_tx($d, function () use ($d, $uid, $id, $reason) {
        $s = $d->prepare('SELECT e.*,c.name cn FROM expenses e JOIN expense_categories c ON c.id=e.category_id WHERE e.id=? FOR UPDATE'); $s->execute([$id]); $e = $s->fetch();
        if (!$e || $e['status'] !== 'active') dom('لا يمكن إلغاء هذا المصروف (غير موجود أو ملغى سابقًا)');
        $d->prepare("UPDATE expenses SET status='cancelled',cancel_reason=?,cancelled_by=?,cancelled_at=NOW() WHERE id=?")->execute([$reason, $uid, $id]);
        audit_for($uid, 'expense_cancelled', 'expense', $id, ['category' => $e['cn'], 'amount' => (float)$e['amount'], 'date' => $e['expense_date'], 'reason' => $reason, 'by' => user_name($d, $uid)]);
    });
}

/* =============== دفتر حساب المورد (مشترك بين الصفحة والـ PDF) =============== */
/** يرجع [الحركات مع الرصيد الجاري، الرصيد السابق، إجمالي المشتريات، إجمالي المدفوعات] ضمن الفترة. الرصيد = ما علينا للمورد */
function supplier_ledger(PDO $d, int $sid, string $f = '', string $t = ''): array {
    $sql = "SELECT p.created_at dt,p.id pid,'purchase' kind,CONCAT('PUR-',LPAD(p.id,5,'0')) ref,'فاتورة شراء' label,0 debit,p.total credit,p.id ord FROM purchases p WHERE p.supplier_id=?
      UNION ALL SELECT p.cancelled_at,p.id,'cancel',CONCAT('PUR-',LPAD(p.id,5,'0')),'إلغاء فاتورة شراء',p.total,0,p.id FROM purchases p WHERE p.supplier_id=? AND p.status='cancelled'
      UNION ALL SELECT TIMESTAMP(sp.payment_date,TIME(sp.created_at)),sp.purchase_id,sp.type,CONCAT('PUR-',LPAD(sp.purchase_id,5,'0')),
        CONCAT(CASE sp.type WHEN 'initial' THEN 'دفعة عند الشراء' WHEN 'payment' THEN 'دفعة للمورد' ELSE 'استرداد (عكس دفعة)' END,' - ',pm.name),
        IF(sp.amount>0,sp.amount,0),IF(sp.amount<0,-sp.amount,0),sp.id FROM supplier_payments sp JOIN payment_methods pm ON pm.id=sp.payment_method_id WHERE sp.supplier_id=?
      ORDER BY dt,ord";
    $s = $d->prepare($sql); $s->execute([$sid, $sid, $sid]);
    $open = 0.0; $rows = [];
    foreach ($s->fetchAll() as $e) {
        $day = substr((string)$e['dt'], 0, 10);
        if ($f !== '' && $day < $f) { $open += $e['credit'] - $e['debit']; continue; }
        if ($t !== '' && $day > $t) continue;
        $rows[] = $e;
    }
    $bal = r2($open); $out = []; $tp = 0; $tpay = 0;
    foreach ($rows as $e) { $bal = r2($bal + $e['credit'] - $e['debit']); $tp += $e['credit']; $tpay += $e['debit']; $out[] = $e + ['bal' => $bal]; }
    return [$out, r2($open), r2($tp), r2($tpay)];
}
