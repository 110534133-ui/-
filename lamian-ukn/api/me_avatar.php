<?php
// /lamian-ukn/api/me_avatar.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth_core.php';
require_once __DIR__ . '/config.php';

// 驗證登入
$uid = require_login_core();

// === 檢查檔案 ===
if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['error' => '未選擇圖片或上傳失敗'], JSON_UNESCAPED_UNICODE);
  exit;
}

$f = $_FILES['avatar'];
$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));

// === 限制檔案格式 ===
$allow = ['jpg','jpeg','png'];
if (!in_array($ext, $allow)) {
  http_response_code(400);
  echo json_encode(['error' => '只接受 JPG / PNG 格式'], JSON_UNESCAPED_UNICODE);
  exit;
}

// === 限制檔案大小（3MB） ===
if ($f['size'] > 3 * 1024 * 1024) {
  http_response_code(400);
  echo json_encode(['error' => '圖片太大（上限 3MB）'], JSON_UNESCAPED_UNICODE);
  exit;
}

// === 檢查/建立目錄 ===
$upload_dir = dirname(__DIR__) . '/uploads/avatar/'; // C:\xampp\htdocs\lamian-ukn\uploads\avatar\
if (!is_dir($upload_dir)) {
  mkdir($upload_dir, 0777, true);
}

// === 產生檔名 ===
$newname = 'emp_' . $uid . '_' . time() . '.' . $ext;
$target_path = $upload_dir . $newname;

// === 儲存檔案 ===
if (!move_uploaded_file($f['tmp_name'], $target_path)) {
  http_response_code(500);
  echo json_encode(['error' => '無法儲存檔案'], JSON_UNESCAPED_UNICODE);
  exit;
}

// === 網頁可訪問的網址 ===
$public_url = '/lamian-ukn/uploads/avatar/' . $newname;

// === 更新資料庫 ===
try {
  $pdo = pdo();
  $st = $pdo->prepare("UPDATE `員工基本資料` SET avatar_url=? WHERE id=?");
  $st->execute([$public_url, $uid]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => '資料庫更新失敗: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}

// === 回傳成功 ===
echo json_encode([
  'ok' => true,
  'avatar_url' => $public_url
], JSON_UNESCAPED_UNICODE);
