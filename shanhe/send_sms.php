<?php
require_once "config.php";

header('Content-Type: application/json; charset=utf-8');

$phone = trim($_POST['reset-phone'] ?? '');

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => '請輸入手機號碼']);
    exit;
}

// 檢查會員存在
$check_stmt = $conn->prepare("SELECT * FROM ramen_members WHERE 電話 = ?");
$check_stmt->bind_param("s", $phone);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => '此電話號碼尚未註冊會員']);
    exit;
}

// 產生 6 位驗證碼
$verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expiry_time = date('Y-m-d H:i:s', time() + 300); // 5 分鐘過期

// 寫入資料庫（覆蓋舊驗證碼）
$update_stmt = $conn->prepare("UPDATE ramen_members SET 驗證碼=?, 驗證碼建立時間=? WHERE 電話=?");
$update_stmt->bind_param("sss", $verification_code, $expiry_time, $phone);

if ($update_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => '驗證碼已發送至您的手機！',
        'test_code' => $verification_code // 測試用，可正式環境移除
    ]);
} else {
    echo json_encode(['success' => false, 'message' => '驗證碼發送失敗']);
}

$update_stmt->close();
$conn->close();
?>
