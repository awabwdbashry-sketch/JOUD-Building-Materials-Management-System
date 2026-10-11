<?php
/** واجهة نقطة البيع: GET = بحث المنتجات، POST = إنشاء فاتورة (كل المنطق في includes/accounting.php) */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/accounting.php';
need('create_sale');
header('Content-Type: application/json; charset=utf-8');
function out(array $d, int $c = 200): never { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $qv = trim($_GET['q'] ?? ''); $cat = (int)($_GET['cat'] ?? 0);
    $s = q("SELECT p.id,p.name,p.sku,p.base_price,p.min_price,p.stock,p.min_stock,u.name unit FROM products p JOIN units u ON u.id=p.unit_id WHERE p.status='active' AND (p.name LIKE ? OR p.sku LIKE ?) AND (?=0 OR p.category_id=?) ORDER BY (p.stock<=0),p.name LIMIT 80", ["%$qv%", "%$qv%", $cat, $cat]);
    out(['products' => $s->fetchAll()]);
}
if (!csrf_ok()) out(['error' => 'انتهت الجلسة، أعد تحميل الصفحة'], 419);
$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) out(['error' => 'بيانات غير صالحة'], 422);
try {
    $r = create_sale(db(), ctx(), $in);
    out($r + ['url' => BASE . '/sales/invoice.php?id=' . $r['id']]);
} catch (DomainException $x) { out(['error' => $x->getMessage()], 422); }
catch (Throwable $x) { error_log('POS error: ' . $x->getMessage()); out(['error' => 'تعذر حفظ الفاتورة بسبب خطأ في قاعدة البيانات'], 500); }
