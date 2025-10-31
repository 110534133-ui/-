<?php
// register.php - 會員註冊處理

// 顯示錯誤訊息，方便除錯
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 引入資料庫連線
require_once "config.php";

// ========== 檢查必填欄位 ==========



// 只處理 POST 請求
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 取得表單資料
    $name     = $_POST["name"];
    $phone    = $_POST["phone"];
    $password = $_POST["password"];
    $birthday = $_POST["birthday"];

    if (empty($birthday)) {
        echo "<script>alert('請記得選擇生日喔！'); window.history.back();</script>";
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

    // 新增會員
    $insert_sql = "INSERT INTO ramen_members (`姓名`, `電話`, `密碼`, `生日`) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    if (!$stmt) {
        die("Prepare 失敗: " . $conn->error);
    }
    $stmt->bind_param("ssss", $name, $phone, $hashed_password, $birthday);

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
