<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$phone = trim($_POST['phone'] ?? '');
$code = trim($_POST['verification-code'] ?? '');

if (empty($phone) || empty($code)) {
    echo json_encode(['success' => false, 'message' => '請輸入完整資料']);
    exit;
}

// 查詢驗證碼
$stmt = $conn->prepare("SELECT 驗證碼, 驗證碼建立時間 FROM ramen_members WHERE 電話=?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();

if (!$record || empty($record['驗證碼'])) {
    echo json_encode(['success' => false, 'message' => '驗證碼不存在，請重新發送']);
    exit;
}

// 檢查是否過期
if (strtotime($record['驗證碼建立時間']) < time() - 300) {
    // 自動清除過期驗證碼
    $clear_stmt = $conn->prepare("UPDATE ramen_members SET 驗證碼=NULL, 驗證碼建立時間=NULL WHERE 電話=?");
    $clear_stmt->bind_param("s", $phone);
    $clear_stmt->execute();
    $clear_stmt->close();

    echo json_encode(['success' => false, 'message' => '驗證碼已過期，請重新發送']);
    exit;
}

// 驗證
if ($record['驗證碼'] === $code) {
    // 清除驗證碼
    $clear_stmt = $conn->prepare("UPDATE ramen_members SET 驗證碼=NULL, 驗證碼建立時間=NULL WHERE 電話=?");
    $clear_stmt->bind_param("s", $phone);
    $clear_stmt->execute();
    $clear_stmt->close();

    session_start();
    $_SESSION['reset_phone'] = $phone;
    $_SESSION['reset_verified'] = true;

    echo json_encode([
        'success' => true,
        'message' => '驗證成功！',
    ]);
} else {
    echo json_encode(['success' => false, 'message' => '驗證碼錯誤']);
}

$stmt->close();
$conn->close();
?>
