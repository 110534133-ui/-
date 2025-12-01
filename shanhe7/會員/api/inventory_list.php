<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';

$INVENTORY_TABLE = '庫存管理';   // 你庫存紀錄的表
$CANDIDATE_PRODUCT_TABLES = ['庫存商品', '商品分類']; // 優先用前者；沒有就用後者

// 檢查某表是否存在於目前 DB
function table_exists(PDO $pdo, string $t): bool {
  $stmt = $pdo->prepare(
    'SELECT 1 FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = :t LIMIT 1'
  );
  $stmt->execute([':t' => $t]);
  return (bool)$stmt->fetchColumn();
}

// 找到可用的商品主檔表名
$PRODUCT_TABLE = null;
foreach ($CANDIDATE_PRODUCT_TABLES as $t) {
  if (table_exists($pdo, $t)) { $PRODUCT_TABLE = $t; break; }
}
if (!$PRODUCT_TABLE) {
  http_response_code(500);
  echo json_encode(['error'=>'找不到商品主檔：請建立「庫存商品」或「商品分類」資料表'], JSON_UNESCAPED_UNICODE);
  exit;
}

// 參數
$q      = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit  = isset($_GET['limit'])  ? max(1, min(5000, (int)$_GET['limit'])) : 1000;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

// WHERE & 綁定
$where = '1';
$params = [];
if ($q !== '') {
  // id / item_id / updated_by / 品名 / 類別 都能搜
  $where .= " AND (
    CAST(i.id AS CHAR) LIKE :kw OR
    CAST(i.item_id AS CHAR) LIKE :kw OR
    i.updated_by LIKE :kw OR
    p.name LIKE :kw OR
    p.category_id  LIKE :kw
  )";
  $params[':kw'] = "%$q%";
}

// 注意：中文表名要用反引號包起來
$sql = "
  SELECT
    i.id,
    i.item_id,
    i.quantity,
    i.last_update,
    i.updated_by,
    p.name      AS name,
    p.category_id   AS category_id,
    p.unit      AS unit
  FROM `{$INVENTORY_TABLE}` AS i
  LEFT JOIN `{$PRODUCT_TABLE}` AS p
         ON p.id = i.item_id
  WHERE $where
  ORDER BY i.id DESC
  LIMIT :lim OFFSET :off
";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// 統一回傳 last_update_iso（無論你是 DATETIME 或存 UNIX 秒數）
foreach ($rows as &$r) {
  if (isset($r['last_update'])) {
    $v = $r['last_update'];
    if (ctype_digit((string)$v)) {
      // UNIX 秒數
      $ts = (int)$v;
      $r['last_update_iso'] = $ts > 0 ? date('Y-m-d H:i:s', $ts) : null;
    } else {
      // DATETIME/TIMESTAMP 字串
      $t = strtotime($v);
      $r['last_update_iso'] = $t ? date('Y-m-d H:i:s', $t) : $v;
    }
  }
}

echo json_encode($rows, JSON_UNESCAPED_UNICODE);

