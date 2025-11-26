<?php
// /lamian-ukn/api/face_list.php
require __DIR__.'/config.php';

function logError($msg, $data = null) {
    error_log("[face_list] " . $msg . ($data ? " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) : ""));
}

try {
    $pdo = pdo();
    
    logError("=== 查詢人臉資料清單 ===");
    
    // 查詢所有已註冊人臉的員工
    $sql = "SELECT 
                f.id,
                f.user_id,
                f.created_at,
                f.updated_at,
                e.`name` AS emp_name,
                e.`position` AS emp_position,
                e.`role` AS emp_role,
                e.`avatar_url`
            FROM `face_data` f
            LEFT JOIN `員工基本資料` e ON f.user_id = e.`id`
            ORDER BY f.updated_at DESC";
    
    $st = $pdo->query($sql);
    $faceData = $st->fetchAll(PDO::FETCH_ASSOC);
    
    logError("Found face data records", ['count' => count($faceData)]);
    
    // 計算統計資料
    $totalEmployees = (int)$pdo->query("SELECT COUNT(*) FROM `員工基本資料`")->fetchColumn();
    $registeredCount = count($faceData);
    $registrationRate = $totalEmployees > 0 
        ? round(($registeredCount / $totalEmployees) * 100, 1) . '%' 
        : '0%';
    
    $stats = [
        'total_employees' => $totalEmployees,
        'registered_count' => $registeredCount,
        'registration_rate' => $registrationRate
    ];
    
    logError("Statistics", $stats);
    
    ok([
        'success' => true,
        'face_data' => $faceData,
        'stats' => $stats
    ]);
    
} catch(PDOException $ex) {
    logError("=== PDO Exception ===", [
        'message' => $ex->getMessage(),
        'code' => $ex->getCode()
    ]);
    err('資料庫查詢失敗', 500, ['detail' => $ex->getMessage()]);
    
} catch(Throwable $ex) {
    logError("=== General Exception ===", [
        'message' => $ex->getMessage()
    ]);
    err('查詢失敗', 500, ['detail' => $ex->getMessage()]);
}
