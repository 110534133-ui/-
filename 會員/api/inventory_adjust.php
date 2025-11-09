<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    throw new Exception('POST only');
  }

  $payload = json_decode(file_get_contents('php://input'), true);
  if (!is_array($payload)) { http_response_code(400); throw new Exception('請傳送 JSON'); }

  $item_id    = (int)($payload['item_id'] ?? 0);
  $quantity   = (int)($payload['quantity'] ?? 0);  // 可正可負
  $updated_by = trim($payload['updated_by'] ?? '');
  $when_raw   = trim($payload['when'] ?? '');

  if ($item_id <= 0)          throw new Exception('缺少 item_id');
  if ($quantity === 0)        throw new Exception('quantity 不可為 0');
  if ($updated_by === '')     throw new Exception('請填 經手人');

  // 時間：空白=現在；或把 'YYYY-MM-DDTHH:mm' 轉 'YYYY-MM-DD HH:MM:SS'
  if ($when_raw === '') $when = date('Y-m-d H:i:s');
  else {
    $ts = strtotime(str_replace('T',' ',$when_raw));
    if (!$ts) throw new Exception('時間格式錯誤');
    $when = date('Y-m-d H:i:s', $ts);
  }

  $sql = "INSERT INTO `庫存管理` (item_id, quantity, last_update, updated_by)
          VALUES (:item_id, :quantity, :last_update, :updated_by)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':item_id'    => $item_id,
    ':quantity'   => $quantity,   // 可為負數(出庫)
    ':last_update'=> $when,
    ':updated_by' => $updated_by,
  ]);

  echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
