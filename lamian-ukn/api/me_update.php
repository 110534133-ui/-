<?php
// /lamian-ukn/api/me_update.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth_core.php';
require_once __DIR__ . '/config.php';

$uid = require_login_core();

// 1. 檢查請求方法是否為 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => '僅允許 POST 請求'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. 讀取前端發送的 JSON 資料
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => '無效的 JSON 資料'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. 準備要更新的欄位和值
$fields_to_update = [];
$params = [];

// 可編輯的欄位 (從前端 profileForm 收集)
if (isset($data['email'])) {
    $fields_to_update[] = 'email = ?';
    $params[] = $data['email'];
}
if (isset($data['address'])) {
    $fields_to_update[] = 'address = ?';
    $params[] = $data['address'];
}
if (isset($data['emergency_contact'])) {
    $fields_to_update[] = 'emergency_contact = ?';
    $params[] = $data['emergency_contact'];
}
if (isset($data['emergency_phone'])) {
    $fields_to_update[] = 'emergency_phone = ?';
    $params[] = $data['emergency_phone'];
}
if (isset($data['memo'])) {
    $fields_to_update[] = 'memo = ?';
    $params[] = $data['memo'];
}

// 處理密碼更新
if (!empty($data['new_password'])) {
    // 基本驗證 (長度等)
    if (strlen($data['new_password']) < 6) {
         http_response_code(400);
         echo json_encode(['error' => '新密碼至少需要 6 個字元'], JSON_UNESCAPED_UNICODE);
         exit;
    }
    // 雜湊密碼
    $password_hash = password_hash($data['new_password'], PASSWORD_DEFAULT);
    if ($password_hash === false) {
        http_response_code(500);
        echo json_encode(['error' => '密碼雜湊失敗'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $fields_to_update[] = 'password_hash = ?';
    $params[] = $password_hash;
}

// 4. 如果沒有任何要更新的欄位，則直接回傳成功
if (empty($fields_to_update)) {
    echo json_encode(['message' => '沒有需要更新的欄位'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 5. 執行 UPDATE
try {
    $pdo = pdo();
    $sql = "UPDATE `員工基本資料` SET " . implode(', ', $fields_to_update) . " WHERE id = ?";
    $params[] = $uid; // 最後一個參數是 WHERE 條件的 id

    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute($params);

    if ($success) {
        echo json_encode(['message' => '個人資料已更新'], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode(['error' => '更新資料庫失敗'], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    http_response_code(500);
    // 注意：不要在生產環境中直接顯示 $e->getMessage()，可能包含敏感資訊
    error_log("Error updating profile for user $uid: " . $e->getMessage()); // 寫入伺服器日誌
    echo json_encode(['error' => '更新時發生資料庫錯誤'], JSON_UNESCAPED_UNICODE);
    exit;
}
?>