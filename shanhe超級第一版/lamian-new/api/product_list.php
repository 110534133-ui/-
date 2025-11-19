<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';

function table_exists(PDO $pdo, string $t): bool {
  $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t LIMIT 1');
  $stmt->execute([':t'=>$t]); return (bool)$stmt->fetchColumn();
}
function column_exists(PDO $pdo, string $t, string $c): bool {
  $stmt = $pdo->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c LIMIT 1');
  $stmt->execute([':t'=>$t, ':c'=>$c]); return (bool)$stmt->fetchColumn();
}

$PRODUCT_TABLE = null;
if (table_exists($pdo,'庫存商品')) $PRODUCT_TABLE = '庫存商品';
elseif (table_exists($pdo,'商品分類')) $PRODUCT_TABLE = '商品分類';
if (!$PRODUCT_TABLE) {
  http_response_code(500);
  echo json_encode(['error'=>'找不到商品主檔：請建立「庫存商品」或「商品分類」資料表'], JSON_UNESCAPED_UNICODE);
  exit;
}

$q        = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$where = '1'; $params = [];

if ($PRODUCT_TABLE === '庫存商品') {
  $has_category_id = column_exists($pdo,'庫存商品','category_id');
  $has_category    = column_exists($pdo,'庫存商品','category');
  $has_unit        = column_exists($pdo,'庫存商品','unit');

  if ($q !== '') { $where .= " AND (CAST(p.id AS CHAR) LIKE :kw OR p.name LIKE :kw ".($has_unit?" OR p.unit LIKE :kw":"").")"; $params[':kw']="%$q%"; }

  if ($has_category_id && table_exists($pdo,'商品分類')) {
    if ($category!=='') { $where .= " AND c.name = :cat"; $params[':cat']=$category; }
    $sql = "SELECT p.id, p.name, ".($has_unit?'p.unit':'NULL AS unit').", c.name AS category
            FROM `庫存商品` p
            LEFT JOIN `商品分類` c ON c.id = p.category_id
            WHERE $where
            ORDER BY p.id ASC";
  } else {
    if ($category!=='') { $where .= " AND p.category = :cat"; $params[':cat']=$category; }
    $sql = "SELECT p.id, p.name, ".($has_unit?'p.unit':'NULL AS unit').", ".($has_category?'p.category':'NULL AS category')."
            FROM `庫存商品` p
            WHERE $where
            ORDER BY p.id ASC";
  }
} else { // 商品分類當作商品主檔（你先暫時這麼用）
  if ($q!==''){ $where.=" AND (CAST(id AS CHAR) LIKE :kw OR name LIKE :kw)"; $params[':kw']="%$q%"; }
  if ($category!==''){ $where.=" AND name = :cat"; $params[':cat']=$category; }
  // 只有 id/name，補齊欄位為空
  $sql = "SELECT id, name, NULL AS unit, name AS category FROM `商品分類` WHERE $where ORDER BY id ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
