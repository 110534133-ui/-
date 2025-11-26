<?php
/**
 * API: 取得所有員工請假記錄
 * Method: GET
 * Response: JSON array
 */

require_once 'db_config.php';

try {
    $pdo = getDbConnection();
    
    // 查詢所有請假記錄 (JOIN 假別資料表)
    $sql = "SELECT 
                ls.request_id,
                ls.name as employee,
                lt.name as type,
                DATE_FORMAT(ls.start_date, '%Y-%m-%d') as start,
                DATE_FORMAT(ls.end_date, '%Y-%m-%d') as end,
                ls.total_days,
                ls.reason,
                ls.proof,
                ls.status
            FROM leave_system ls
            LEFT JOIN 假別 lt ON ls.leave_type_id = lt.id
            ORDER BY ls.request_id DESC";
    
    $stmt = $pdo->query($sql);
    $records = $stmt->fetchAll();
    
    // 處理資料
    foreach ($records as &$record) {
        // 如果 JOIN 失敗，給預設值
        if (empty($record['type'])) {
            $record['type'] = '未知';
        }
        
        // 確保 status 是整數
        $record['status'] = intval($record['status']);
        
        // 如果 reason 為空，給預設值
        if (empty($record['reason'])) {
            $record['reason'] = '-';
        }
    }
    
    echo json_encode($records, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error in 整合email.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => '載入失敗'], JSON_UNESCAPED_UNICODE);
}
?>