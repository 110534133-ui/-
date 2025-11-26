<?php
/**
 * 檔案位置: api/schedule/week.php
 * GET /api/schedule/week?start=YYYY-MM-DD
 * 獲取指定週的「已排定班表」
 */

require_once '../../config/database_config.php';

// 設定 headers
setCORSHeaders();
handleOptionsRequest();
setJSONHeaders();

// 只接受 GET 請求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSONError('只接受 GET 請求', 405);
}

// 獲取參數
$weekStart = $_GET['start'] ?? null;

if (!$weekStart) {
    sendJSONError('缺少 start 參數', 400);
}

// 驗證日期格式
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStart)) {
    sendJSONError('日期格式錯誤，應為 YYYY-MM-DD', 400);
}

try {
    $pdo = getDBConnection();
    
    // 計算週結束日期
    $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
    
    // 查詢該週所有員工的「已排定班表」
    $sql = "SELECT 
                s.employee_id,
                e.name as employee_name,
                s.shift_date,
                s.start_time,
                s.end_time
            FROM schedules s
            LEFT JOIN `員工基本資料` e ON s.employee_id = e.id
            WHERE s.shift_date BETWEEN :week_start AND :week_end
            ORDER BY s.employee_id, s.shift_date";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'week_start' => $weekStart,
        'week_end' => $weekEnd
    ]);
    $results = $stmt->fetchAll();
    
    // 如果沒有班表資料，也要列出所有員工
    if (empty($results)) {
        // 獲取所有員工
        $empSql = "SELECT id, name FROM `員工基本資料` ORDER BY id";
        $empStmt = $pdo->query($empSql);
        $employees = $empStmt->fetchAll();
        
        $rows = array_map(function($emp) {
            return [
                'name' => $emp['name'],
                'shifts' => array_fill(0, 7, '-')
            ];
        }, $employees);
        
        sendJSONSuccess([
            'rows' => $rows,
            'message' => '本週尚無排班資料'
        ]);
    }
    
    // 組織資料：按員工分組
    $employeeData = [];
    foreach ($results as $row) {
        $empId = $row['employee_id'];
        
        if (!isset($employeeData[$empId])) {
            $employeeData[$empId] = [
                'name' => $row['employee_name'] ?? '員工' . $empId,
                'shifts' => array_fill(0, 7, '-')
            ];
        }
        
        // 計算是星期幾 (0=週一, 6=週日)
        $shiftDate = new DateTime($row['shift_date']);
        $weekStartDate = new DateTime($weekStart);
        $dayIndex = $shiftDate->diff($weekStartDate)->days;
        
        if ($dayIndex >= 0 && $dayIndex < 7) {
            // 格式化時間 (移除秒數)
            $startTime = substr($row['start_time'], 0, 5);
            $endTime = substr($row['end_time'], 0, 5);
            $employeeData[$empId]['shifts'][$dayIndex] = $startTime . '-' . $endTime;
        }
    }
    
    // 轉換為前端需要的格式
    $rows = array_values($employeeData);
    
    sendJSONSuccess(['rows' => $rows]);
    
} catch (PDOException $e) {
    error_log('Database error in week.php: ' . $e->getMessage());
    sendJSONError('資料庫錯誤', 500);
} catch (Exception $e) {
    error_log('Error in week.php: ' . $e->getMessage());
    sendJSONError('系統錯誤', 500);
}
?>