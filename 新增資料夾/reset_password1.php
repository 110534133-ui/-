<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

// 檢查是否已驗證
if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
    echo json_encode(['success' => false, 'message' => '請先完成驗證']);
    exit;
}

$email = $_SESSION['reset_email'] ?? '';
$new_password = trim($_POST['new-password'] ?? '');

if (empty($email) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => '資料不完整']);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => '密碼至少需要6個字元']);
    exit;
}

// 密碼加密
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// 更新密碼並清除驗證碼
$stmt = $conn->prepare("UPDATE ramen_members SET 密碼=?, 驗證碼=NULL, 驗證碼建立時間=NULL WHERE Email=?");
$stmt->bind_param("ss", $hashed_password, $email);

if ($stmt->execute()) {
    // 清除 session
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_verified']);

    echo json_encode([
        'success' => true,
        'message' => '密碼重設成功,請使用新密碼登入'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => '密碼更新失敗']);
}

$stmt->close();
$conn->close();
?>