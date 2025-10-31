<?php
// 開啟錯誤顯示（開發用）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "config.php"; // 資料庫連線設定
session_start();

// 僅處理 POST 請求
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $phone = trim($_POST["phone"] ?? "");
    $password = trim($_POST["password"] ?? "");

    // ====== 基本欄位檢查 ======
    if (empty($phone)) {
        echo "<script>alert('請輸入電話號碼'); window.history.back();</script>";
        exit;
    }

    if (empty($password)) {
        echo "<script>alert('請輸入密碼'); window.history.back();</script>";
        exit;
    }

    // ====== 查詢會員資料 ======
    $sql = "SELECT id, 姓名, 電話, 密碼 FROM ramen_members WHERE 電話 = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("SQL Prepare 失敗：" . $conn->error);
    }

    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->bind_result($id, $name, $db_phone, $db_password);

    if ($stmt->fetch()) {
        // 找到會員 → 驗證密碼
        if (password_verify($password, $db_password)) {
            // ✅ 登入成功
            $_SESSION['member_id'] = $id;
            $_SESSION['member_name'] = $name;
            $_SESSION['member_phone'] = $db_phone;

            // 登入成功 → 跳轉會員首頁
            echo "<script>
                alert('登入成功！');
                window.location.href='會員/index.php';
            </script>";
            exit;

        } else {
            // ❌ 密碼錯誤
            echo "<script>alert('密碼不正確，請重新輸入'); window.history.back();</script>";
            exit;
        }
    } else {
        // ❌ 帳號不存在
        echo "<script>alert('查無此帳號，請先註冊會員'); window.history.back();</script>";
        exit;
    }

    $stmt->close();
    $conn->close();

} else {
    // 非 POST 請求
    echo "<script>alert('請使用登入表單送出資料'); window.history.back();</script>";
    exit;
}
?>
