<?php
/**
 * 檔案位置: api/availability/weekly.php
 * POST /api/availability/weekly
 * 員工提交每週可排班時段
 */

require_once '../../config/database_config.php';

// 設定 headers
setCORSHeaders();
handleOptionsRequest();
setJSONHeaders();

// 啟動 session
session_start();

// 只接受 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONError('只接受 POST 請求', 405);
}

// 測試階段：使用固定員工 ID
// 正式環境應該從 SESSION 取得：$_SESSION['employee_id']
$employeeId = $_SESSION['employee_id'] ?? 1;

// 獲取 JSON 資料
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    sendJSONError('無效的 JSON 資料', 400);
}

// 驗證必要欄位
if (!isset($data['week_start']) || !isset($data['availability'])) {
    sendJSONError('缺少必要欄位: week_start 或 availability', 400);
}

$weekStart = $data['week_start'];
$availability = $data['availability'];

// 驗證日期格式
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStart)) {
    sendJSONError('日期格式錯誤，應為 YYYY-MM-DD', 400);
}

// 驗證 availability 是否為陣列
if (!is_array($availability)) {
    sendJSONError('availability 必須是物件', 400);
}

try {
    $pdo = getDBConnection();
    
    // 開始事務
    $pdo->beginTransaction();
    
    // 先刪除該員工該週的舊資料
    $deleteSql = "DELETE FROM employee_availability 
                  WHERE employee_id = :employee_id 
                  AND week_start_date = :week_start";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([
        'employee_id' => $employeeId,
        'week_start' => $weekStart
    ]);
    
    // 插入新資料
    $insertSql = "INSERT INTO employee_availability 
                  (employee_id, week_start_date, work_date, time_ranges, created_at, updated_at) 
                  VALUES 
                  (:employee_id, :week_start, :work_date, :time_ranges, NOW(), NOW())";
    $insertStmt = $pdo->prepare($insertSql);
    
    $insertCount = 0;
    $errors = [];
    
    foreach ($availability as $date => $ranges) {
        // 跳過空的時段
        if (empty($ranges) || !is_array($ranges)) {
            continue;
        }
        
        // 驗證日期格式
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors[] = "日期格式錯誤: $date";
            continue;
        }
        
        // 驗證日期是否在本週範圍內
        $dateTime = strtotime($date);
        $weekStartTime = strtotime($weekStart);
        $weekEndTime = strtotime($weekStart . ' +6 days');
        
        if ($dateTime < $weekStartTime || $dateTime > $weekEndTime) {
            $errors[] = "日期 $date 不在指定週範圍內";
            continue;
        }
        
        // 驗證每個時段
        $validRanges = [];
        foreach ($ranges as $range) {
            if (!isset($range['start']) || !isset($range['end'])) {
                continue;
            }
            
            // 驗證時間格式
            if (!preg_match('/^\d{2}:\d{2}$/', $range['start']) || 
                !preg_match('/^\d{2}:\d{2}$/', $range['end'])) {
                $errors[] = "時間格式錯誤: {$range['start']}-{$range['end']}";
                continue;
            }
            
            // 驗證結束時間晚於開始時間
            if ($range['start'] >= $range['end']) {
                $errors[] = "結束時間必須晚於開始時間: {$range['start']}-{$range['end']}";
                continue;
            }
            
            $validRanges[] = $range;
        }
        
        // 如果有有效的時段才插入
        if (!empty($validRanges)) {
            $timeRangesJson = json_encode($validRanges, JSON_UNESCAPED_UNICODE);
            
            $insertStmt->execute([
                'employee_id' => $employeeId,
                'week_start' => $weekStart,
                'work_date' => $date,
                'time_ranges' => $timeRangesJson
            ]);
            
            $insertCount++;
        }
    }
    
    // 提交事務
    $pdo->commit();
    
    $response = [
        'success' => true,
        'message' => "成功儲存 $insertCount 天的可排班時段",
        'employee_id' => $employeeId,
        'week_start' => $weekStart,
        'inserted_days' => $insertCount
    ];
    
    if (!empty($errors)) {
        $response['warnings'] = $errors;
    }
    
    sendJSONSuccess($response);
    
} catch (PDOException $e) {
    // 發生錯誤時回滾
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Database error in weekly.php: ' . $e->getMessage());
    sendJSONError('資料庫錯誤: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('Error in weekly.php: ' . $e->getMessage());
    sendJSONError('系統錯誤', 500);
}
?>