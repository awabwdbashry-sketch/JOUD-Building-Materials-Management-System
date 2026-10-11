<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/accounting.php';
need('cancel_expense');
require_post();
try { cancel_expense(db(), ctx(), (int)($_POST['id'] ?? 0), $_POST['reason'] ?? ''); flash('success', 'تم إلغاء المصروف ولن يُحتسب في التقارير والإجماليات'); }
catch (DomainException $x) { flash('danger', $x->getMessage()); }
redirect(BASE . '/expenses/' . (isset($_POST['back_id']) ? '?id=' . (int)$_POST['back_id'] : ''));
