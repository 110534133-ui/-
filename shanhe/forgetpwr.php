<?php
// 資料庫連接設定
$servername = "localhost";
$username = "your_username"; // 替換為您的資料庫用戶名
$password = "your_password"; // 替換為您的資料庫密碼
$dbname = "your_database"; // 替換為您的資料庫名稱

// 創建連接
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連接
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $newPassword = password_hash($_POST['new-password'], PASSWORD_DEFAULT);

    // 更新密碼的 SQL 語句
    $sql = "UPDATE members SET password = ? WHERE token = ?"; // 假設您有一個 token 字段
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $newPassword, $token);
    
    if ($stmt->execute()) {
        echo "密碼已成功更新！";
    } else {
        echo "更新密碼時出錯。";
    }

    // 關閉連接
    $stmt->close();
    $conn->close();
}
?>