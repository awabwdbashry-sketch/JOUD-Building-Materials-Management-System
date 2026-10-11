<?php
require __DIR__ . '/../includes/bootstrap.php';
need('create_sale');
$cats = db()->query('SELECT id,name FROM categories WHERE is_active=1 ORDER BY name')->fetchAll();
$custs = db()->query('SELECT id,name,phone,balance FROM customers WHERE is_active=1 ORDER BY name')->fetchAll();
$methods = pm_list();
head('نقطة البيع', 'create_sale');
?>
<div class="pos-grid">
  <section>
    <div class="panel panel-pad mb-3">
      <div class="input-group input-group-lg mb-2"><span class="input-group-text"><i class="bi bi-search"></i></span><input id="q" class="form-control" placeholder="ابحث بالاسم أو SKU..." autocomplete="off" autofocus></div>
      <div class="chips"><button class="chip on" data-c="0">الكل</button><?php foreach ($cats as $c) echo '<button class="chip" data-c="' . $c['id'] . '">' . e($c['name']) . '</button>'; ?></div>
    </div>
    <div id="grid" class="pos-products"></div>
  </section>

  <aside class="pos-cart" id="cartPanel"><div class="panel panel-pad">
    <h6 class="panel-title"><i class="bi bi-cart3"></i> الفاتورة <span class="badge bg-secondary-subtle text-secondary-emphasis me-auto" id="cnt">0</span></h6>
    <div id="cart"></div>
    <div class="totals my-3"><div class="row-t"><span>المجموع</span><b id="sub">0</b></div><div class="row-t text-success"><span>الخصم</span><b id="disc">0</b></div><div class="row-t grand"><span>الإجمالي</span><span><span id="total">0</span> <small class="fs-6"><?= e(currency()) ?></small></span></div></div>

    <label class="form-label">العميل</label>
    <select id="cust" class="form-select mb-2"><option value="">— بدون عميل (نقدي) —</option><?php foreach ($custs as $c) echo '<option value="' . $c['id'] . '">' . e($c['name'] . ($c['phone'] ? ' — ' . $c['phone'] : '') . ($c['balance'] > 0 ? ' (عليه ' . num($c['balance']) . ')' : '')) . '</option>'; ?></select>
    <?php if (can('manage_customers')): ?><a class="small d-block mb-2" href="<?= BASE ?>/customers/" target="_blank"><i class="bi bi-person-plus"></i> إضافة عميل جديد</a><?php endif; ?>

    <label class="form-label">طريقة الدفع</label>
    <div class="pay-seg mb-2">
      <input type="radio" name="pmode" id="m_full" value="full" checked><label for="m_full">دفع كامل</label>
      <input type="radio" name="pmode" id="m_part" value="part"><label for="m_part">دفع جزئي</label>
      <input type="radio" name="pmode" id="m_credit" value="credit" <?= can('credit_sale') ? '' : 'disabled' ?>><label for="m_credit" <?= can('credit_sale') ? '' : 'title="لا تملك صلاحية البيع الآجل" style="opacity:.45"' ?>>آجل</label>
    </div>
    <div id="methodBox"><select id="pm" class="form-select mb-2"><?php foreach ($methods as $k => $v) echo '<option value="' . $k . '">' . e($v) . '</option>'; ?></select></div>
    <div id="paidBox" class="d-none"><div class="input-group mb-2"><span class="input-group-text">المدفوع</span><input id="paid" type="number" min="0" step="0.01" class="form-control" placeholder="0"></div></div>
    <div class="totals mb-2"><div class="row-t"><span>المدفوع</span><b id="paidShow">0</b></div><div class="row-t"><span>المتبقي على العميل</span><b id="rem" class="text-danger">0</b></div></div>
    <div id="whyBox" class="d-none mb-2"><label class="form-label text-danger">سبب البيع تحت الحد الأدنى <span>*</span></label><input id="why" class="form-control" maxlength="250" placeholder="مطلوب لتسجيل التجاوز"></div>
    <div id="err" class="alert alert-danger py-2 d-none"></div>
    <button id="pay" class="btn btn-gold btn-lg w-100"><i class="bi bi-check2-circle"></i> إتمام البيع</button>
    <button id="clear" class="btn btn-link text-muted w-100 mt-1" type="button">تفريغ الفاتورة</button>
  </div></aside>
</div>
<div class="cart-fab no-print"><button class="btn btn-ink w-100 justify-content-between py-2" id="fab"><span><i class="bi bi-cart3"></i> الفاتورة (<b id="fabN">0</b>)</span><span><b id="fabT">0</b> <?= e(currency()) ?></span></button></div>
<?php
$js = '<script>window.POS=' . json_encode(['csrf' => csrf(), 'canOvr' => can('override_min_price'), 'cur' => currency(), 'api' => BASE . '/pos/api.php'], JSON_UNESCAPED_UNICODE) . ';</script><script src="' . BASE . '/assets/js/pos.js"></script>';
foot($js);
