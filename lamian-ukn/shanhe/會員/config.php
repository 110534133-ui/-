<?php
$host = "localhost";  
$user = "root";        
$pass = "";          
$db   = "lamian";      

$conn = new mysqli($host, $user, $pass, $db);

// 檢查連線
if ($conn->connect_error) {
    die("資料庫連線失敗：" . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


?>
