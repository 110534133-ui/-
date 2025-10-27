<?php
session_start();
require_once "config.php";

// 移除 JSON header

// 確認是否通過驗證階段
if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']) {
    echo "<script>alert('尚未通過驗證，請先取得驗證碼'); window.location.href='index.html';</script>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPassword = trim($_POST['new-password'] ?? '');
    $confirmPassword = trim($_POST['confirm-password'] ?? '');
    $phone = $_SESSION['reset_phone'] ?? '';

    // 驗證欄位
    if (empty($newPassword) || empty($confirmPassword)) {
        echo "<script>alert('請輸入新密碼並再次確認'); window.history.back();</script>";
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        echo "<script>alert('兩次密碼不一致'); window.history.back();</script>";
        exit;
    }

    if (strlen($newPassword) < 6) {
        echo "<script>alert('密碼長度至少需6個字元'); window.history.back();</script>";
        exit;
    }

    try {
        // 更新密碼到資料庫
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE ramen_members SET 密碼 = ? WHERE 電話 = ?");
        $stmt->bind_param("ss", $hashedPassword, $phone);

        if ($stmt->execute()) {
            // 清除所有驗證相關的 session
            session_destroy();
            echo "<script>
                alert('密碼重設成功！請使用新密碼登入');
                // 關閉彈窗並跳轉到登入頁面
                document.getElementById('forgot-password-popup-step2').style.display = 'none';
                window.location.href = 'login.html';
            </script>";
        } else {
            echo "<script>alert('密碼更新失敗，請稍後再試'); window.history.back();</script>";
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        echo "<script>alert('系統錯誤，請稍後再試'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('請求方法錯誤'); window.history.back();</script>";
}
?>