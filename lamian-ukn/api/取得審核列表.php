<?php
// 🔥 修正：路徑
require_once __DIR__ . '/db_config.php';

try {
    $pdo = getDbConnection();
    
    // ( ... 以下 SQL 查詢 ... )
    $sql = "SELECT 
                ls.request_id as id,
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
            WHERE ls.status = 1
            ORDER BY ls.request_id DESC";
    
    $stmt = $pdo->query($sql);
    $records = $stmt->fetchAll();
    
    foreach ($records as &$record) {
        if (empty($record['type'])) {
            $record['type'] = '未知';
        }
        if (!empty($record['proof'])) {
            $record['photo'] = 'uploads/leave/' . $record['proof'];
        } else {
            $record['photo'] = null;
        }
        unset($record['proof']);
        $record['status'] = intval($record['status']);
        if (empty($record['reason'])) {
            $record['reason'] = '-';
        }
    }
    
    echo json_encode($records, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error in 取得審核列表.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => '載入失敗'], JSON_UNESCAPED_UNICODE);
}
?>