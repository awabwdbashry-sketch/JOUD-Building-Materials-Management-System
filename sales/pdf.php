<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/invoice.php';
require __DIR__ . '/../includes/pdf.php';
[$sale, $items, $pays] = invoice_load((int)($_GET['id'] ?? 0));
$thermal = ($_GET['t'] ?? '') === '1';
audit('invoice_pdf', 'sale', $sale['id'], ['invoice' => $sale['invoice_no'], 'format' => $thermal ? '80mm' : 'A4']);
pdf_send(pdf_invoice($sale, array_values($items), array_values(array_filter($pays, fn($p) => $p['amount'] > 0)), $thermal), $sale['invoice_no'] . ($thermal ? '-80mm' : '') . '.pdf', isset($_GET['view']));
