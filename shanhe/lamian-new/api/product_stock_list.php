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

$INVENTORY_TABLE = '庫存管理';
if (!table_exists($pdo,$INVENTORY_TABLE)) {
  http_response_code(500);
  echo json_encode(['error'=>'找不到「庫存管理」資料表'], JSON_UNESCAPED_UNICODE);
  exit;
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
$limit    = isset($_GET['limit'])  ? max(1, min(5000, (int)$_GET['limit'])) : 2000;
$offset   = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

$where = '1'; $params = [];

if ($PRODUCT_TABLE === '庫存商品') {
  $has_category_id = column_exists($pdo,'庫存商品','category_id');
  $has_category    = column_exists($pdo,'庫存商品','category');
  $has_unit        = column_exists($pdo,'庫存商品','unit');

  if ($q !== '') {
    $where .= " AND (CAST(p.id AS CHAR) LIKE :kw OR p.name LIKE :kw ".($has_unit?" OR p.unit LIKE :kw":"").($has_category?" OR p.category LIKE :kw":"").")";
    $params[':kw']="%$q%";
  }

  if ($has_category_id && table_exists($pdo,'商品分類')) {
    if ($category!==''){ $where.=" AND c.name = :cat"; $params[':cat']=$category; }
    $sql = "
      SELECT
        p.id, p.name, ".($has_unit?'p.unit':'NULL AS unit').", c.name AS category,
        COALESCE(s.qty,0) AS quantity, s.last_update,
        (SELECT i.updated_by FROM `{$INVENTORY_TABLE}` i WHERE i.item_id=p.id ORDER BY i.last_update DESC, i.id DESC LIMIT 1) AS updated_by
      FROM `庫存商品` p
      LEFT JOIN `商品分類` c ON c.id = p.category_id
      LEFT JOIN (
        SELECT item_id, SUM(quantity) AS qty, MAX(last_update) AS last_update
        FROM `{$INVENTORY_TABLE}` GROUP BY item_id
      ) s ON s.item_id = p.id
      WHERE $where
      ORDER BY p.id ASC
      LIMIT :lim OFFSET :off";
  } else {
    if ($category!==''){ $where.=" AND ".($has_category?'p.category':'1')." = :cat"; $params[':cat']=$category; }
    $sql = "
      SELECT
        p.id, p.name, ".($has_unit?'p.unit':'NULL AS unit').", ".($has_category?'p.category':'NULL AS category')." AS category,
        COALESCE(s.qty,0) AS quantity, s.last_update,
        (SELECT i.updated_by FROM `{$INVENTORY_TABLE}` i WHERE i.item_id=p.id ORDER BY i.last_update DESC, i.id DESC LIMIT 1) AS updated_by
      FROM `庫存商品` p
      LEFT JOIN (
        SELECT item_id, SUM(quantity) AS qty, MAX(last_update) AS last_update
        FROM `{$INVENTORY_TABLE}` GROUP BY item_id
      ) s ON s.item_id = p.id
      WHERE $where
      ORDER BY p.id ASC
      LIMIT :lim OFFSET :off";
  }
} else {
  // 商品分類當商品主檔(暫用)：只有 id/name
  if ($q!==''){ $where.=" AND (CAST(p.id AS CHAR) LIKE :kw OR p.name LIKE :kw)"; $params[':kw']="%$q%"; }
  if ($category!==''){ $where.=" AND p.name = :cat"; $params[':cat']=$category; }
  $sql = "
    SELECT
      p.id, p.name, NULL AS unit, p.name AS category,
      COALESCE(s.qty,0) AS quantity, s.last_update,
      (SELECT i.updated_by FROM `{$INVENTORY_TABLE}` i WHERE i.item_id=p.id ORDER BY i.last_update DESC, i.id DESC LIMIT 1) AS updated_by
    FROM `商品分類` p
    LEFT JOIN (
      SELECT item_id, SUM(quantity) AS qty, MAX(last_update) AS last_update
      FROM `{$INVENTORY_TABLE}` GROUP BY item_id
    ) s ON s.item_id = p.id
    WHERE $where
    ORDER BY p.id ASC
    LIMIT :lim OFFSET :off";
}

$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue($k,$v, PDO::PARAM_STR);
$stmt->bindValue(':lim',$limit,PDO::PARAM_INT);
$stmt->bindValue(':off',$offset,PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

foreach ($rows as &$r) {
  $v = $r['last_update'];
  if ($v !== null) {
    if (ctype_digit((string)$v)) { $ts=(int)$v; $r['last_update_iso'] = $ts?date('Y-m-d H:i:s',$ts):null; }
    else { $t=strtotime($v); $r['last_update_iso'] = $t?date('Y-m-d H:i:s',$t):$v; }
  }
}
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
