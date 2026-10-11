<?php
/** JOUD — استعلامات لوحة التحكم (كلها من قاعدة البيانات، لا أرقام ثابتة). */
function dash_range_sql(string $col, string $f, string $t): array { return ["$col>=? AND $col<?", [$f, day_after($t)]]; }

function dash_kpis(string $f, string $t): array {
    [$w, $p] = dash_range_sql('s.created_at', $f, $t);
    $s = q("SELECT COUNT(*) n,COALESCE(SUM(s.total),0) total,COALESCE(SUM(s.paid),0) paid,COALESCE(SUM(s.due),0) due,COALESCE(SUM(s.discount_total),0) disc FROM sales s WHERE s.status='completed' AND $w", $p)->fetch();
    $profit = (float)q("SELECT COALESCE(SUM(i.line_total-i.qty*COALESCE(i.unit_cost,pr.cost_price)),0) FROM sale_items i JOIN sales s ON s.id=i.sale_id JOIN products pr ON pr.id=i.product_id WHERE s.status='completed' AND $w", $p)->fetchColumn();
    $collected = (float)q('SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_date>=? AND payment_date<=?', [$f, $t])->fetchColumn();
    [$wp, $pp] = dash_range_sql('p.created_at', $f, $t);
    $pu = q("SELECT COALESCE(SUM(p.total),0) total,COALESCE(SUM(p.paid),0) paid,COUNT(*) n FROM purchases p WHERE p.status='active' AND $wp", $pp)->fetch();
    $sp = (float)q('SELECT COALESCE(SUM(amount),0) FROM supplier_payments WHERE payment_date>=? AND payment_date<=?', [$f, $t])->fetchColumn();
    $sdebt = (float)q('SELECT COALESCE(SUM(balance),0) FROM suppliers WHERE balance>0')->fetchColumn();
    $ex = q("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE status='active' AND expense_date>=? AND expense_date<=?", [$f, $t])->fetchColumn();
    $prod = q("SELECT COUNT(*) n,COALESCE(SUM(stock<=min_stock),0) low FROM products WHERE status='active'")->fetch();
    $debt = q('SELECT COALESCE(SUM(balance),0) t,COUNT(*) n FROM customers WHERE balance>0')->fetch();
    return $s + ['profit' => $profit, 'collected' => $collected, 'purch' => (float)$pu['total'], 'purch_paid' => $sp, 'sup_debt' => $sdebt, 'purch_n' => (int)$pu['n'], 'exp' => (float)$ex,
        'net_cash' => $collected - $sp - (float)$ex, 'products' => (int)$prod['n'], 'low' => (int)$prod['low'], 'debt' => (float)$debt['t'], 'debtors' => (int)$debt['n']];
}

/** سلسلة المبيعات للرسم: ساعات (يوم) | أيام (أسبوع/شهر/مخصص قصير) | أشهر (سنة/مخصص طويل) */
function dash_series(string $f, string $t, string $kind): array {
    [$w, $p] = dash_range_sql('created_at', $f, $t);
    $days = (int)((strtotime($t) - strtotime($f)) / 86400) + 1;
    $labels = []; $keys = [];
    if ($kind === 'day') {
        $g = 'HOUR(created_at)'; for ($h = 0; $h < 24; $h++) { $keys[] = $h; $labels[] = sprintf('%02d:00', $h); } $gran = 'hour';
    } elseif ($kind === 'year' || ($kind === 'custom' && $days > 62)) {
        $g = "DATE_FORMAT(created_at,'%Y-%m')"; $gran = 'month';
        for ($c = strtotime(date('Y-m-01', strtotime($f))); $c <= strtotime($t); $c = strtotime('+1 month', $c)) { $keys[] = date('Y-m', $c); $labels[] = AR_MONTHS[(int)date('n', $c)] . ($kind === 'custom' ? ' ' . date('y', $c) : ''); }
    } else {
        $g = 'DATE(created_at)'; $gran = 'day';
        for ($c = strtotime($f); $c <= strtotime($t); $c = strtotime('+1 day', $c)) { $keys[] = date('Y-m-d', $c); $labels[] = $kind === 'week' ? AR_DAYS[(int)date('w', $c)] : (string)(int)date('j', $c); }
    }
    $rows = q("SELECT $g k,COALESCE(SUM(total),0) t,COUNT(*) n FROM sales WHERE status='completed' AND $w GROUP BY k", $p)->fetchAll(PDO::FETCH_UNIQUE);
    $tot = []; $cnt = [];
    foreach ($keys as $k) { $tot[] = (float)($rows[$k]['t'] ?? 0); $cnt[] = (int)($rows[$k]['n'] ?? 0); }
    return ['labels' => $labels, 'totals' => $tot, 'counts' => $cnt, 'gran' => $gran];
}

/** توزيع المبيعات: نقدي/بنك/... (من الدفعات الموزعة) + الآجل (المتبقي) */
function dash_split(string $f, string $t): array {
    [$w, $p] = dash_range_sql('s.created_at', $f, $t);
    $rows = q("SELECT pm.name,COALESCE(SUM(pa.amount),0) a FROM payment_allocations pa JOIN payments pp ON pp.id=pa.payment_id JOIN payment_methods pm ON pm.id=pp.payment_method_id JOIN sales s ON s.id=pa.sale_id WHERE s.status='completed' AND $w GROUP BY pm.id,pm.name HAVING a>0 ORDER BY pm.id", $p)->fetchAll();
    $due = (float)q("SELECT COALESCE(SUM(s.due),0) FROM sales s WHERE s.status='completed' AND $w", $p)->fetchColumn();
    $out = array_map(fn($r) => ['label' => $r['name'], 'value' => (float)$r['a']], $rows);
    if ($due > 0) $out[] = ['label' => 'آجل', 'value' => $due];
    return $out;
}
function dash_top_products(string $f, string $t, int $n = 6): array {
    [$w, $p] = dash_range_sql('s.created_at', $f, $t);
    return q("SELECT i.name,SUM(i.qty) q,SUM(i.line_total) t FROM sale_items i JOIN sales s ON s.id=i.sale_id WHERE s.status='completed' AND $w GROUP BY i.product_id,i.name ORDER BY t DESC LIMIT $n", $p)->fetchAll();
}
