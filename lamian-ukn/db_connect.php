<?php
// 資料庫連線設定
$servername = "localhost";     // 本機伺服器
$username = "root";            // 預設帳號
$password = "";                // 預設密碼為空
$dbname = "lamian";            // 你建立的資料庫名稱

// 建立連線
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連線是否成功
if ($conn->connect_error) {
    die("連線失敗：" . $conn->connect_error);
}

// echo "連線成功"; // 測試用，可註解
?>