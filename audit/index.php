<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/audit_format.php';
require __DIR__ . '/../includes/pdf.php';
need('view_audit');
$d = db();
$u = (int)($_GET['u'] ?? 0); $a = trim($_GET['a'] ?? ''); $f = $_GET['from'] ?? ''; $t = $_GET['to'] ?? ''; $qv = trim($_GET['q'] ?? ''); $pg = page_no(); $per = 25;
if (!is_date($f)) $f = ''; if (!is_date($t)) $t = '';
$w = ['1']; $p = [];
if ($u) { $w[] = 'l.user_id=?'; $p[] = $u; } if ($a !== '') { $w[] = 'l.action=?'; $p[] = $a; }
if ($f !== '') { $w[] = 'l.created_at>=?'; $p[] = $f; } if ($t !== '') { $w[] = 'l.created_at<?'; $p[] = day_after($t); }
if ($qv !== '') { $w[] = '(l.details LIKE ? OR u.full_name LIKE ? OR l.action LIKE ?)'; array_push($p, "%$qv%", "%$qv%", "%$qv%"); }
$W = implode(' AND ', $w); $from = 'audit_logs l LEFT JOIN users u ON u.id=l.user_id';
$total = (int)q("SELECT COUNT(*) FROM $from WHERE $W", $p)->fetchColumn();
$fmt = $_GET['fmt'] ?? '';
if ($fmt === 'pdf') {
    $rows = q("SELECT l.*,u.full_name FROM $from WHERE $W ORDER BY l.id DESC LIMIT 1500", $p)->fetchAll();
    foreach ($rows as &$r) { $x = audit_describe($r); $r['title'] = $x['title']; $r['summary'] = $x['summary']; } unset($r);
    $cols = [['label' => 'الوقت', 'key' => 'created_at', 'type' => 'datetime', 'w' => 1.1], ['label' => 'المستخدم', 'f' => fn($r) => $r['full_name'] ?: 'النظام', 'w' => 1], ['label' => 'العملية', 'key' => 'title', 'w' => 1.3], ['label' => 'التفاصيل', 'key' => 'summary', 'w' => 4]];
    audit('report_exported', 'report', null, ['report' => 'سجل العمليات', 'format' => 'PDF', 'rows' => count($rows)]);
    pdf_send(pdf_table('سجل العمليات', [['الفترة', ($f ? d($f) : 'البداية') . ' — ' . ($t ? d($t) : 'الآن')], ['عدد السجلات', (string)count($rows)]], [], $cols, $rows), 'audit-' . date('Ymd') . '.pdf');
}
$rows = q("SELECT l.*,u.full_name FROM $from WHERE $W ORDER BY l.id DESC LIMIT $per OFFSET " . (($pg - 1) * $per), $p)->fetchAll();
$acts = []; foreach ($d->query('SELECT DISTINCT action FROM audit_logs')->fetchAll(PDO::FETCH_COLUMN) as $x) $acts[$x] = audit_title_for($x) . ' (' . $x . ')'; asort($acts);
$acts = array_map(fn($v) => preg_replace('/ \(.*\)$/', '', $v), $acts);
head('سجل العمليات', 'view_audit');
page_header('سجل العمليات', 'من فعل ماذا ومتى — بصياغة واضحة', '<a class="btn btn-outline-ink" href="' . e(qs(['fmt' => 'pdf'], ['p'])) . '"><i class="bi bi-file-earmark-pdf"></i> تقرير PDF</a>');
?>
<form class="panel panel-pad mb-3"><div class="filters">
  <div class="grow"><label class="form-label">بحث</label><input name="q" class="form-control" placeholder="اسم، فاتورة، عميل..." value="<?= e($qv) ?>"></div>
  <div><label class="form-label">المستخدم</label><select name="u" class="form-select"><?= opts(users_list(), $u ?: null, 'الكل') ?></select></div>
  <div><label class="form-label">نوع العملية</label><select name="a" class="form-select"><?= opts($acts, $a, 'الكل') ?></select></div>
  <div><label class="form-label">من</label><input type="date" name="from" class="form-control" value="<?= e($f) ?>"></div>
  <div><label class="form-label">إلى</label><input type="date" name="to" class="form-control" value="<?= e($t) ?>"></div>
  <div class="d-flex gap-2"><button class="btn btn-ink flex-fill"><i class="bi bi-funnel"></i> تصفية</button><?= ($qv !== '' || $u || $a !== '' || $f || $t) ? '<a class="btn btn-soft" href="./">مسح</a>' : '' ?></div>
</div></form>
<div class="panel">
<?php if (!$rows) echo empty_state('لا توجد عمليات', 'لا نتائج ضمن هذه التصفية.', 'clock-history'); foreach ($rows as $i => $r): $x = audit_describe($r); $cid = 'au' . $r['id']; ?>
  <div class="audit-item"><span class="audit-ico tone-<?= $x['tone'] ?>"><i class="bi bi-<?= $x['icon'] ?>"></i></span>
    <div class="flex-grow-1 min-w-0">
      <div class="d-flex flex-wrap justify-content-between gap-1"><span class="audit-title"><?= e($x['title']) ?></span><span class="small text-muted"><?= dt($r['created_at']) ?></span></div>
      <div class="audit-sum"><i class="bi bi-person"></i> <?= e($r['full_name'] ?: 'النظام') ?><?= $x['summary'] ? ' — ' . e($x['summary']) : '' ?></div>
      <div class="collapse mt-2" id="<?= $cid ?>"><dl class="kv panel-pad bg-light rounded-3 mb-2" style="padding:.9rem 1rem">
        <dt>العملية</dt><dd><?= e($x['title']) ?></dd><dt>المستخدم</dt><dd><?= e($r['full_name'] ?: 'النظام') ?></dd><dt>التاريخ</dt><dd><?= d($r['created_at']) ?></dd><dt>الوقت</dt><dd><?= date('H:i:s', strtotime($r['created_at'])) ?></dd>
        <?php foreach ($x['lines'] as [$l, $v]) echo '<dt>' . e($l) . '</dt><dd>' . e($v) . '</dd>'; ?>
      </dl><?= $x['link'] ? '<a class="btn btn-sm btn-soft" href="' . e($x['link']) . '"><i class="bi bi-box-arrow-up-left"></i> فتح السجل المرتبط</a>' : '' ?></div>
    </div>
    <button class="btn btn-sm btn-outline-ink flex-none" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $cid ?>"><i class="bi bi-eye"></i> عرض</button>
  </div>
<?php endforeach; ?></div>
<div class="mt-3"><?= pager($total, $pg, $per) ?></div>
<?php foot();
