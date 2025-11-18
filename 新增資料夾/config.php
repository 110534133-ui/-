<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "lamian";  // 修正資料庫名稱

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("資料庫連線失敗：" . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>