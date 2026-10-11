<?php
/**
 * JOUD — صفحة CRUD عامة (إضافة / تعديل / تعطيل / حذف / بحث / ترقيم).
 * الإعداد:
 *  table, title, noun (اسم المفرد), icon, view (صلاحية العرض), edit (صلاحية التعديل), delete (صلاحية الحذف)
 *  fields: [عمود => [التسمية, النوع, خيارات|SQL, مطلوب?, أقصى طول?]]  الأنواع: text number date textarea select checkbox ro money
 *  cols: أعمدة الجدول، search: أعمدة البحث، ins: قيم ثابتة عند الإضافة
 *  deps: [[SQL يعدّ السجلات المرتبطة (؟ = id), 'وصف المرتبط']] تمنع الحذف
 *  guard: fn($act,$row) => رسالة خطأ أو null  — قواعد إضافية (مثل منع تعطيل عميل عليه رصيد)
 *  actions: fn($row) => html أزرار إضافية
 */
function crud(array $c): void {
    need($c['view']);
    $t = $c['table']; $F = $c['fields']; $noun = $c['noun'] ?? 'سجل';
    $canEdit = can($c['edit']); $canDel = can($c['delete'] ?? 'delete_records');
    $hasActive = isset($F['is_active']);
    foreach ($F as $k => &$f) { if (isset($f[2]) && is_string($f[2])) $f[2] = db()->query($f[2])->fetchAll(PDO::FETCH_KEY_PAIR); }
    unset($f);
    $err = ''; $posted = null; $openId = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_post(); need($c['edit']);
        $act = $_POST['act'] ?? 'save'; $id = (int)($_POST['id'] ?? 0);
        $row = null;
        if ($id) { $s = q("SELECT * FROM `$t` WHERE id=?", [$id]); $row = $s->fetch(); if (!$row) { flash('danger', 'السجل غير موجود'); redirect(qs([], ['edit'])); } }
        $label = $row['name'] ?? ('#' . $id);
        try {
            if ($act === 'toggle' && $row && $hasActive) {
                if (isset($c['guard']) && ($m = $c['guard']('toggle', $row))) throw new DomainException($m);
                $new = $row['is_active'] ? 0 : 1;
                q("UPDATE `$t` SET is_active=? WHERE id=?", [$new, $id]);
                audit($t . ($new ? '_activated' : '_deactivated'), $t, $id, ['name' => $label]);
                flash('success', ($new ? 'تم تفعيل ' : 'تم تعطيل ') . $noun . ' «' . $label . '»');
                redirect(qs());
            } elseif ($act === 'delete' && $row) {
                need($c['delete'] ?? 'delete_records');
                foreach ($c['deps'] ?? [] as [$sql, $what]) { $n = (int)q($sql, [$id])->fetchColumn(); if ($n > 0) throw new DomainException("لا يمكن حذف «{$label}» لارتباطه بـ $n $what — يمكنك تعطيله بدل الحذف."); }
                if (isset($c['guard']) && ($m = $c['guard']('delete', $row))) throw new DomainException($m);
                try { q("DELETE FROM `$t` WHERE id=?", [$id]); }
                catch (PDOException $x) { throw new DomainException("لا يمكن حذف «{$label}» لارتباطه بسجلات أخرى — يمكنك تعطيله بدل الحذف."); }
                audit($t . '_deleted', $t, $id, ['name' => $label]);
                flash('success', 'تم حذف ' . $noun . ' «' . $label . '»');
                redirect(qs());
            } else {
                $v = []; $posted = $_POST;
                foreach ($F as $k => $f) {
                    if ($f[1] === 'ro' || $f[1] === 'money') continue;
                    $x = trim((string)($_POST[$k] ?? ''));
                    if ($f[1] === 'checkbox') $x = isset($_POST[$k]) ? 1 : 0;
                    else {
                        if (!empty($f[3]) && $x === '') throw new DomainException($f[0] . ' مطلوب');
                        if ($f[1] === 'number' && $x !== '' && (!is_numeric($x) || $x < 0)) throw new DomainException($f[0] . ' غير صالح');
                        if ($f[1] === 'select' && $x !== '' && !isset($f[2][$x])) throw new DomainException($f[0] . ' غير صالح');
                        if ($f[1] === 'date' && $x !== '' && !is_date($x)) throw new DomainException($f[0] . ' غير صالح');
                        if (in_array($f[1], ['text', 'textarea']) && mb_strlen($x) > ($f[4] ?? ($f[1] === 'text' ? 255 : 2000))) throw new DomainException($f[0] . ' أطول من المسموح');
                    }
                    $v[$k] = $x === '' ? null : $x;
                }
                if (isset($c['validate']) && ($m = $c['validate']($v, $id))) throw new DomainException($m);
                if ($id) {
                    if (isset($c['guard']) && ($m = $c['guard']('save', $row, $v))) throw new DomainException($m);
                    q("UPDATE `$t` SET " . implode(',', array_map(fn($k) => "`$k`=?", array_keys($v))) . ' WHERE id=?', [...array_values($v), $id]);
                    $chg = [];
                    foreach ($v as $k => $nv) { if ((string)$row[$k] !== (string)$nv) $chg[$F[$k][0]] = [$F[$k][1] === 'select' ? ($F[$k][2][$row[$k]] ?? $row[$k]) : $row[$k], $F[$k][1] === 'select' ? ($F[$k][2][$nv] ?? $nv) : $nv]; }
                    audit($t . '_updated', $t, $id, ['name' => $v['name'] ?? $label, 'changes' => $chg]);
                    flash('success', 'تم تحديث ' . $noun . ' بنجاح');
                } else {
                    $w = $v + ($c['ins'] ?? []);
                    q("INSERT INTO `$t`(" . implode(',', array_map(fn($k) => "`$k`", array_keys($w))) . ') VALUES(' . implode(',', array_fill(0, count($w), '?')) . ')', array_values($w));
                    audit($t . '_created', $t, db()->lastInsertId(), ['name' => $v['name'] ?? '']);
                    flash('success', 'تمت إضافة ' . $noun . ' بنجاح');
                }
                redirect(qs([], ['edit', 'p']));
            }
        } catch (DomainException $x) { $err = $x->getMessage(); if (!in_array($act, ['toggle', 'delete'])) $openId = $id; else { flash('danger', $err); redirect(qs()); } }
        catch (PDOException $x) { $err = 'قيمة مكررة أو غير صالحة (تأكد أن الاسم/الرمز غير مستخدم)'; $openId = $id; }
    }

    /* ---- القائمة ---- */
    $qv = trim($_GET['q'] ?? ''); $st = $_GET['st'] ?? ''; $pg = page_no(); $per = 25;
    $w = ['1']; $p = [];
    if ($qv !== '' && !empty($c['search'])) { $w[] = '(' . implode(' OR ', array_map(fn($k) => "`$k` LIKE ?", $c['search'])) . ')'; foreach ($c['search'] as $_) $p[] = "%$qv%"; }
    if ($hasActive && $st !== '') { $w[] = 'is_active=?'; $p[] = (int)$st; }
    $W = implode(' AND ', $w);
    $total = (int)q("SELECT COUNT(*) FROM `$t` WHERE $W", $p)->fetchColumn();
    $rows = q("SELECT * FROM `$t` WHERE $W ORDER BY " . ($c['order'] ?? 'id DESC') . ' LIMIT ' . $per . ' OFFSET ' . (($pg - 1) * $per), $p)->fetchAll();

    $edit = null;
    if (isset($_GET['edit']) && $canEdit) { $edit = q("SELECT * FROM `$t` WHERE id=?", [(int)$_GET['edit']])->fetch() ?: null; if ($edit) $openId = (int)$edit['id']; }
    if ($posted !== null && $openId) { $edit = ['id' => $openId] + $posted; }
    $openModal = $err !== '' || $edit !== null;

    head($c['title'], $c['view']);
    page_header($c['title'], num($total) . ' ' . $noun, $canEdit ? '<button class="btn btn-gold" data-open-modal="crudModal"><i class="bi bi-plus-lg"></i> إضافة ' . e($noun) . '</button>' : '');

    echo '<form class="panel panel-pad mb-3 no-print"><div class="filters"><div class="grow"><label class="form-label">بحث</label><input name="q" class="form-control" placeholder="ابحث..." value="' . e($qv) . '"></div>';
    if ($hasActive) echo '<div><label class="form-label">الحالة</label><select name="st" class="form-select">' . opts(['1' => 'فعّال', '0' => 'معطّل'], $st, 'الكل') . '</select></div>';
    echo '<div class="d-flex gap-2"><button class="btn btn-ink flex-fill"><i class="bi bi-search"></i> بحث</button>' . (($qv !== '' || $st !== '') ? '<a class="btn btn-soft" href="./">مسح</a>' : '') . '</div></div></form>';

    $cols = [];
    foreach ($c['cols'] as $k) {
        $f = $F[$k]; $type = $f[1];
        $cols[] = ['label' => $f[0], 'type' => 'html', 'class' => in_array($type, ['money']) ? 'text-end-num' : '', 'f' => function ($r) use ($k, $f, $type) {
            $x = $r[$k] ?? '';
            return match ($type) { 'checkbox' => active_badge($x), 'select' => e($f[2][$x] ?? '—'), 'money' => '<span class="amt">' . money($x) . '</span>', 'number' => num($x), 'date' => d($x), default => e($x) };
        }];
    }
    $cols[] = ['label' => '', 'type' => 'html', 'f' => function ($r) use ($c, $canEdit, $canDel, $hasActive, $noun) {
        $o = '<div class="row-actions">';
        if ($canEdit) $o .= '<a class="btn-act btn" title="تعديل" href="' . e(qs(['edit' => $r['id']])) . '"><i class="bi bi-pencil-square"></i></a>';
        if (isset($c['actions'])) $o .= $c['actions']($r);
        if ($canEdit && $hasActive) $o .= '<form method="post" class="d-inline" data-confirm="' . ($r['is_active'] ? 'تعطيل' : 'تفعيل') . ' ' . e($noun) . ' «' . e($r['name'] ?? '') . '»؟">' . csrf_field() . '<input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="' . $r['id'] . '"><button class="btn-act btn ' . ($r['is_active'] ? '' : 'ok') . '" title="' . ($r['is_active'] ? 'تعطيل' : 'تفعيل') . '"><i class="bi bi-' . ($r['is_active'] ? 'toggle-on' : 'toggle-off') . '"></i></button></form>';
        if ($canDel) $o .= '<form method="post" class="d-inline" data-confirm="حذف ' . e($noun) . ' «' . e($r['name'] ?? '#' . $r['id']) . '» نهائيًا؟ لا يمكن التراجع." data-confirm-title="تأكيد الحذف">' . csrf_field() . '<input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="' . $r['id'] . '"><button class="btn-act btn danger" title="حذف"><i class="bi bi-trash3"></i></button></form>';
        return $o . '</div>';
    }];
    echo '<div class="panel">' . render_table($cols, $rows, ['empty' => empty_state('لا توجد بيانات', $qv !== '' ? 'لا نتائج مطابقة للبحث.' : 'ابدأ بإضافة أول ' . $noun . '.', 'inbox', $canEdit ? '<button class="btn btn-gold mt-2" data-open-modal="crudModal"><i class="bi bi-plus-lg"></i> إضافة ' . e($noun) . '</button>' : '')]) . '</div><div class="mt-3">' . pager($total, $pg, $per) . '</div>';

    if ($canEdit) {
        $editing = isset($edit['id']) && (int)$edit['id'] > 0;
        modal_open('crudModal', ($editing ? 'تعديل ' : 'إضافة ') . $noun, $openModal);
        echo '<form method="post" action="' . e(qs([], ['edit'])) . '"><div class="modal-body">' . ($err ? '<div class="alert alert-danger">' . e($err) . '</div>' : '') . csrf_field() . '<input type="hidden" name="id" value="' . (int)($edit['id'] ?? 0) . '"><div class="row g-3">';
        foreach ($F as $k => $f) {
            if (in_array($f[1], ['ro', 'money'])) continue;
            $val = $edit[$k] ?? ($f[1] === 'checkbox' ? 1 : '');
            if ($f[1] === 'checkbox' && $posted !== null) $val = isset($posted[$k]) ? 1 : 0;
            echo '<div class="col-12' . (in_array($f[1], ['textarea']) ? '' : ' col-md-6') . '"><label class="form-label">' . e($f[0]) . (!empty($f[3]) ? ' <span class="text-danger">*</span>' : '') . '</label>';
            if ($f[1] === 'select') echo '<select name="' . $k . '" class="form-select"' . (!empty($f[3]) ? ' required' : '') . '>' . opts($f[2], $val, '— اختر —') . '</select>';
            elseif ($f[1] === 'checkbox') echo '<div class="form-check form-switch pt-1"><input class="form-check-input" type="checkbox" name="' . $k . '" id="f_' . $k . '"' . ($val ? ' checked' : '') . '><label class="form-check-label" for="f_' . $k . '">فعّال</label></div>';
            elseif ($f[1] === 'textarea') echo '<textarea name="' . $k . '" class="form-control" rows="3" maxlength="2000">' . e($val) . '</textarea>';
            else echo '<input name="' . $k . '" class="form-control" type="' . ($f[1] === 'number' ? 'number" step="0.01" min="0' : ($f[1] === 'date' ? 'date' : 'text')) . '" value="' . e($f[1] === 'date' && !$val ? today() : $val) . '"' . (!empty($f[3]) ? ' required' : '') . ' maxlength="' . ($f[4] ?? 255) . '">';
            echo '</div>';
        }
        echo '</div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-gold"><i class="bi bi-check2"></i> حفظ</button></div></form>';
        modal_close();
    }
    foot();
}
