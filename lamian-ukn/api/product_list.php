<?php
// 取「庫存商品」清單，給前端下拉選單用
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';

try {
  $pdo = pdo();

  $sql = "
    SELECT
      p.id,
      p.name,
      p.unit,
      p.category_id,
      c.name AS category
    FROM `庫存商品` AS p
    LEFT JOIN `商品分類` AS c ON c.id = p.category_id
    ORDER BY p.id ASC
  ";
  $st = $pdo->query($sql);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'product_list failed','detail'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
