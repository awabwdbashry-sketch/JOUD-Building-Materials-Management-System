<?php
/**
 * JOUD — ترقية قاعدة البيانات (idempotent: آمنة عند التكرار ولا تحذف بيانات).
 * تُنفَّذ تلقائيًا عند أول طلب بعد تحديث الملفات، أو يدويًا: php database/migrate.php
 */
const SCHEMA_VERSION = 3;

function mg_col(PDO $d, string $t, string $c): bool {
    $s = $d->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');
    $s->execute([$t, $c]); return (bool)$s->fetchColumn();
}
function mg_idx(PDO $d, string $t, string $i): bool {
    $s = $d->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=? AND index_name=?');
    $s->execute([$t, $i]); return (bool)$s->fetchColumn();
}
function mg_table(PDO $d, string $t): bool {
    $s = $d->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
    $s->execute([$t]); return (bool)$s->fetchColumn();
}
function mg_add_col(PDO $d, string $t, string $c, string $def, array &$log): void {
    if (!mg_col($d, $t, $c)) { $d->exec("ALTER TABLE `$t` ADD COLUMN `$c` $def"); $log[] = "أُضيف العمود $t.$c"; }
}
function mg_add_idx(PDO $d, string $t, string $i, string $cols, array &$log): void {
    if (!mg_idx($d, $t, $i)) { $d->exec("ALTER TABLE `$t` ADD INDEX `$i` ($cols)"); $log[] = "أُضيف الفهرس $t.$i"; }
}

function ensure_schema(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try { db(); } catch (Throwable $x) {
        http_response_code(500);
        exit('<!doctype html><meta charset="utf-8"><body dir="rtl" style="font-family:Tahoma;padding:2rem"><h3>تعذر الاتصال بقاعدة البيانات</h3><p>تأكد أن MySQL يعمل وأن قاعدة <b>' . e(DB_NAME) . '</b> موجودة (استورد <code>database/joud.sql</code>) وأن بيانات الاتصال في <code>config/config.php</code> صحيحة.</p></body>');
    }
    try { $v = (int)db()->query("SELECT v FROM settings WHERE k='schema_version'")->fetchColumn(); }
    catch (Throwable $x) { $v = 0; if (!mg_table(db(), 'settings')) return; }   // قاعدة فارغة: ينتظر الاستيراد
    if ($v < SCHEMA_VERSION) run_migrations();
}

function run_migrations(): array {
    $d = db(); $log = [];
    $lock = $d->query("SELECT GET_LOCK('joud_migrate', 30)")->fetchColumn();
    if (!$lock) return ['تعذر الحصول على قفل الترقية'];
    try {
        $v = (int)$d->query("SELECT v FROM settings WHERE k='schema_version'")->fetchColumn();
        if ($v >= SCHEMA_VERSION) return ['قاعدة البيانات محدّثة'];
        require_once __DIR__ . '/accounting.php';
        if ($v < 2) migrate_v2($d, $log);
        if ($v < 3) migrate_v3($d, $log);
    } finally { $d->query("SELECT RELEASE_LOCK('joud_migrate')"); }
    return $log;
}

function migrate_v2(PDO $d, array &$log): void {
    /* 1) أعمدة وفهارس جديدة */
    mg_add_col($d, 'sales', 'refunded', 'DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER cancel_reason', $log);
    mg_add_idx($d, 'sales', 'idx_sales_status_date', 'status,created_at', $log);
    mg_add_idx($d, 'sales', 'idx_sales_user', 'user_id,created_at', $log);
    mg_add_idx($d, 'sale_items', 'idx_items_product', 'product_id', $log);
    mg_add_col($d, 'sale_items', 'unit_cost', 'DECIMAL(14,2) NULL AFTER discount', $log);   // تكلفة الوحدة وقت البيع (للربح)
    mg_add_col($d, 'payments', 'type', "ENUM('sale','customer','reversal') NOT NULL DEFAULT 'sale' AFTER payment_method_id", $log);
    mg_add_col($d, 'payments', 'reversal_of', 'INT NULL AFTER type', $log);
    mg_add_idx($d, 'payments', 'idx_pay_method', 'payment_method_id,payment_date', $log);
    mg_add_col($d, 'purchases', 'status', "ENUM('active','cancelled') NOT NULL DEFAULT 'active'", $log);
    mg_add_col($d, 'purchases', 'cancel_reason', 'VARCHAR(255) NULL', $log);
    mg_add_col($d, 'purchases', 'cancelled_by', 'INT NULL', $log);
    mg_add_col($d, 'purchases', 'cancelled_at', 'DATETIME NULL', $log);
    mg_add_idx($d, 'purchases', 'idx_purch_supplier', 'supplier_id', $log);
    mg_add_col($d, 'inventory_movements', 'qty_before', 'DECIMAL(14,3) NULL', $log);
    mg_add_col($d, 'inventory_movements', 'qty_after', 'DECIMAL(14,3) NULL', $log);
    $d->exec("ALTER TABLE inventory_movements MODIFY type ENUM('sale','purchase','adjustment','return','purchase_cancel') NOT NULL");
    mg_add_idx($d, 'inventory_movements', 'idx_mov_ref', 'type,ref_id', $log);
    mg_add_col($d, 'expenses', 'updated_by', 'INT NULL', $log);
    mg_add_col($d, 'expenses', 'updated_at', 'DATETIME NULL', $log);
    mg_add_idx($d, 'expenses', 'idx_exp_status_date', 'status,expense_date', $log);
    mg_add_col($d, 'users', 'last_login_at', 'DATETIME NULL', $log);
    mg_add_idx($d, 'audit_logs', 'idx_audit_user', 'user_id', $log);
    mg_add_idx($d, 'audit_logs', 'idx_audit_entity', 'entity,entity_id', $log);

    /* 2) جدول توزيع الدفعات على الفواتير */
    if (!mg_table($d, 'payment_allocations')) {
        $d->exec('CREATE TABLE payment_allocations(id INT AUTO_INCREMENT PRIMARY KEY,payment_id INT NOT NULL,sale_id INT NOT NULL,amount DECIMAL(14,2) NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(sale_id),INDEX(payment_id),FOREIGN KEY(payment_id) REFERENCES payments(id),FOREIGN KEY(sale_id) REFERENCES sales(id))ENGINE=InnoDB');
        $log[] = 'أُنشئ جدول payment_allocations';
    }

    /* 3) الصلاحيات */
    $newPerms = [
        'view_analytics' => 'عرض تحليلات لوحة التحكم', 'view_all_sales' => 'عرض فواتير جميع الكاشيرية',
        'view_sales_reports' => 'تقارير المبيعات والدفعات', 'cancel_purchase' => 'إلغاء فاتورة شراء',
        'delete_records' => 'حذف السجلات (عملاء، موردون، تصنيفات…)', 'manage_roles' => 'إدارة الأدوار والصلاحيات',
    ];
    foreach ($newPerms as $code => $label) $d->prepare('INSERT IGNORE INTO permissions(code,label) VALUES(?,?)')->execute([$code, $label]);

    /* 4) الأدوار: Admin + Cashier فقط */
    $d->exec("INSERT IGNORE INTO roles(name,label) VALUES('admin','المدير'),('cashier','الكاشير')");
    $admin = (int)$d->query("SELECT id FROM roles WHERE name='admin'")->fetchColumn();
    $cash = (int)$d->query("SELECT id FROM roles WHERE name='cashier'")->fetchColumn();
    $d->prepare("UPDATE roles SET label='المدير',is_active=1 WHERE id=?")->execute([$admin]);
    $d->prepare("UPDATE roles SET label='الكاشير',is_active=1 WHERE id=?")->execute([$cash]);
    $old = $d->prepare('SELECT r.id,r.label,GROUP_CONCAT(u.full_name SEPARATOR "، ") names,COUNT(u.id) c FROM roles r LEFT JOIN users u ON u.role_id=r.id WHERE r.id NOT IN (?,?) GROUP BY r.id');
    $old->execute([$admin, $cash]);
    foreach ($old->fetchAll() as $r) {
        if ($r['c'] > 0) {
            $d->prepare('UPDATE users SET role_id=? WHERE role_id=?')->execute([$cash, $r['id']]);
            $msg = "نُقل {$r['c']} مستخدم من دور «{$r['label']}» إلى «الكاشير»: {$r['names']} — راجع صلاحياتهم وارفعهم إلى مدير عند اللزوم";
            $log[] = $msg;
            $d->prepare("INSERT INTO audit_logs(action,entity,details) VALUES('roles_migrated','role',?)")->execute([json_encode(['role' => $r['label'], 'users' => $r['names'], 'moved_to' => 'الكاشير'], JSON_UNESCAPED_UNICODE)]);
        }
        $d->prepare('DELETE FROM roles WHERE id=?')->execute([$r['id']]);   // role_permissions تُحذف بـ CASCADE
        $log[] = "حُذف الدور القديم «{$r['label']}»";
    }
    $d->prepare('INSERT IGNORE INTO role_permissions(role_id,permission_id) SELECT ?,id FROM permissions')->execute([$admin]);
    $cashPerms = ['view_products', 'view_sales', 'create_sale', 'credit_sale', 'manage_customers', 'receive_customer_payment', 'view_sales_reports'];
    $has = $d->prepare('SELECT COUNT(*) FROM role_permissions WHERE role_id=?'); $has->execute([$cash]);
    // نعيد ضبط صلاحيات الكاشير فقط في أول ترقية (لا نمس تعديلات المدير لاحقًا)
    $d->prepare('DELETE FROM role_permissions WHERE role_id=?')->execute([$cash]);
    foreach ($cashPerms as $c) $d->prepare('INSERT IGNORE INTO role_permissions(role_id,permission_id) SELECT ?,id FROM permissions WHERE code=?')->execute([$cash, $c]);

    /* 5) الإعدادات */
    $defaults = ['email' => '', 'invoice_footer' => 'شكرًا لتعاملكم معنا', 'logo' => '', 'tax_no' => '', 'invoice_seq' => '0'];
    foreach ($defaults as $k => $v) $d->prepare('INSERT IGNORE INTO settings(k,v) VALUES(?,?)')->execute([$k, $v]);
    $maxSale = (int)$d->query('SELECT COALESCE(MAX(id),0) FROM sales')->fetchColumn();
    $d->prepare("UPDATE settings SET v=? WHERE k='invoice_seq' AND CAST(v AS UNSIGNED)<?")->execute([$maxSale, $maxSale]);

    /* 6) ترحيل بيانات المدفوعات القديمة إلى نموذج التوزيع */
    legacy_backfill_allocations($d, $log);

    $d->exec("UPDATE sales SET due=0 WHERE status='cancelled' AND due<>0");   // الملغاة لا متبقي عليها

    /* 7) تصحيح أرصدة العملاء حسب دفتر الحساب */
    foreach ($d->query('SELECT id,name,balance FROM customers')->fetchAll() as $c) {
        $calc = customer_balance_calc($d, (int)$c['id']);
        if (abs($calc - (float)$c['balance']) > 0.004) {
            $d->prepare('UPDATE customers SET balance=? WHERE id=?')->execute([$calc, $c['id']]);
            $d->prepare("INSERT INTO audit_logs(action,entity,entity_id,details) VALUES('balance_recalculated','customer',?,?)")->execute([$c['id'], json_encode(['customer' => $c['name'], 'old' => (float)$c['balance'], 'new' => $calc], JSON_UNESCAPED_UNICODE)]);
            $log[] = "صُحّح رصيد العميل {$c['name']}: {$c['balance']} ← $calc";
        }
    }
    $d->prepare("INSERT INTO settings(k,v) VALUES('schema_version',?) ON DUPLICATE KEY UPDATE v=VALUES(v)")->execute([2]);
    $d->prepare("INSERT INTO audit_logs(action,entity,details) VALUES('schema_upgraded','system',?)")->execute([json_encode(['version' => 2, 'steps' => count($log)], JSON_UNESCAPED_UNICODE)]);
    $log[] = 'اكتملت الترقية إلى الإصدار 2';
}

/** يملأ payment_allocations من بيانات النظام القديم (مرة واحدة، فقط إن كان الجدول فارغًا) */
function legacy_backfill_allocations(PDO $d, array &$log): void {
    if ((int)$d->query('SELECT COUNT(*) FROM payment_allocations')->fetchColumn() > 0) return;
    $d->exec("UPDATE payments SET type=IF(sale_id IS NULL,'customer',IF(amount<0,'reversal','sale'))");
    $ins = $d->prepare('INSERT INTO payment_allocations(payment_id,sale_id,amount) VALUES(?,?,?)');
    $n = 0;
    // دفعات مرتبطة بفاتورة مباشرة (بيع/عكس)
    foreach ($d->query('SELECT id,sale_id,amount FROM payments WHERE sale_id IS NOT NULL ORDER BY id')->fetchAll() as $p) { $ins->execute([$p['id'], $p['sale_id'], $p['amount']]); $n++; }
    // دفعات العملاء: إعادة تطبيق قاعدة "الأقدم أولًا" كما كان يعمل النظام القديم
    $cust = $d->query("SELECT id,customer_id,amount,created_at FROM payments WHERE sale_id IS NULL AND customer_id IS NOT NULL AND amount>0 ORDER BY id")->fetchAll();
    $paidOf = $d->prepare('SELECT COALESCE(SUM(amount),0) FROM payment_allocations WHERE sale_id=?');
    $sales = $d->prepare("SELECT id,total FROM sales WHERE customer_id=? AND created_at<=? AND (status='completed' OR cancelled_at>?) ORDER BY id");
    foreach ($cust as $p) {
        $left = (float)$p['amount'];
        $sales->execute([$p['customer_id'], $p['created_at'], $p['created_at']]);
        foreach ($sales->fetchAll() as $s) {
            if ($left <= 0.004) break;
            $paidOf->execute([$s['id']]); $room = (float)$s['total'] - (float)$paidOf->fetchColumn();
            if ($room <= 0.004) continue;
            $a = round(min($left, $room), 2); $ins->execute([$p['id'], $s['id'], $a]); $left -= $a; $n++;
        }
    }
    // مزامنة paid/due/pay_status وحقل refunded مع التوزيع
    $fix = 0;
    foreach ($d->query('SELECT id,total,paid,status FROM sales')->fetchAll() as $s) {
        $sum = (float)$d->query('SELECT COALESCE(SUM(amount),0) FROM payment_allocations WHERE sale_id=' . (int)$s['id'])->fetchColumn();
        if ($s['status'] === 'completed') {
            if (abs($sum - (float)$s['paid']) > 0.004) { $fix++; recalc_sale($d, (int)$s['id']); }
        } else {
            $ref = (float)$d->query('SELECT COALESCE(-SUM(amount),0) FROM payments WHERE sale_id=' . (int)$s['id'] . ' AND amount<0')->fetchColumn();
            $d->prepare('UPDATE sales SET refunded=? WHERE id=?')->execute([$ref, $s['id']]);
        }
    }
    $log[] = "رُحّلت $n حركة توزيع دفعات" . ($fix ? " (صُحّحت $fix فاتورة)" : '');
}

/* =====================================================================
   الإصدار 3: دفعات الموردين + رصيد المورد + حالة دفع الشراء
   ===================================================================== */
function migrate_v3(PDO $d, array &$log): void {
    mg_add_col($d, 'suppliers', 'balance', 'DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER notes', $log);
    mg_add_col($d, 'purchases', 'pay_status', "ENUM('paid','partial','credit') NOT NULL DEFAULT 'credit' AFTER paid", $log);
    mg_add_col($d, 'purchases', 'refunded', 'DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER pay_status', $log);
    mg_add_idx($d, 'purchases', 'idx_purch_status_date', 'status,created_at', $log);
    if (!mg_table($d, 'supplier_payments')) {
        $d->exec("CREATE TABLE supplier_payments(
            id INT AUTO_INCREMENT PRIMARY KEY, supplier_id INT NOT NULL, purchase_id INT NOT NULL, user_id INT NOT NULL, payment_method_id INT NOT NULL,
            type ENUM('initial','payment','reversal') NOT NULL DEFAULT 'payment', reversal_of INT NULL,
            amount DECIMAL(14,2) NOT NULL, payment_date DATE NOT NULL, reference VARCHAR(100) NULL, notes VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sp_supplier(supplier_id,payment_date), INDEX idx_sp_purchase(purchase_id), INDEX idx_sp_method(payment_method_id,payment_date),
            FOREIGN KEY(supplier_id) REFERENCES suppliers(id), FOREIGN KEY(purchase_id) REFERENCES purchases(id),
            FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(payment_method_id) REFERENCES payment_methods(id)) ENGINE=InnoDB");
        $log[] = 'أُنشئ جدول supplier_payments';
        // ترحيل المدفوعات القديمة (عمود purchases.paid) إلى سجلات دفعات حقيقية
        $m = (int)$d->query("SELECT COALESCE((SELECT id FROM payment_methods WHERE code='cash' LIMIT 1),(SELECT MIN(id) FROM payment_methods))")->fetchColumn();
        $ins = $d->prepare("INSERT INTO supplier_payments(supplier_id,purchase_id,user_id,payment_method_id,type,reversal_of,amount,payment_date,notes,created_at) VALUES(?,?,?,?,?,?,?,?,?,?)");
        $n = 0;
        foreach ($d->query('SELECT id,supplier_id,user_id,paid,status,created_at,cancelled_at FROM purchases WHERE paid>0')->fetchAll() as $p) {
            $ins->execute([$p['supplier_id'], $p['id'], $p['user_id'], $m, 'initial', null, $p['paid'], substr($p['created_at'], 0, 10), 'مرحَّلة من النظام السابق (طريقة الدفع غير مسجلة)', $p['created_at']]); $n++;
            if ($p['status'] === 'cancelled') {
                $ins->execute([$p['supplier_id'], $p['id'], $p['user_id'], $m, 'reversal', $d->lastInsertId(), -$p['paid'], substr($p['cancelled_at'] ?: $p['created_at'], 0, 10), 'عكس دفعة بسبب إلغاء الفاتورة', $p['cancelled_at'] ?: $p['created_at']]); $n++;
            }
        }
        if ($n) $log[] = "رُحّلت $n حركة دفع للموردين";
    }
    /* صلاحية جديدة واحدة فقط: تسجيل دفعات الموردين (بقية الصلاحيات موجودة وتُعاد استخدامها) */
    $d->prepare('INSERT IGNORE INTO permissions(code,label) VALUES(?,?)')->execute(['pay_supplier', 'تسجيل دفعات للموردين']);
    $admin = (int)$d->query("SELECT id FROM roles WHERE name='admin'")->fetchColumn();
    $d->prepare('INSERT IGNORE INTO role_permissions(role_id,permission_id) SELECT ?,id FROM permissions')->execute([$admin]);
    foreach ($d->query('SELECT id FROM purchases')->fetchAll(PDO::FETCH_COLUMN) as $pid) recalc_purchase($d, (int)$pid);
    foreach ($d->query('SELECT id,name,balance FROM suppliers')->fetchAll() as $s) {
        $new = recalc_supplier_balance($d, (int)$s['id']);
        if (abs($new - $s['balance']) > 0.004 && $new != 0) $log[] = "رصيد المورد {$s['name']}: $new";
    }
    $d->prepare("INSERT INTO settings(k,v) VALUES('schema_version',3) ON DUPLICATE KEY UPDATE v=3")->execute();
    $d->prepare("INSERT INTO audit_logs(action,entity,details) VALUES('schema_upgraded','system',?)")->execute([json_encode(['version' => 3, 'steps' => count($log)], JSON_UNESCAPED_UNICODE)]);
    $log[] = 'اكتملت الترقية إلى الإصدار 3 (دفعات الموردين)';
}
