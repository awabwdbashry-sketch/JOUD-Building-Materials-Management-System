<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/accounting.php';
need('receive_customer_payment');
$d = db(); $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    try {
        $cid = (int)($_POST['customer_id'] ?? 0);
        $r = receive_customer_payment($d, ctx(), $cid, (int)($_POST['method_id'] ?? 0), $_POST['amount'] ?? 0, $_POST['date'] ?? '', trim($_POST['reference'] ?? ''), trim($_POST['notes'] ?? ''));
        flash('success', 'تم تسجيل الدفعة. الرصيد السابق ' . cur($r['old_balance']) . ' ← الرصيد الجديد ' . cur($r['new_balance']));
        redirect(BASE . '/customers/statement.php?id=' . $cid);
    } catch (DomainException $x) { $err = $x->getMessage(); }
}
$cs = $d->query('SELECT id,name,phone,balance FROM customers WHERE is_active=1 AND balance>0 ORDER BY name')->fetchAll();
$ms = pm_list();
$sel = (int)($_POST['customer_id'] ?? $_GET['customer'] ?? 0);
head('دفعة عميل', 'receive_customer_payment');
page_header('تسجيل دفعة عميل', 'تُوزَّع الدفعة تلقائيًا على أقدم الفواتير المستحقة');
if (!$cs) { echo '<div class="panel">' . empty_state('لا توجد ديون', 'لا يوجد عملاء عليهم رصيد مستحق حاليًا.', 'emoji-smile', '<a class="btn btn-gold mt-2" href="' . BASE . '/pos/">بيع جديد</a>') . '</div>'; foot(); exit; }
?>
<form method="post" class="panel panel-pad mx-auto" style="max-width:680px"><?= csrf_field() ?>
  <?= $err ? '<div class="alert alert-danger">' . e($err) . '</div>' : '' ?>
  <div class="row g-3">
    <div class="col-12"><label class="form-label">العميل <span class="text-danger">*</span></label>
      <select name="customer_id" id="cust" class="form-select" required><option value="">— اختر العميل —</option><?php foreach ($cs as $c) echo '<option value="' . $c['id'] . '" data-bal="' . $c['balance'] . '"' . ($sel === (int)$c['id'] ? ' selected' : '') . '>' . e($c['name'] . ($c['phone'] ? ' — ' . $c['phone'] : '')) . '</option>'; ?></select></div>
    <div class="col-12"><div class="totals d-flex justify-content-between"><span>الرصيد المستحق</span><b class="text-danger" id="bal">—</b></div></div>
    <div class="col-12 col-md-6"><label class="form-label">المبلغ <span class="text-danger">*</span></label>
      <div class="input-group"><input type="number" step="0.01" min="0.01" name="amount" id="amt" class="form-control" required value="<?= e($_POST['amount'] ?? '') ?>"><button class="btn btn-soft" type="button" id="all">سداد كامل</button></div></div>
    <div class="col-6 col-md-3"><label class="form-label">التاريخ</label><input type="date" name="date" class="form-control" value="<?= e($_POST['date'] ?? today()) ?>" max="<?= today() ?>" required></div>
    <div class="col-6 col-md-3"><label class="form-label">طريقة الدفع</label><select name="method_id" class="form-select" required><?= opts($ms, $_POST['method_id'] ?? null) ?></select></div>
    <div class="col-12 col-md-6"><label class="form-label">رقم المرجع / الإيصال</label><input name="reference" class="form-control" maxlength="100" value="<?= e($_POST['reference'] ?? '') ?>"></div>
    <div class="col-12 col-md-6"><label class="form-label">ملاحظات</label><input name="notes" class="form-control" maxlength="250" value="<?= e($_POST['notes'] ?? '') ?>"></div>
    <div class="col-12"><div class="totals" id="after" style="display:none"><div class="row-t"><span>الرصيد بعد الدفعة</span><b id="newbal"></b></div></div></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-gold"><i class="bi bi-check2-circle"></i> تسجيل الدفعة</button><a class="btn btn-light" href="<?= BASE ?>/customers/">إلغاء</a></div>
  </div>
</form>
<script>
const sel=document.getElementById('cust'),amt=document.getElementById('amt'),f=n=>(+n).toLocaleString('en',{maximumFractionDigits:2});
function upd(){const o=sel.selectedOptions[0],b=o&&o.dataset.bal?+o.dataset.bal:0;document.getElementById('bal').textContent=b?f(b)+' <?= e(currency()) ?>':'—';amt.max=b||'';const a=+amt.value||0;document.getElementById('after').style.display=b&&a?'block':'none';document.getElementById('newbal').textContent=f(Math.max(0,b-a))+' <?= e(currency()) ?>'}
sel.onchange=upd;amt.oninput=upd;document.getElementById('all').onclick=()=>{const o=sel.selectedOptions[0];if(o&&o.dataset.bal){amt.value=o.dataset.bal;upd()}};upd();
</script>
<?php foot();
