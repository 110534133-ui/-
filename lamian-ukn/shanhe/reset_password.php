<?php
session_start();
include 'config.php';

// 必須有 email
if (!isset($_SESSION['reset_email'])) {
    echo "未授權：請從忘記密碼流程重新開始";
    exit;
}

// 必須通過驗證碼
if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
    echo "未授權：請先通過驗證碼";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = trim($_POST['new_password'] ?? '');
    $email = $_SESSION['reset_email'];

    if (empty($newPassword)) {
        echo "請輸入新密碼";
        exit;
    }

    if (strlen($newPassword) < 6) {
        echo "密碼長度至少需6個字元";
        exit;
    }

    // 密碼加密
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

    // 更新
    $stmt = $conn->prepare("UPDATE ramen_members 
                            SET `密碼` = ?, `驗證碼` = NULL, `驗證碼建立時間` = NULL 
                            WHERE Email = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ss", $hashed, $email);

    if ($stmt->execute()) {

        // 清除 reset session
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_verified']);

        echo "success"; // 給前端判斷跳轉
    } else {
        echo "密碼更新失敗：" . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}

?>
