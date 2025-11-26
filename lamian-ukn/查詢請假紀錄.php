<?php
/**
 * API: 查詢當前登入員工的請假記錄
 * Method: GET
 * Response: JSON { "data": [...] }  // 前端期望的格式
 */

// 啟動 Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設定回應標頭
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 連線設定
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "lamian";

try {
    // 建立連線
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("連線失敗: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // 🔥 取得登入員工姓名 (與 indexC.php 和新增請假.php 保持一致)
    $employeeName = null;
    
    // 方法1: 從 Session 取得 (優先使用 'name')
    if (isset($_SESSION['name']) && !empty($_SESSION['name'])) {
        $employeeName = $_SESSION['name'];
    }
    // 方法2: 備用 - 使用 'employee_name'
    elseif (isset($_SESSION['employee_name']) && !empty($_SESSION['employee_name'])) {
        $employeeName = $_SESSION['employee_name'];
    }
    // 方法3: 如果有 uid,從資料庫查詢
    elseif (isset($_SESSION['uid']) && !empty($_SESSION['uid'])) {
        $stmt = $conn->prepare("SELECT name FROM 員工基本資料 WHERE id = ?");
        $stmt->bind_param("s", $_SESSION['uid']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $employeeName = $row['name'];
        }
        $stmt->close();
    }
    
    // 如果還是沒有,回傳錯誤
    if (empty($employeeName)) {
        throw new Exception("無法取得員工資訊,請重新登入");
    }
    
    // SQL 查詢,JOIN 假別資料表,只查詢當前登入員工的記錄
    $stmt = $conn->prepare("
        SELECT 
            假別.name AS leave_type_name,
            leave_system.start_date,
            leave_system.end_date,
            leave_system.reason,
            leave_system.status,
            leave_system.request_id
        FROM leave_system
        JOIN 假別 ON leave_system.leave_type_id = 假別.id
        WHERE leave_system.name = ?
        ORDER BY leave_system.request_id DESC
    ");
    
    if (!$stmt) {
        throw new Exception("準備查詢失敗: " . $conn->error);
    }
    
    $stmt->bind_param("s", $employeeName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                "type" => $row["leave_type_name"],
                "start" => $row["start_date"],
                "end" => $row["end_date"],
                "reason" => $row["reason"] ?: "-",
                "status" => intval($row["status"]),
                "id" => $row["request_id"]
            ];
        }
    }
    
    $stmt->close();
    $conn->close();
    
    // 🔥 重要:返回正確的格式 { "data": [...] }
    echo json_encode([
        "data" => $data
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("查詢請假紀錄錯誤: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'success' => false
    ], JSON_UNESCAPED_UNICODE);
}
?>