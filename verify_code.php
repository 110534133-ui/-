<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_SESSION['reset_email'] ?? '';
    $inputCode = trim($_POST['verification_code'] ?? '');

    if (empty($email) || empty($inputCode)) {
        echo "請輸入驗證碼";
        exit;
    }

    // 從 DB 取出驗證碼與建立時間
    $stmt = $conn->prepare("SELECT 驗證碼, 驗證碼建立時間 FROM ramen_members WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($dbCode, $dbTime);
    $stmt->fetch();
    $stmt->close();

    if (!$dbCode) {
        echo "驗證碼不存在，請重新發送";
        exit;
    }

    // 檢查是否過期（5 分鐘 = 300 秒）
    if (time() - strtotime($dbTime) > 300) {
        echo "驗證碼已過期，請重新發送";
        exit;
    }

    // 檢查驗證碼
    if ($inputCode == $dbCode) {
        $_SESSION['reset_verified'] = true; // 驗證成功
        echo "驗證成功";
    } else {
        echo "驗證碼錯誤";
    }
} else {
    echo "請透過表單送出資料";
}
?>
