<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/invoice.php';
[$sale, $items, $pays] = invoice_load((int)($_GET['id'] ?? 0));
$th = ($_GET['t'] ?? '') === '1';
$cancelled = $sale['status'] === 'cancelled';
$methods = implode('، ', array_unique(array_map(fn($x) => $x['name'], array_filter($pays, fn($p) => $p['amount'] > 0)))) ?: ($cancelled ? '—' : 'آجل');
$logo = logo_url();
head('فاتورة ' . $sale['invoice_no']);
$u = '?id=' . $sale['id'];
page_header('فاتورة ' . $sale['invoice_no'], dt($sale['created_at']),
    '<a class="btn btn-outline-ink" href="' . e($th ? $u : $u . '&t=1') . '"><i class="bi bi-' . ($th ? 'file-earmark-text' : 'receipt') . '"></i> ' . ($th ? 'عرض A4' : 'إيصال 80mm') . '</a>'
    . '<button class="btn btn-gold" onclick="print()"><i class="bi bi-printer"></i> طباعة ' . ($th ? '80mm' : 'A4') . '</button>'
    . '<a class="btn btn-outline-ink" href="pdf.php' . $u . ($th ? '&t=1' : '') . '"><i class="bi bi-file-earmark-pdf"></i> PDF</a>'
    . (can('create_sale') ? '<a class="btn btn-soft" href="' . BASE . '/pos/"><i class="bi bi-plus-lg"></i> بيع جديد</a>' : ''));
?>
<style>@media print{@page{<?= $th ? 'size:80mm auto;margin:3mm' : 'size:A4;margin:12mm' ?>}}</style>
<div class="paper<?= $th ? ' receipt' : '' ?>">
  <div class="inv-head">
    <div class="co"><?= $logo ? '<img src="' . e($logo) . '" alt="">' : '<span class="mark">J</span>' ?>
      <div><h2><?= e(company()) ?></h2><div class="small text-muted"><?= e(setting('phone')) ?><?= setting('phone') && setting('address') ? ' · ' : '' ?><?= e(setting('address')) ?></div><?php if (setting('tax_no')) echo '<div class="small text-muted">الرقم الضريبي: ' . e(setting('tax_no')) . '</div>'; ?></div></div>
    <div class="<?= $th ? 'mt-2' : 'text-start' ?>"><div class="fw-bold" style="color:var(--gold);font-size:<?= $th ? '1rem' : '1.3rem' ?>">فاتورة بيع</div><div class="fw-bold"><?= e($sale['invoice_no']) ?></div><div><?= sale_status_badge($sale) ?></div></div>
  </div>
  <div class="meta-grid">
    <div><small>التاريخ والوقت</small><b><?= dt($sale['created_at']) ?></b></div>
    <div><small>الكاشير</small><b><?= e($sale['full_name']) ?></b></div>
    <div><small>العميل</small><b><?= e($sale['cn'] ?: 'عميل نقدي') ?><?= $sale['cp'] ? ' — ' . e($sale['cp']) : '' ?></b></div>
    <div><small>طريقة الدفع</small><b><?= e($methods) ?></b></div>
  </div>
  <div class="table-wrap"><table class="table table-sm align-middle">
    <thead><tr><th>#</th><th>الصنف</th><th class="text-end-num">الكمية</th><th class="text-end-num">سعر الوحدة</th><?php if (!$th): ?><th class="text-end-num">الخصم</th><?php endif; ?><th class="text-end-num">الإجمالي</th></tr></thead><tbody>
    <?php foreach ($items as $i => $r): ?><tr><td><?= $i + 1 ?></td><td><?= e($r['name']) ?><?php if ($r['override_reason'] && can('view_all_sales')) echo '<div class="small text-warning"><i class="bi bi-exclamation-triangle"></i> سعر تحت الحد الأدنى: ' . e($r['override_reason']) . '</div>'; ?></td><td class="text-end-num"><?= qty($r['qty']) ?></td><td class="text-end-num"><?= money($r['unit_price']) ?></td><?php if (!$th): ?><td class="text-end-num"><?= $r['discount'] > 0 ? money($r['discount']) : '—' ?></td><?php endif; ?><td class="text-end-num"><b><?= money($r['line_total']) ?></b></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <div class="sum">
    <div><span>المجموع قبل الخصم</span><span class="amt"><?= money($sale['subtotal']) ?></span></div>
    <div><span>إجمالي الخصم</span><span class="amt"><?= money($sale['discount_total']) ?></span></div>
    <div class="grand"><span>الإجمالي النهائي</span><span class="amt"><?= cur($sale['total']) ?></span></div>
    <div class="text-success"><span>المدفوع</span><b class="amt"><?= cur($cancelled ? ($sale['refunded'] ?? 0) : $sale['paid']) ?></b></div>
    <div class="text-danger"><span>المتبقي</span><b class="amt"><?= cur($cancelled ? 0 : $sale['due']) ?></b></div>
  </div>
  <?php if ($pays): ?><div class="mt-3 small"><b>سجل الدفعات:</b><?php foreach ($pays as $r) echo '<div class="d-flex justify-content-between border-bottom py-1"><span>' . e($r['name']) . ' — ' . d($r['payment_date']) . ($r['type'] === 'customer' ? ' (دفعة عميل)' : ($r['type'] === 'reversal' ? ' (عكس)' : '')) . '</span><b class="amt ' . ($r['amount'] < 0 ? 'text-danger' : '') . '">' . money($r['amount']) . '</b></div>'; ?></div><?php endif; ?>
  <?php if ($cancelled): ?><div class="alert alert-danger mt-3 mb-0"><span class="stamp">ملغاة</span> <?= e($sale['cancel_reason']) ?> — <?= dt($sale['cancelled_at']) ?><?= $sale['cancelled_by'] ? ' بواسطة ' . e(user_name(db(), (int)$sale['cancelled_by'])) : '' ?></div><?php endif; ?>
  <div class="foot-note"><?= e(setting('invoice_footer', 'شكرًا لتعاملكم معنا')) ?></div>
</div>
<?php if (!$cancelled && can('cancel_sale')): ?>
<form method="post" action="cancel.php" class="panel panel-pad mt-3 no-print d-flex flex-wrap gap-2 mx-auto" style="max-width:860px" data-confirm="سيتم إلغاء الفاتورة وإرجاع الكميات للمخزون وعكس المدفوعات وتصحيح رصيد العميل. متابعة؟" data-confirm-title="إلغاء الفاتورة">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $sale['id'] ?>">
  <input name="reason" class="form-control flex-grow-1" style="min-width:220px;max-width:480px" placeholder="سبب الإلغاء (مطلوب)" required maxlength="250">
  <button class="btn btn-outline-danger"><i class="bi bi-x-octagon"></i> إلغاء الفاتورة</button>
</form>
<?php endif;
foot(!empty($_GET['print']) ? '<script>window.addEventListener("load",()=>setTimeout(print,400))</script>' : '');
