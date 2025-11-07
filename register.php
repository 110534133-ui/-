<?php
// register.php - 會員註冊處理

// 顯示錯誤訊息，方便除錯
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 引入資料庫連線
require_once "config.php";

// 只處理 POST 請求
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 取得表單資料 - 加入 email
    $name     = $_POST["name"];
    $phone    = $_POST["phone"];
    $email    = $_POST["email"];  // ← 加入這行！
    $password = $_POST["password"];
    $birthday = $_POST["birthday"];

    // 檢查必填欄位
    if (empty($name) || empty($phone) || empty($email) || empty($password) || empty($birthday)) {
        echo "<script>alert('請填寫所有必填欄位！'); window.history.back();</script>";
        exit;
    }

    // 檢查手機號碼是否已註冊
    $check_sql = "SELECT id FROM ramen_members WHERE `電話` = ?";
    $stmt = $conn->prepare($check_sql);
    if (!$stmt) {
        die("Prepare 失敗: " . $conn->error);
    }
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('此手機號碼已註冊過！'); window.history.back();</script>";
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // 密碼加密
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 新增會員 - 修正 SQL 語句，加入電子郵件
    $insert_sql = "INSERT INTO ramen_members (`姓名`, `電話`, `電子郵件`, `密碼`, `生日`) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    if (!$stmt) {
        die("Prepare 失敗: " . $conn->error);
    }
    $stmt->bind_param("sssss", $name, $phone, $email, $hashed_password, $birthday);

    if ($stmt->execute()) {
        echo "<script>
            alert('註冊成功！請重新登入');
            window.location.href='login.html';
        </script>";
    } else {
        echo "<script>
            alert('註冊失敗，請稍後再試');
            window.history.back();
        </script>";
    }

    $stmt->close();
    $conn->close();
}
?>