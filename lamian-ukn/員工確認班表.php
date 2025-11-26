<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json; charset=utf-8');

// 檢查登入
if (!isset($_SESSION['uid'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => '未登入'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once 'db.php';

try {
    $start = $_GET['start'] ?? null;
    
    if (!$start) {
        throw new Exception('缺少 start 參數');
    }
    
    $startDate = new DateTime($start);
    $endDate = clone $startDate;
    $endDate->modify('+6 days');
    
    $startStr = $startDate->format('Y-m-d');
    $endStr = $endDate->format('Y-m-d');
    
    // 使用正確的欄位名稱
    $sql = "SELECT 
                e.id as user_id,
                e.name as name,
                c.work_date,
                c.start_time,
                c.end_time,
                c.shift_type
            FROM 員工基本資料 e
            LEFT JOIN 確認班表 c ON e.id = c.user_id 
                AND c.work_date BETWEEN ? AND ?
            ORDER BY e.id, c.work_date";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('SQL 準備失敗: ' . $conn->error);
    }
    
    $stmt->bind_param('ss', $startStr, $endStr);
    
    if (!$stmt->execute()) {
        throw new Exception('SQL 執行失敗: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // 組織資料結構
    $employees = [];
    
    while ($row = $result->fetch_assoc()) {
        $userId = $row['user_id'];
        
        // 初始化員工資料
        if (!isset($employees[$userId])) {
            $employees[$userId] = [
                'user_id' => $userId,
                'name' => $row['name'],
                'shifts' => [[], [], [], [], [], [], []] // 週一到週日
            ];
        }
        
        // 如果有班表資料
        if ($row['work_date']) {
            $workDate = new DateTime($row['work_date']);
            $dayIndex = (int)$workDate->diff($startDate)->format('%a');
            
            if ($dayIndex >= 0 && $dayIndex < 7) {
                $startTime = substr($row['start_time'], 0, 5);
                $endTime = substr($row['end_time'], 0, 5);
                
                // 格式化班次顯示
                if ($row['shift_type']) {
                    $shiftLabel = "{$row['shift_type']}<br>{$startTime}-{$endTime}";
                } else {
                    $shiftLabel = "{$startTime}-{$endTime}";
                }
                
                $employees[$userId]['shifts'][$dayIndex][] = $shiftLabel;
            }
        }
    }
    
    // 轉換為陣列格式
    $rows = array_values($employees);
    
    echo json_encode([
        'success' => true,
        'rows' => $rows,
        'start' => $startStr,
        'end' => $endStr
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>