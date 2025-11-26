<?php
// /lamian-ukn/api/get_confirmed_roster.php - 取得老闆確認的班表

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// 檢查是否已登入
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => '未登入'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['uid'];

// 資料庫連線
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "lamian";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 取得查詢參數
    $weekStart = $_GET['week_start'] ?? null;
    $weekEnd = $_GET['week_end'] ?? null;
    $specificUserId = $_GET['user_id'] ?? null;
    
    // 構建 SQL 查詢
    $sql = "SELECT * FROM `確認班表` WHERE 1=1";
    $params = [];
    
    // 根據用戶等級決定查詢範圍
    $userLevel = $_SESSION['user_level'] ?? $_SESSION['role_code'] ?? 'C';
    
    if ($userLevel === 'C') {
        // C級員工只能看自己的班表
        $sql .= " AND user_id = :user_id";
        $params[':user_id'] = $userId;
    } elseif ($specificUserId) {
        // A/B級如果指定user_id,則只查該員工
        $sql .= " AND user_id = :user_id";
        $params[':user_id'] = $specificUserId;
    }
    
    // 如果有指定週起始日
    if ($weekStart) {
        $sql .= " AND work_date >= :week_start";
        $params[':week_start'] = $weekStart;
    }
    
    // 如果有指定週結束日
    if ($weekEnd) {
        $sql .= " AND work_date <= :week_end";
        $params[':week_end'] = $weekEnd;
    }
    
    // 排序:依日期和時間
    $sql .= " ORDER BY work_date ASC, start_time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rosters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 如果需要包含員工姓名,JOIN員工基本資料表
    if (count($rosters) > 0 && ($userLevel === 'A' || $userLevel === 'B')) {
        $userIds = array_unique(array_column($rosters, 'user_id'));
        
        if (count($userIds) > 0) {
            $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
            $nameStmt = $conn->prepare("SELECT 編號, 姓名 FROM `員工基本資料` WHERE 編號 IN ($placeholders)");
            $nameStmt->execute($userIds);
            $names = $nameStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // 將姓名加入結果
            foreach ($rosters as &$roster) {
                $roster['user_name'] = $names[$roster['user_id']] ?? '未知';
            }
        }
    } elseif (count($rosters) > 0 && $userLevel === 'C') {
        // C級員工也需要自己的姓名
        foreach ($rosters as &$roster) {
            $roster['user_name'] = $_SESSION['name'] ?? '我';
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $rosters,
        'count' => count($rosters),
        'user_level' => $userLevel
    ], JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => '資料庫錯誤: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>