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

// 獲取電子郵件
$email = trim($_POST['reset-email']);

// 檢查電子郵件是否存在於資料庫
$sql = "SELECT * FROM members WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // 確認電子郵件存在後，生成重設密碼的鏈接
    $token = bin2hex(random_bytes(50)); // 生成一個隨機的 token
    $resetLink = "http://yourwebsite.com/reset_password.php?token=$token";

    // 在這裡發送電子郵件給用戶，包含重設密碼的連結
    $subject = "重設您的密碼";
    $message = "點擊以下鏈接重設您的密碼：\n$resetLink";
    $headers = "From: no-reply@yourwebsite.com";

    if (mail($email, $subject, $message, $headers)) {
        echo "重設密碼的連結已發送到您的電子郵件！";
    } else {
        echo "無法發送電子郵件，請稍後再試。";
    }
} else {
    echo "該電子郵件地址未註冊。";
}

// 關閉連接
$stmt->close();
$conn->close();
?>