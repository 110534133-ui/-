<?php
// 🔥 修正版：api/inventory_adjust.php
// 新增一筆庫存異動（入庫/出庫）
header('Content-Type: application/json; charset=utf-8');

// 🔥 修正：引入標準設定檔和權限檢查
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/config.php'; // 包含 pdo(), ok(), err()

try {
  // 🔥 修正：檢查權限 (A 或 B 級)
  if (!check_user_level(['A', 'B'], false)) {
      err('權限不足 (僅限 A/B 級)', 403);
  }

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // 🔥 修正：使用 err()
    err('Method Not Allowed', 405);
  }

  $pdo  = pdo();
  $body = json_decode(file_get_contents('php://input'), true) ?? [];

  $item_id    = isset($body['item_id']) ? (int)$body['item_id'] : 0;
  $quantity   = isset($body['quantity']) ? (int)$body['quantity'] : 0; // 正=入庫 負=出庫
  $updated_by = trim((string)($body['updated_by'] ?? ''));

  // 🔥 修正：使用 err()
  if ($item_id <= 0) { err('item_id required', 422); }
  if ($quantity === 0) { err('quantity must be non-zero', 422); }
  if ($updated_by === '') { err('updated_by required', 422); }

  // 轉換時間（給 DATETIME 欄位）
  $when = trim((string)($body['when'] ?? ''));
  if ($when !== '') {
    $when = str_replace('T', ' ', $when);
    if (strlen($when) === 16) $when .= ':00';
    $dt = date('Y-m-d H:i:s', strtotime($when));
  } else {
    $dt = date('Y-m-d H:i:s');
  }

  // 確認品項存在
  $chk = $pdo->prepare("SELECT 1 FROM `庫存商品` WHERE id=:id LIMIT 1");
  $chk->execute([':id'=>$item_id]);
  if (!$chk->fetchColumn()) {
    // 🔥 修正：使用 err()
    err('product not found: '.$item_id, 404);
  }

  // 寫入一筆異動
  $ins = $pdo->prepare("
    INSERT INTO `庫存管理` (item_id, quantity, last_update, updated_by)
    VALUES (:iid, :q, :lu, :u)
  ");
  $ins->execute([':iid'=>$item_id, ':q'=>$quantity, ':lu'=>$dt, ':u'=>$updated_by]);
  $id = (int)$pdo->lastInsertId();

  // 🔥 修正：使用 ok()
  ok(['ok'=>true, 'id'=>$id]);

} catch (Throwable $e) {
  // 🔥 修正：使用 err() 並記錄日誌
  error_log("inventory_adjust.php Error: " . $e->getMessage());
  err('API 內部錯誤', 500, ['detail'=>$e->getMessage()]);
}
?>