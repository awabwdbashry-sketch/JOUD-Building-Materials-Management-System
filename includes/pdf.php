<?php
/**
 * JOUD — توليد ملفات PDF حقيقية بمكتبة TCPDF (lib/tcpdf) مع دعم العربية RTL.
 * كل بيانات الشركة (الاسم، الهاتف، العنوان، العملة، التذييل) تُقرأ من الإعدادات.
 */
function pdf_boot(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $root = realpath(__DIR__ . '/../lib/tcpdf') . '/';
    foreach ([
        'K_TCPDF_EXTERNAL_CONFIG' => true, 'K_PATH_MAIN' => $root, 'K_PATH_URL' => '/', 'K_PATH_FONTS' => $root . 'fonts/',
        'K_PATH_CACHE' => rtrim(sys_get_temp_dir(), '/\\') . '/', 'K_PATH_IMAGES' => $root, 'K_BLANK_IMAGE' => '', 'PDF_PAGE_FORMAT' => 'A4',
        'PDF_PAGE_ORIENTATION' => 'P', 'PDF_CREATOR' => 'JOUD', 'PDF_AUTHOR' => 'JOUD', 'PDF_UNIT' => 'mm', 'K_CELL_HEIGHT_RATIO' => 1.25,
        'K_TITLE_MAGNIFICATION' => 1.3, 'K_SMALL_RATIO' => 2 / 3, 'K_THAI_TOPCHARS' => true, 'K_TCPDF_CALLS_IN_HTML' => false,
        'K_TCPDF_THROW_EXCEPTION_ERROR' => false, 'K_TIMEZONE' => 'Africa/Khartoum',
    ] as $k => $v) if (!defined($k)) define($k, $v);
    require_once $root . 'tcpdf.php';
}

function pdf_new($format, string $title, string $orientation = 'P'): TCPDF {
    pdf_boot();
    $p = new TCPDF($orientation, 'mm', $format, true, 'UTF-8', false);
    $p->SetCreator('JOUD'); $p->SetAuthor(company()); $p->SetTitle($title);
    $p->setPrintHeader(false); $p->setPrintFooter(false);
    $p->setRTL(true);
    $p->SetAutoPageBreak(true, 12);
    $p->SetFont('dejavusans', '', 9);
    return $p;
}
function pdf_logo_path(): ?string {
    $l = setting('logo'); $f = __DIR__ . '/../uploads/' . $l;
    return $l && is_file($f) && preg_match('/\.(png|jpe?g)$/i', $l) ? $f : null;
}
/** رأس الشركة: شعار + الاسم + الهاتف + العنوان */
function pdf_company_html(bool $compact = false): string {
    $name = e(company()); $meta = array_filter([setting('phone') ? 'هاتف: ' . e(setting('phone')) : '', setting('address') ? e(setting('address')) : '', setting('tax_no') ? 'الرقم الضريبي: ' . e(setting('tax_no')) : '']);
    $sz = $compact ? 11 : 16;
    return '<div style="font-size:' . $sz . 'pt;font-weight:bold;color:#0f1a2e">' . $name . '</div>' . ($meta ? '<div style="font-size:' . ($compact ? 7 : 8.5) . 'pt;color:#667085">' . implode(' &nbsp;|&nbsp; ', $meta) . '</div>' : '');
}
function pdf_footer_line(TCPDF $p, string $text): void {
    $p->SetFont('dejavusans', '', 7.5); $p->SetTextColor(120, 128, 145);
    $p->Cell(0, 5, $text, 0, 1, 'C'); $p->SetTextColor(0);
}
function pdf_send(TCPDF $p, string $name, bool $inline = false): never {
    while (ob_get_level()) ob_end_clean();
    $p->Output(preg_replace('/[^A-Za-z0-9_.-]/', '_', $name), $inline ? 'I' : 'D');
    exit;
}

/* ================= الفاتورة (A4 / 80mm) ================= */
function pdf_invoice(array $sale, array $items, array $pays, bool $thermal): TCPDF {
    $build = function (float $h) use ($sale, $items, $pays, $thermal): TCPDF {
        $cancelled = $sale['status'] === 'cancelled';
        $title = 'فاتورة ' . $sale['invoice_no'];
        $stTxt = $cancelled ? 'ملغاة' : ['paid' => 'مدفوعة', 'partial' => 'مدفوعة جزئيًا', 'credit' => 'آجلة'][$sale['pay_status']];
        $stClr = $cancelled ? '#c2413b' : ['paid' => '#1f8a5b', 'partial' => '#c98a14', 'credit' => '#2b6cb0'][$sale['pay_status']];
        $methods = implode('، ', array_unique(array_map(fn($x) => $x['name'], $pays))) ?: '—';
        if ($thermal) {
            $p = pdf_new([80, $h], $title); $p->SetMargins(4, 4, 4); $p->SetAutoPageBreak(false); $p->AddPage(); $fs = 7.5;
            $html = '<div style="text-align:center">' . pdf_company_html(true) . '</div><hr>'
                . '<table cellpadding="1.5" style="font-size:' . $fs . 'pt"><tr><td width="35%">رقم الفاتورة</td><td width="65%"><b>' . e($sale['invoice_no']) . '</b></td></tr><tr><td>التاريخ</td><td>' . dt($sale['created_at']) . '</td></tr><tr><td>الكاشير</td><td>' . e($sale['full_name']) . '</td></tr>'
                . ($sale['cn'] ? '<tr><td>العميل</td><td>' . e($sale['cn']) . '</td></tr>' : '') . '<tr><td>الدفع</td><td>' . e($methods) . '</td></tr><tr><td>الحالة</td><td style="color:' . $stClr . '"><b>' . $stTxt . '</b></td></tr></table><hr>'
                . '<table cellpadding="1.5" style="font-size:' . $fs . 'pt"><tr style="font-weight:bold"><td width="46%">الصنف</td><td width="16%" align="center">كمية</td><td width="18%" align="center">سعر</td><td width="20%" align="center">إجمالي</td></tr>';
            foreach ($items as $r) $html .= '<tr><td>' . e($r['name']) . '</td><td align="center">' . qty($r['qty']) . '</td><td align="center">' . money($r['unit_price']) . '</td><td align="center">' . money($r['line_total']) . '</td></tr>';
            $html .= '</table><hr><table cellpadding="1.5" style="font-size:' . ($fs + .5) . 'pt">'
                . ($sale['discount_total'] > 0 ? '<tr><td>الخصم</td><td align="left">' . money($sale['discount_total']) . '</td></tr>' : '')
                . '<tr style="font-size:10pt"><td><b>الإجمالي</b></td><td align="left"><b>' . money($sale['total']) . ' ' . e(currency()) . '</b></td></tr><tr><td>المدفوع</td><td align="left">' . money($sale['paid']) . '</td></tr><tr><td><b>المتبقي</b></td><td align="left"><b>' . money($cancelled ? 0 : $sale['due']) . '</b></td></tr></table>';
            if ($cancelled) $html .= '<div style="text-align:center;color:#c2413b"><b>*** فاتورة ملغاة ***</b></div>';
            $p->writeHTML($html, true, false, true, false, '');
            $p->SetFont('dejavusans', '', 7); $p->MultiCell(0, 4, setting('invoice_footer', 'شكرًا لتعاملكم معنا'), 0, 'C');
            return $p;
        }
        $p = pdf_new('A4', $title); $p->SetMargins(14, 14, 14); $p->AddPage();
        $logo = pdf_logo_path();
        $head = '<table cellpadding="0"><tr><td width="62%">' . pdf_company_html() . '</td><td width="38%" align="left"><div style="font-size:17pt;font-weight:bold;color:#b8893a">فاتورة بيع</div><div style="font-size:11pt"><b>' . e($sale['invoice_no']) . '</b></div><div style="font-size:9pt;color:' . $stClr . '"><b>' . $stTxt . '</b></div></td></tr></table>';
        if ($logo) $p->Image($logo, 14, 12, 18, 18, '', '', '', true, 300, 'R');
        $p->SetY(14); $p->writeHTML($head, true, false, true, false, '');
        $p->SetDrawColor(184, 137, 58); $p->SetLineWidth(0.7); $p->Line(14, $p->GetY() + 1, 196, $p->GetY() + 1); $p->Ln(4);
        $meta = '<table cellpadding="3" border="0" style="font-size:9pt;background-color:#f7f8fb"><tr><td width="25%"><span style="color:#667085">التاريخ والوقت</span><br><b>' . dt($sale['created_at']) . '</b></td><td width="25%"><span style="color:#667085">الكاشير</span><br><b>' . e($sale['full_name']) . '</b></td><td width="25%"><span style="color:#667085">العميل</span><br><b>' . e($sale['cn'] ?: 'عميل نقدي') . ($sale['cp'] ? ' — ' . e($sale['cp']) : '') . '</b></td><td width="25%"><span style="color:#667085">طريقة الدفع</span><br><b>' . e($methods) . '</b></td></tr></table><br>';
        $p->writeHTML($meta, true, false, true, false, '');
        $t = '<table cellpadding="5" border="0" style="font-size:9pt"><thead><tr style="background-color:#0f1a2e;color:#ffffff;font-weight:bold"><th width="6%" align="center">#</th><th width="34%">الصنف</th><th width="12%" align="center">الكمية</th><th width="16%" align="center">سعر الوحدة</th><th width="16%" align="center">الخصم</th><th width="16%" align="center">الإجمالي</th></tr></thead>';
        foreach ($items as $i => $r) $t .= '<tr style="background-color:' . ($i % 2 ? '#f7f8fb' : '#ffffff') . '"><td align="center">' . ($i + 1) . '</td><td>' . e($r['name']) . '</td><td align="center">' . qty($r['qty']) . '</td><td align="center">' . money($r['unit_price']) . '</td><td align="center">' . ($r['discount'] > 0 ? money($r['discount']) : '—') . '</td><td align="center"><b>' . money($r['line_total']) . '</b></td></tr>';
        $t .= '</table><br>';
        $p->writeHTML($t, true, false, true, false, '');
        $cur = e(currency());
        $sum = '<table cellpadding="4" style="font-size:9.5pt"><tr><td width="55%"></td><td width="45%"><table cellpadding="4">'
            . '<tr><td>المجموع قبل الخصم</td><td align="left">' . money($sale['subtotal']) . '</td></tr><tr><td>إجمالي الخصم</td><td align="left">' . money($sale['discount_total']) . '</td></tr>'
            . '<tr style="background-color:#0f1a2e;color:#ffffff;font-size:11pt"><td><b>الإجمالي النهائي</b></td><td align="left"><b>' . money($sale['total']) . ' ' . $cur . '</b></td></tr>'
            . '<tr><td>المدفوع</td><td align="left" style="color:#1f8a5b"><b>' . money($sale['paid']) . ' ' . $cur . '</b></td></tr><tr><td>المتبقي</td><td align="left" style="color:#c2413b"><b>' . money($cancelled ? 0 : $sale['due']) . ' ' . $cur . '</b></td></tr></table></td></tr></table>';
        $p->writeHTML($sum, true, false, true, false, '');
        if ($pays) { $x = '<div style="font-size:8.5pt;color:#667085">الدفعات: '; foreach ($pays as $r) $x .= e($r['name']) . ' ' . money($r['amount']) . ' (' . d($r['payment_date']) . ') &nbsp; '; $p->writeHTML($x . '</div>', true, false, true, false, ''); }
        if ($cancelled) $p->writeHTML('<div style="color:#c2413b;font-size:10pt"><b>فاتورة ملغاة</b> — ' . e($sale['cancel_reason']) . '</div>', true, false, true, false, '');
        $p->SetY(-24); $p->SetDrawColor(220, 224, 232); $p->SetLineWidth(0.2); $p->Line(14, $p->GetY(), 196, $p->GetY()); $p->Ln(2);
        pdf_footer_line($p, setting('invoice_footer', 'شكرًا لتعاملكم معنا'));
        return $p;
    };
    if (!$thermal) return $build(297);
    // 80mm: نقيس الارتفاع المطلوب ثم نعيد البناء بارتفاع مناسب (إيصال بطول المحتوى)
    $probe = $build(600); $h = $probe->GetY() + 14;
    return $build(max(90, $h));
}

/* ================= جدول عام (تقارير / كشف حساب / سجل) ================= */
/**
 * $meta: أسطر وصفية [[label,value],...]  |  $summary: [[label,value],...]  |  $cols: نفس تعريف render_table
 * يعيد كائن TCPDF جاهزًا للإرسال.
 */
function pdf_table(string $title, array $meta, array $summary, array $cols, array $rows, array $foot = []): TCPDF {
    $land = count($cols) > 6;
    $p = pdf_new('A4', $title, $land ? 'L' : 'P'); $p->SetMargins(10, 12, 10); $p->AddPage();
    $logo = pdf_logo_path(); $W = $land ? 277 : 190;
    if ($logo) $p->Image($logo, 10, 10, 14, 14, '', '', '', true, 300, 'R');
    $p->SetY(11);
    $p->writeHTML('<table cellpadding="0"><tr><td width="64%">' . pdf_company_html(true) . '</td><td width="36%" align="left"><div style="font-size:13pt;font-weight:bold;color:#b8893a">' . e($title) . '</div><div style="font-size:7.5pt;color:#667085">طُبع: ' . dt(date('Y-m-d H:i')) . '</div></td></tr></table>', true, false, true, false, '');
    $p->SetDrawColor(184, 137, 58); $p->SetLineWidth(0.6); $p->Line(10, $p->GetY() + 1, 10 + $W, $p->GetY() + 1); $p->Ln(3);
    if ($meta) { $m = '<table cellpadding="2" style="font-size:8pt"><tr>'; foreach ($meta as [$l, $v]) $m .= '<td><span style="color:#667085">' . e($l) . ':</span> <b>' . e($v) . '</b></td>'; $p->writeHTML($m . '</tr></table>', true, false, true, false, ''); }
    if ($summary) { $s = '<table cellpadding="4" style="font-size:8.5pt;background-color:#f7f8fb"><tr>'; foreach ($summary as [$l, $v]) $s .= '<td align="center"><span style="color:#667085;font-size:7.5pt">' . e($l) . '</span><br><b>' . e($v) . '</b></td>'; $p->writeHTML($s . '</tr></table><br>', true, false, true, false, ''); }
    $tw = array_sum(array_map(fn($c) => $c['w'] ?? 1, $cols));
    $fs = count($cols) > 8 ? 7 : 8;
    $t = '<table cellpadding="3.5" style="font-size:' . $fs . 'pt"><thead><tr style="background-color:#0f1a2e;color:#ffffff;font-weight:bold">';
    foreach ($cols as $c) $t .= '<th width="' . round(100 * ($c['w'] ?? 1) / $tw, 2) . '%" align="' . (in_array($c['type'] ?? '', ['money', 'qty', 'num']) ? 'center' : 'right') . '">' . e($c['label']) . '</th>';
    $t .= '</tr></thead>';
    foreach ($rows as $i => $r) {
        $t .= '<tr style="background-color:' . ($i % 2 ? '#f7f8fb' : '#ffffff') . '">';
        foreach ($cols as $c) $t .= '<td align="' . (in_array($c['type'] ?? '', ['money', 'qty', 'num']) ? 'center' : 'right') . '">' . e(cell_text($c, $r)) . '</td>';
        $t .= '</tr>';
    }
    if (!$rows) $t .= '<tr><td colspan="' . count($cols) . '" align="center">لا توجد بيانات</td></tr>';
    if ($foot) { $t .= '<tr style="background-color:#f5ecd8;font-weight:bold">'; foreach ($cols as $c) $t .= '<td align="center">' . e($foot[$c['key'] ?? ''] ?? '') . '</td>'; $t .= '</tr>'; }
    $p->writeHTML($t . '</table>', true, false, true, false, '');
    return $p;
}

/* ================= فاتورة شراء (A4) ================= */
function pdf_purchase(array $p, array $items, array $pays): TCPDF {
    $cancelled = $p['status'] === 'cancelled';
    $clr = $cancelled ? '#c2413b' : ['paid' => '#1f8a5b', 'partial' => '#c98a14', 'credit' => '#2b6cb0'][$p['pay_status']];
    $rem = $cancelled ? 0 : r2($p['total'] - $p['paid']);
    $pdf = pdf_new('A4', 'فاتورة شراء ' . purchase_no((int)$p['id'])); $pdf->SetMargins(14, 14, 14); $pdf->AddPage();
    if ($logo = pdf_logo_path()) $pdf->Image($logo, 14, 12, 18, 18, '', '', '', true, 300, 'R');
    $pdf->SetY(14);
    $pdf->writeHTML('<table cellpadding="0"><tr><td width="62%">' . pdf_company_html() . '</td><td width="38%" align="left"><div style="font-size:17pt;font-weight:bold;color:#b8893a">فاتورة شراء</div><div style="font-size:11pt"><b>' . e(purchase_no((int)$p['id'])) . '</b></div><div style="font-size:9pt;color:' . $clr . '"><b>' . e(purchase_pay_label($p)) . '</b></div></td></tr></table>', true, false, true, false, '');
    $pdf->SetDrawColor(184, 137, 58); $pdf->SetLineWidth(0.7); $pdf->Line(14, $pdf->GetY() + 1, 196, $pdf->GetY() + 1); $pdf->Ln(4);
    $pdf->writeHTML('<table cellpadding="3" style="font-size:9pt;background-color:#f7f8fb"><tr><td width="25%"><span style="color:#667085">المورد</span><br><b>' . e($p['sn']) . '</b>' . ($p['sp'] ? '<br>' . e($p['sp']) : '') . '</td><td width="25%"><span style="color:#667085">التاريخ والوقت</span><br><b>' . dt($p['created_at']) . '</b></td><td width="25%"><span style="color:#667085">مرجع فاتورة المورد</span><br><b>' . e($p['ref'] ?: '—') . '</b></td><td width="25%"><span style="color:#667085">سجّلها</span><br><b>' . e($p['full_name']) . '</b></td></tr></table><br>', true, false, true, false, '');
    $t = '<table cellpadding="5" style="font-size:9pt"><thead><tr style="background-color:#0f1a2e;color:#ffffff;font-weight:bold"><th width="6%" align="center">#</th><th width="44%">الصنف</th><th width="14%" align="center">الكمية</th><th width="18%" align="center">تكلفة الوحدة</th><th width="18%" align="center">الإجمالي</th></tr></thead>';
    foreach ($items as $i => $r) $t .= '<tr style="background-color:' . ($i % 2 ? '#f7f8fb' : '#ffffff') . '"><td align="center">' . ($i + 1) . '</td><td>' . e($r['name']) . ' &nbsp;<span style="color:#667085;font-size:7.5pt">' . "\u{202A}" . e($r['sku']) . "\u{202C}" . '</span></td><td align="center">' . qty($r['qty']) . '</td><td align="center">' . money($r['cost']) . '</td><td align="center"><b>' . money($r['line_total']) . '</b></td></tr>';
    $pdf->writeHTML($t . '</table><br>', true, false, true, false, '');
    $cur = e(currency());
    $pdf->writeHTML('<table cellpadding="4" style="font-size:9.5pt"><tr><td width="55%"></td><td width="45%"><table cellpadding="4"><tr><td>المجموع الفرعي</td><td align="left">' . money($p['total']) . '</td></tr><tr style="background-color:#0f1a2e;color:#ffffff;font-size:11pt"><td><b>الإجمالي</b></td><td align="left"><b>' . money($p['total']) . ' ' . $cur . '</b></td></tr><tr><td>المدفوع</td><td align="left" style="color:#1f8a5b"><b>' . money($cancelled ? 0 : $p['paid']) . ' ' . $cur . '</b></td></tr><tr><td>المتبقي للمورد</td><td align="left" style="color:#c2413b"><b>' . money($rem) . ' ' . $cur . '</b></td></tr></table></td></tr></table>', true, false, true, false, '');
    if ($pays) {
        $x = '<br><div style="font-size:9.5pt"><b>سجل الدفعات</b></div><table cellpadding="3" style="font-size:8.5pt"><thead><tr style="background-color:#f5ecd8;font-weight:bold"><th width="16%">التاريخ</th><th width="22%">النوع</th><th width="20%">طريقة الدفع</th><th width="22%">المرجع</th><th width="20%" align="center">المبلغ</th></tr></thead>';
        foreach ($pays as $r) $x .= '<tr><td>' . d($r['payment_date']) . '</td><td>' . e(['initial' => 'دفعة عند الشراء', 'payment' => 'دفعة لاحقة', 'reversal' => 'استرداد (عكس)'][$r['type']]) . '</td><td>' . e($r['name']) . '</td><td>' . e($r['reference'] ?: '—') . '</td><td align="center">' . money($r['amount']) . '</td></tr>';
        $pdf->writeHTML($x . '</table>', true, false, true, false, '');
    }
    if ($cancelled) $pdf->writeHTML('<br><div style="color:#c2413b;font-size:10pt"><b>فاتورة ملغاة</b> — ' . e($p['cancel_reason']) . ' (' . dt($p['cancelled_at']) . ')</div>', true, false, true, false, '');
    $pdf->SetY(-24); $pdf->SetDrawColor(220, 224, 232); $pdf->SetLineWidth(0.2); $pdf->Line(14, $pdf->GetY(), 196, $pdf->GetY()); $pdf->Ln(2);
    pdf_footer_line($pdf, setting('invoice_footer', 'شكرًا لتعاملكم معنا'));
    return $pdf;
}
