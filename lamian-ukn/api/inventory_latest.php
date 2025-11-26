<?php
// 🔥 修正版：api/inventory_latest.php
// 取最近異動清單（預設 20 筆）
header('Content-Type: application/json; charset=utf-8');

// 🔥 修正：引入標準設定檔和權限檢查
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/config.php'; // 包含 pdo(), ok(), err()

try {
  // 🔥 修正：檢查權限 (A 或 B 級)
  if (!check_user_level(['A', 'B'], false)) {
      err('權限不足 (僅限 A/B 級)', 403);
  }

  $pdo   = pdo();
  $limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 20;

  $sql = "
    SELECT
      i.id,
      i.item_id,
      i.quantity,
      i.last_update,
      i.updated_by,
      p.name,
      p.unit,
      c.name AS category
    FROM `庫存管理` AS i
    LEFT JOIN `庫存商品` AS p ON p.id = i.item_id
    LEFT JOIN `商品分類` AS c ON c.id = p.category_id
    ORDER BY i.id DESC
    LIMIT :lim
  ";
  $st = $pdo->prepare($sql);
  $st->bindValue(':lim', $limit, PDO::PARAM_INT);
  $st->execute();

  $rows = [];
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $lu = $r['last_update'];
    $r['last_update_iso'] = $lu ? (date('Y-m-d H:i:s', strtotime($lu))) : null;
    $rows[] = $r;
  }

  // 🔥 修正：使用 ok()
  ok($rows);

} catch (Throwable $e) {
  // 🔥 修正：使用 err() 並記錄日誌
  error_log("inventory_latest.php Error: " . $e->getMessage());
  err('API 內部錯誤', 500, ['detail'=>$e->getMessage()]);
}
?>