<?php
$host = "localhost";   // 你的資料庫主機
$user = "root";        // 你的帳號（通常是 root）
$pass = "";            // 你的密碼（XAMPP/MAMP 通常空白）
$db   = "lamain";      // 你的資料庫名稱

$conn = new mysqli($host, $user, $pass, $db);

// 檢查連線
if ($conn->connect_error) {
    die("資料庫連線失敗：" . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


?>
