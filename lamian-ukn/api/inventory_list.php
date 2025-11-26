<?php
// /lamian-ukn/api/inventory_list.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';

try {
  $pdo = pdo();

  // 參數
  $q      = isset($_GET['q']) ? trim($_GET['q']) : '';
  $limit  = isset($_GET['limit'])  ? max(1, min(5000, (int)$_GET['limit'])) : 1000;
  $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

  // WHERE
  $where  = '1=1';
  $bind   = [];
  if ($q !== '') {
    // 可搜：庫存管理.id / item_id / 更新人 updated_by / 商品名 / 分類名
    $where .= " AND (
      CAST(i.id AS CHAR) LIKE :kw OR
      CAST(i.item_id AS CHAR) LIKE :kw OR
      i.updated_by LIKE :kw OR
      p.name LIKE :kw OR
      c.name LIKE :kw
    )";
    $bind[':kw'] = "%{$q}%";
  }

  // 主要查詢：庫存管理 + 庫存商品 + 商品分類
  $sql = "
    SELECT
      i.id,
      i.item_id,
      i.quantity,
      i.last_update,          -- 你的表是 DATETIME（從截圖看）
      i.updated_by,
      p.name         AS product_name,
      p.category_id  AS product_category_id,
      p.unit         AS product_unit,
      c.name         AS category_name
    FROM `庫存管理` AS i
    LEFT JOIN `庫存商品` AS p ON p.id = i.item_id
    LEFT JOIN `商品分類` AS c ON c.id = p.category_id
    WHERE $where
    ORDER BY i.id DESC
    LIMIT :lim OFFSET :off
  ";

  $st = $pdo->prepare($sql);
  foreach ($bind as $k=>$v) $st->bindValue($k, $v, PDO::PARAM_STR);
  $st->bindValue(':lim', $limit, PDO::PARAM_INT);
  $st->bindValue(':off', $offset, PDO::PARAM_INT);
  $st->execute();

  $rows = [];
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    // last_update 統一給一個標準的字串欄位（方便前端顯示）
    $lu = $r['last_update'];
    if ($lu === null || $lu === '') {
      $r['last_update_iso'] = null;
    } else {
      // 你這張欄位是 DATETIME，保險起見用 strtotime 轉一次
      $ts = strtotime($lu);
      $r['last_update_iso'] = $ts ? date('Y-m-d H:i:s', $ts) : (string)$lu;
    }

    // 也給一個簡短欄位名，前端比較順手
    $r['name']       = $r['product_name'];
    $r['unit']       = $r['product_unit'];
    $r['category']   = $r['category_name'];

    $rows[] = $r;
  }

  echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'inventory_list failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
