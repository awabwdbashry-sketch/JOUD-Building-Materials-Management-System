<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/accounting.php';
need('view_purchases');
$d = db(); $err = ''; $openPay = false; $canCreate = can('create_purchase'); $canPay = can('pay_supplier'); $canCancel = can('cancel_purchase');

/** شارة حالة الدفع لفاتورة شراء */
function purchase_badge(array $p): string {
    if ($p['status'] === 'cancelled') return badge('ملغاة', 'danger');
    return match ($p['pay_status']) { 'paid' => badge('مدفوعة بالكامل', 'success'), 'partial' => badge('مدفوعة جزئيًا', 'warning'), default => badge('آجلة', 'info') };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    $act = $_POST['act'] ?? 'create'; $pid = (int)($_POST['id'] ?? 0);
    try {
        if ($act === 'cancel') {
            $r = cancel_purchase($d, ctx(), $pid, $_POST['reason'] ?? '');
            flash('success', 'تم إلغاء فاتورة الشراء وإرجاع المخزون.' . ($r['refunded'] > 0 ? ' عُكست دفعات بقيمة ' . cur($r['refunded']) . ' (استرداد من المورد).' : '') . ' رصيد المورد الآن: ' . cur($r['supplier_balance']));
            redirect('?id=' . $pid);
        } elseif ($act === 'pay') {
            $r = pay_purchase($d, ctx(), $pid, (int)($_POST['method_id'] ?? 0), $_POST['amount'] ?? '', $_POST['date'] ?? '', trim($_POST['reference'] ?? ''), trim($_POST['notes'] ?? ''));
            flash('success', 'تم تسجيل الدفعة. المتبقي على الفاتورة: ' . cur($r['remaining']) . ($r['status'] === 'paid' ? ' — الفاتورة أصبحت مدفوعة بالكامل ✓' : ''));
            redirect('?id=' . $pid);
        }
        $lines = [];
        foreach (($_POST['pid'] ?? []) as $i => $x) $lines[] = ['product_id' => $x, 'qty' => $_POST['qty'][$i] ?? 0, 'cost' => $_POST['cost'][$i] ?? ''];
        $r = create_purchase($d, ctx(), (int)($_POST['supplier_id'] ?? 0), trim($_POST['ref'] ?? ''), $lines, $_POST['paid'] ?? 0, (int)($_POST['method_id'] ?? 0), trim($_POST['pay_ref'] ?? ''));
        flash('success', 'تم حفظ فاتورة الشراء وزيادة المخزون. المتبقي للمورد: ' . cur($r['due'])); redirect('?id=' . $r['id']);
    } catch (DomainException $x) {
        $err = $x->getMessage();
        if ($act === 'cancel') { flash('danger', $err); redirect('?id=' . $pid); }
        if ($act === 'pay') { $openPay = true; $_GET['id'] = $pid; } else $_GET['new'] = 1;
    } catch (Throwable $x) {                      // خطأ غير متوقع: التراجع تم تلقائيًا (Transaction) — لا بيانات جزئية
        error_log('Purchases error: ' . $x->getMessage());
        $err = 'تعذر حفظ العملية بسبب خطأ في قاعدة البيانات. لم يُحفظ أي شيء، حاول مرة أخرى.';
        if ($act === 'cancel') { flash('danger', $err); redirect('?id=' . $pid); }
        if ($act === 'pay') { $openPay = true; $_GET['id'] = $pid; } else $_GET['new'] = 1;
    }
}

/* ---------- تفاصيل فاتورة شراء ---------- */
if (isset($_GET['id'])) {
    $p = q('SELECT p.*,s.name sn,s.phone sp,u.full_name FROM purchases p JOIN suppliers s ON s.id=p.supplier_id JOIN users u ON u.id=p.user_id WHERE p.id=?', [(int)$_GET['id']])->fetch();
    if (!$p) fail_page(404, 'فاتورة الشراء غير موجودة', 'تحقق من الرابط.');
    $items = q('SELECT i.*,pr.name,pr.sku FROM purchase_items i JOIN products pr ON pr.id=i.product_id WHERE purchase_id=? ORDER BY i.id', [$p['id']])->fetchAll();
    $pays = q('SELECT sp.*,pm.name,u.full_name FROM supplier_payments sp JOIN payment_methods pm ON pm.id=sp.payment_method_id JOIN users u ON u.id=sp.user_id WHERE sp.purchase_id=? ORDER BY sp.id', [$p['id']])->fetchAll();
    $cancelled = $p['status'] === 'cancelled'; $rem = $cancelled ? 0 : r2($p['total'] - $p['paid']); $no = purchase_no((int)$p['id']);
    $showPay = $canPay && !$cancelled && $rem > 0.004;
    if (isset($_GET['pay']) && $showPay) $openPay = true;
    head('فاتورة شراء ' . $no, 'view_purchases');
    $act = '<a class="btn btn-soft" href="./"><i class="bi bi-arrow-right"></i> رجوع</a><a class="btn btn-outline-ink" href="pdf.php?id=' . $p['id'] . '"><i class="bi bi-file-earmark-pdf"></i> PDF</a>'
        . ($showPay ? '<button class="btn btn-gold" data-open-modal="payModal"><i class="bi bi-cash-coin"></i> تسجيل دفعة جديدة</button>' : '') . '<button class="btn btn-outline-ink" onclick="print()"><i class="bi bi-printer"></i> طباعة</button>';
    page_header('فاتورة شراء ' . $no, $p['sn'] . ' — ' . dt($p['created_at']), $act);
    // الخطأ يظهر داخل نافذة الدفع إن كانت مرسومة، وإلا (فاتورة مسدّدة/ملغاة/بلا صلاحية) يظهر هنا
    if ($err !== '' && !$showPay) echo '<div class="alert alert-danger d-flex gap-2 align-items-center"><i class="bi bi-exclamation-octagon-fill"></i><div>' . e($err) . '</div></div>';
    $logo = logo_url(); ?>
<div class="paper">
  <div class="inv-head">
    <div class="co"><?= $logo ? '<img src="' . e($logo) . '" alt="">' : '<span class="mark">J</span>' ?><div><h2><?= e(company()) ?></h2><div class="small text-muted"><?= e(setting('phone')) ?><?= setting('phone') && setting('address') ? ' · ' : '' ?><?= e(setting('address')) ?></div></div></div>
    <div class="text-start"><div class="fw-bold" style="color:var(--gold);font-size:1.3rem">فاتورة شراء</div><div class="fw-bold"><?= e($no) ?></div><div><?= purchase_badge($p) ?></div></div>
  </div>
  <div class="meta-grid">
    <div><small>المورد</small><b><?= can('manage_suppliers') ? '<a href="' . BASE . '/suppliers/view.php?id=' . $p['supplier_id'] . '">' . e($p['sn']) . '</a>' : e($p['sn']) ?></b></div>
    <div><small>مرجع فاتورة المورد</small><b><?= e($p['ref'] ?: '—') ?></b></div>
    <div><small>التاريخ والوقت</small><b><?= dt($p['created_at']) ?></b></div>
    <div><small>سجّلها</small><b><?= e($p['full_name']) ?></b></div>
  </div>
  <div class="table-wrap"><table class="table table-sm align-middle"><thead><tr><th>#</th><th>الصنف</th><th class="text-end-num">الكمية</th><th class="text-end-num">تكلفة الوحدة</th><th class="text-end-num">الإجمالي</th></tr></thead><tbody>
  <?php foreach ($items as $i => $r) echo '<tr><td>' . ($i + 1) . '</td><td>' . e($r['name']) . ' <small class="text-muted">' . e($r['sku']) . '</small></td><td class="text-end-num">' . qty($r['qty']) . '</td><td class="text-end-num">' . money($r['cost']) . '</td><td class="text-end-num"><b>' . money($r['line_total']) . '</b></td></tr>'; ?>
  </tbody></table></div>
  <div class="sum">
    <div><span>المجموع الفرعي</span><span class="amt"><?= money($p['total']) ?></span></div>
    <div class="grand"><span>الإجمالي</span><span class="amt"><?= cur($p['total']) ?></span></div>
    <div class="text-success"><span>المدفوع</span><b class="amt"><?= cur($cancelled ? 0 : $p['paid']) ?></b></div>
    <div class="text-danger"><span>المتبقي للمورد</span><b class="amt"><?= cur($rem) ?></b></div>
    <div><span>حالة الدفع</span><span><?= purchase_badge($p) ?></span></div>
  </div>
  <?php if ($pays): ?><div class="mt-4"><h6 class="panel-title"><i class="bi bi-cash-stack"></i> سجل الدفعات</h6>
  <?= render_table([['label' => 'التاريخ', 'key' => 'payment_date', 'type' => 'date'], ['label' => 'النوع', 'f' => fn($r) => ['initial' => 'دفعة عند الشراء', 'payment' => 'دفعة لاحقة', 'reversal' => 'استرداد (عكس)'][$r['type']]], ['label' => 'طريقة الدفع', 'key' => 'name'], ['label' => 'المرجع', 'f' => fn($r) => $r['reference'] ?: '—'], ['label' => 'سجّلها', 'key' => 'full_name'], ['label' => 'المبلغ', 'type' => 'html', 'f' => fn($r) => '<b class="amt ' . ($r['amount'] < 0 ? 'text-danger' : '') . '">' . money($r['amount']) . '</b>']], $pays) ?></div><?php endif; ?>
  <?php if ($cancelled): ?><div class="alert alert-danger mt-3 mb-0"><span class="stamp">ملغاة</span> <?= e($p['cancel_reason']) ?> — <?= dt($p['cancelled_at']) ?><?= $p['cancelled_by'] ? ' بواسطة ' . e(user_name($d, (int)$p['cancelled_by'])) : '' ?><?= $p['refunded'] > 0 ? ' — عُكست دفعات بقيمة ' . cur($p['refunded']) : '' ?></div><?php endif; ?>
  <div class="foot-note"><?= e(setting('invoice_footer', 'شكرًا لتعاملكم معنا')) ?></div>
</div>
<?php if (!$cancelled && $canCancel): ?>
<form method="post" class="panel panel-pad mt-3 no-print d-flex flex-wrap gap-2 mx-auto" style="max-width:860px" data-confirm="سيتم إلغاء الفاتورة وخصم الكميات من المخزون وعكس الدفعات المسجلة (استرداد من المورد). متابعة؟" data-confirm-title="إلغاء فاتورة الشراء"><?= csrf_field() ?><input type="hidden" name="act" value="cancel"><input type="hidden" name="id" value="<?= $p['id'] ?>">
  <input name="reason" class="form-control flex-grow-1" style="min-width:220px;max-width:480px" placeholder="سبب الإلغاء (مطلوب)" required maxlength="250"><button class="btn btn-outline-danger"><i class="bi bi-x-octagon"></i> إلغاء الفاتورة</button></form>
<?php endif;
    if ($showPay) { modal_open('payModal', 'تسجيل دفعة جديدة — ' . $no, $openPay); ?>
<form method="post" action="?id=<?= $p['id'] ?>"><div class="modal-body"><?= $err ? '<div class="alert alert-danger">' . e($err) . '</div>' : '' ?><?= csrf_field() ?><input type="hidden" name="act" value="pay"><input type="hidden" name="id" value="<?= $p['id'] ?>">
  <div class="totals mb-3"><div class="row-t"><span>إجمالي الفاتورة</span><b><?= cur($p['total']) ?></b></div><div class="row-t"><span>المدفوع</span><b class="text-success"><?= cur($p['paid']) ?></b></div><div class="row-t grand"><span>المتبقي</span><b class="text-danger"><?= cur($rem) ?></b></div></div>
  <div class="row g-3"><div class="col-12 col-md-7"><label class="form-label">المبلغ <span class="text-danger">*</span></label><div class="input-group"><input type="number" step="0.01" min="0.01" max="<?= $rem ?>" name="amount" id="payAmt" class="form-control" required value="<?= e($_POST['amount'] ?? '') ?>"><button type="button" class="btn btn-soft" onclick="document.getElementById('payAmt').value='<?= $rem ?>'">سداد المتبقي</button></div></div>
  <div class="col-6 col-md-5"><label class="form-label">التاريخ</label><input type="date" name="date" class="form-control" required max="<?= today() ?>" min="<?= substr($p['created_at'], 0, 10) ?>" value="<?= e($_POST['date'] ?? today()) ?>"></div>
  <div class="col-6 col-md-5"><label class="form-label">طريقة الدفع <span class="text-danger">*</span></label><select name="method_id" class="form-select" required><?= opts(pm_list(), $_POST['method_id'] ?? null, '— اختر —') ?></select></div>
  <div class="col-12 col-md-7"><label class="form-label">رقم المرجع / الإيصال</label><input name="reference" class="form-control" maxlength="100" value="<?= e($_POST['reference'] ?? '') ?>"></div>
  <div class="col-12"><label class="form-label">ملاحظات</label><input name="notes" class="form-control" maxlength="250" value="<?= e($_POST['notes'] ?? '') ?>"></div></div></div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-gold"><i class="bi bi-check2-circle"></i> تسجيل الدفعة</button></div></form>
<?php modal_close(); }
    foot(); exit;
}

/* ---------- نموذج شراء جديد ---------- */
if (isset($_GET['new'])) {
    need('create_purchase');
    $sup = $d->query('SELECT id,name FROM suppliers WHERE is_active=1 ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);
    $pr = $d->query("SELECT p.id,CONCAT(p.name,' (',p.sku,')') n FROM products p WHERE p.status='active' ORDER BY p.name")->fetchAll(PDO::FETCH_KEY_PAIR);
    head('شراء جديد', 'create_purchase');
    page_header('فاتورة شراء جديدة', 'تُضاف الكميات للمخزون وتُحدَّث تكلفة المنتجات، والمتبقي يُسجَّل ديْنًا على المورد', '<a class="btn btn-soft" href="./"><i class="bi bi-arrow-right"></i> رجوع</a>');
    if (!$sup) echo '<div class="alert alert-warning">لا يوجد موردون فعّالون. <a href="' . BASE . '/suppliers/">أضف مورّدًا أولًا</a>.</div>';
    echo $err ? '<div class="alert alert-danger">' . e($err) . '</div>' : ''; ?>
<form method="post" class="panel panel-pad"><?= csrf_field() ?><input type="hidden" name="act" value="create">
  <div class="row g-3 mb-3"><div class="col-md-6"><label class="form-label">المورد <span class="text-danger">*</span></label><select name="supplier_id" class="form-select" required><?= opts($sup, $_POST['supplier_id'] ?? ($_GET['supplier'] ?? ''), '— اختر —') ?></select></div>
  <div class="col-md-6"><label class="form-label">مرجع فاتورة المورد</label><input name="ref" class="form-control" maxlength="60" value="<?= e($_POST['ref'] ?? '') ?>"></div></div>
  <h6 class="panel-title"><i class="bi bi-list-check"></i> البنود</h6><div id="rows"></div>
  <button type="button" class="btn btn-outline-ink my-2" onclick="addRow()"><i class="bi bi-plus-lg"></i> إضافة بند</button>
  <div class="totals mt-2"><div class="row-t grand"><span>إجمالي الشراء</span><span><b id="grand">0</b> <?= e(currency()) ?></span></div></div>
  <h6 class="panel-title mt-4"><i class="bi bi-cash-coin"></i> الدفع (اختياري — اتركه 0 للشراء الآجل)</h6>
  <div class="row g-3"><div class="col-md-4"><label class="form-label">المبلغ المدفوع الآن</label><input type="number" step="0.01" min="0" name="paid" id="paid" class="form-control" value="<?= e($_POST['paid'] ?? 0) ?>" oninput="calc()"></div>
  <div class="col-md-4"><label class="form-label">طريقة الدفع</label><select name="method_id" id="pm" class="form-select"><?= opts(pm_list(), $_POST['method_id'] ?? null, '— اختر —') ?></select></div>
  <div class="col-md-4"><label class="form-label">مرجع الدفعة</label><input name="pay_ref" class="form-control" maxlength="100" value="<?= e($_POST['pay_ref'] ?? '') ?>"></div></div>
  <div class="totals mt-3"><div class="row-t"><span>المتبقي على المورد</span><b class="text-danger" id="remain">0</b></div></div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-gold"><i class="bi bi-check2"></i> حفظ الشراء</button><a class="btn btn-light" href="./">إلغاء</a></div>
</form>
<script>
const OPT=<?= json_encode(opts($pr, null, 'اختر منتجًا'), JSON_UNESCAPED_UNICODE) ?>, f=n=>(+n).toLocaleString('en',{maximumFractionDigits:2});
function addRow(){const r=document.createElement('div');r.className='row g-2 mb-2 align-items-end line';
r.innerHTML='<div class="col-12 col-md-5"><label class="form-label small d-md-none">المنتج</label><select name="pid[]" class="form-select">'+OPT+'</select></div><div class="col-4 col-md-2"><label class="form-label small d-md-none">الكمية</label><input type="number" step="0.001" min="0" name="qty[]" class="form-control" placeholder="الكمية" oninput="calc()"></div><div class="col-4 col-md-2"><label class="form-label small d-md-none">التكلفة</label><input type="number" step="0.01" min="0" name="cost[]" class="form-control" placeholder="التكلفة" oninput="calc()"></div><div class="col-3 col-md-2 fw-bold text-center lt">0</div><div class="col-1"><button type="button" class="btn btn-act danger" aria-label="حذف" onclick="this.closest(\'.line\').remove();calc()"><i class="bi bi-x-lg"></i></button></div>';
document.getElementById('rows').appendChild(r)}
function calc(){let g=0;document.querySelectorAll('.line').forEach(l=>{const t=(+l.querySelector('[name="qty[]"]').value||0)*(+l.querySelector('[name="cost[]"]').value||0);l.querySelector('.lt').textContent=f(t);g+=t});document.getElementById('grand').textContent=f(g);document.getElementById('remain').textContent=f(Math.max(0,g-(+document.getElementById('paid').value||0)))}
addRow();
</script>
<?php foot(); exit; }

/* ---------- القائمة ---------- */
$qv = trim($_GET['q'] ?? ''); $sup = (int)($_GET['sup'] ?? 0); $f = $_GET['f'] ?? ''; $t = $_GET['t'] ?? ''; $st = $_GET['st'] ?? ''; $pg = page_no(); $per = 25;
$w = ['1']; $pr = [];
if ($qv !== '') { $w[] = '(p.ref LIKE ? OR s.name LIKE ? OR p.id=?)'; array_push($pr, "%$qv%", "%$qv%", (int)preg_replace('/\D/', '', $qv)); }
if ($sup) { $w[] = 'p.supplier_id=?'; $pr[] = $sup; }
if (is_date($f)) { $w[] = 'p.created_at>=?'; $pr[] = $f; } if (is_date($t)) { $w[] = 'p.created_at<?'; $pr[] = day_after($t); }
if ($st === 'cancelled') $w[] = "p.status='cancelled'"; elseif (in_array($st, ['paid', 'partial', 'credit'], true)) { $w[] = "p.status='active' AND p.pay_status=?"; $pr[] = $st; }
elseif ($st === 'due') $w[] = "p.status='active' AND p.pay_status<>'paid'";
$W = implode(' AND ', $w);
$sum = q("SELECT COUNT(*) c,COALESCE(SUM(IF(p.status='active',p.total,0)),0) tot,COALESCE(SUM(IF(p.status='active',p.paid,0)),0) paid FROM purchases p JOIN suppliers s ON s.id=p.supplier_id WHERE $W", $pr)->fetch();
$rows = q("SELECT p.*,s.name sn FROM purchases p JOIN suppliers s ON s.id=p.supplier_id WHERE $W ORDER BY p.id DESC LIMIT $per OFFSET " . (($pg - 1) * $per), $pr)->fetchAll();
$sups = $d->query('SELECT id,name FROM suppliers ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);
head('المشتريات', 'view_purchases');
page_header('المشتريات', num($sum['c']) . ' فاتورة شراء', ($canCreate ? '<a class="btn btn-gold" href="?new=1"><i class="bi bi-plus-lg"></i> شراء جديد</a>' : '') . (can('view_reports') ? '<a class="btn btn-outline-ink" href="' . e(BASE . '/reports/?r=purchases&f=' . $f . '&t=' . $t . '&fmt=pdf') . '"><i class="bi bi-file-earmark-pdf"></i> تقرير PDF</a>' : ''));
?>
<div class="row g-3 mb-3">
  <div class="col-12 col-md-4"><div class="kpi"><span class="kpi-ico"><i class="bi bi-truck"></i></span><div class="kpi-body"><div class="kpi-label">إجمالي المشتريات (غير الملغاة)</div><div class="kpi-value"><?= num($sum['tot']) ?><small><?= e(currency()) ?></small></div></div></div></div>
  <div class="col-6 col-md-4"><div class="kpi t-ok"><span class="kpi-ico"><i class="bi bi-check2-circle"></i></span><div class="kpi-body"><div class="kpi-label">المدفوع للموردين</div><div class="kpi-value"><?= num($sum['paid']) ?></div></div></div></div>
  <div class="col-6 col-md-4"><a class="kpi t-bad" href="<?= e(qs(['st' => 'due'], ['p'])) ?>"><span class="kpi-ico"><i class="bi bi-hourglass-split"></i></span><div class="kpi-body"><div class="kpi-label">المتبقي للموردين</div><div class="kpi-value"><?= num($sum['tot'] - $sum['paid']) ?></div></div></a></div>
</div>
<form class="panel panel-pad mb-3 no-print"><div class="filters">
  <div class="grow"><label class="form-label">بحث</label><input name="q" class="form-control" placeholder="رقم / مرجع / مورد" value="<?= e($qv) ?>"></div>
  <div><label class="form-label">المورد</label><select name="sup" class="form-select"><?= opts($sups, $sup, 'الكل') ?></select></div>
  <div><label class="form-label">من</label><input type="date" name="f" class="form-control" value="<?= e($f) ?>"></div><div><label class="form-label">إلى</label><input type="date" name="t" class="form-control" value="<?= e($t) ?>"></div>
  <div><label class="form-label">الحالة</label><select name="st" class="form-select"><?= opts(['paid' => 'مدفوعة بالكامل', 'partial' => 'مدفوعة جزئيًا', 'credit' => 'آجلة', 'due' => 'غير مسددة بالكامل', 'cancelled' => 'ملغاة'], $st, 'الكل') ?></select></div>
  <div class="d-flex gap-2"><button class="btn btn-ink flex-fill"><i class="bi bi-funnel"></i> تصفية</button><?= array_filter([$qv, $sup, $f, $t, $st]) ? '<a class="btn btn-soft" href="./">مسح</a>' : '' ?></div>
</div></form>
<?php
$cols = [
    ['label' => 'رقم الفاتورة', 'type' => 'html', 'f' => fn($r) => '<a href="?id=' . $r['id'] . '"><b>' . e(purchase_no((int)$r['id'])) . '</b></a>'],
    ['label' => 'التاريخ', 'key' => 'created_at', 'type' => 'datetime'], ['label' => 'المورد', 'key' => 'sn'], ['label' => 'المرجع', 'f' => fn($r) => $r['ref'] ?: '—'],
    ['label' => 'الإجمالي', 'key' => 'total', 'type' => 'money'], ['label' => 'المدفوع', 'f' => fn($r) => $r['status'] === 'cancelled' ? 0 : $r['paid'], 'type' => 'money'], ['label' => 'المتبقي', 'f' => fn($r) => $r['status'] === 'cancelled' ? 0 : $r['total'] - $r['paid'], 'type' => 'money'],
    ['label' => 'الحالة', 'type' => 'html', 'f' => 'purchase_badge'],
    ['label' => '', 'type' => 'html', 'f' => fn($r) => '<div class="row-actions"><a class="btn-act btn" title="عرض" href="?id=' . $r['id'] . '"><i class="bi bi-eye"></i></a><a class="btn-act btn" title="PDF" href="pdf.php?id=' . $r['id'] . '"><i class="bi bi-file-earmark-pdf"></i></a>' . ($GLOBALS['canPay'] && $r['status'] === 'active' && $r['pay_status'] !== 'paid' ? '<a class="btn-act btn ok" title="تسجيل دفعة جديدة" href="?id=' . $r['id'] . '&pay=1"><i class="bi bi-cash-coin"></i></a>' : '') . '</div>'],
];
echo '<div class="panel">' . render_table($cols, $rows, ['row_class' => fn($r) => $r['status'] === 'cancelled' ? 'row-cancelled' : '', 'empty' => empty_state('لا توجد مشتريات', 'لا فواتير شراء ضمن هذه التصفية.', 'truck', $canCreate ? '<a class="btn btn-gold mt-2" href="?new=1"><i class="bi bi-plus-lg"></i> شراء جديد</a>' : '')]) . '</div><div class="mt-3">' . pager((int)$sum['c'], $pg, $per) . '</div>';
foot();
