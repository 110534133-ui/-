<?php
// 資料庫配置
$servername = "localhost"; // 資料庫伺服器
$username = "root"; // 資料庫用戶名
$password = ""; // 資料庫密碼
$dbname = "lamain"; // 資料庫名稱

// 創建資料庫連接
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連接
if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}

// 檢查是否有接收到請假申請的數據
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 獲取請假申請的數據
    $leaveType = $_POST['leaveType']; // 假別
    $startDate = $_POST['startDate']; // 開始日期
    $endDate = $_POST['endDate']; // 結束日期
    $reason = $_POST['reason']; // 請假原因

    // 查詢郵件地址
    $employeeName = "員工姓名"; // 根據實際情況獲取員工姓名
    $sql = "SELECT email FROM employees WHERE name = '$employeeName'"; // 假設員工資料表名為 employees
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // 獲取郵件地址
        $row = $result->fetch_assoc();
        $employeeEmail = $row['email']; // 員工的電子郵件地址
        $bossEmail = "boss@example.com"; // 老闆的電子郵件地址

        // 郵件主題和內容
        $subject = "$employeeName 的請假通知";
        $message = "請假事項: $leaveType\n" .
                   "請假日期: $startDate 至 $endDate\n" .
                   "請假原因: $reason\n\n" .
                   "請查看並批准。";

        // 設置郵件標頭
        $headers = "From: your_email@example.com\r\n"; // 系統的郵件地址
        $headers .= "Reply-To: your_email@example.com\r\n"; // 回覆郵件的地址

        // 發送郵件
        if (mail($bossEmail, $subject, $message, $headers)) {
            echo "已成功送出請假申請！";
        } else {
            echo "郵件發送失敗，請再試一次。";
        }
    } else {
        echo "找不到該員工的電子郵件地址。";
    }
} else {
    echo "請求無效。";
}

// 關閉資料庫連接
$conn->close();
?>