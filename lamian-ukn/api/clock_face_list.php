<?php
// /lamian-ukn/api/clock_face_list.php
require __DIR__.'/config.php';

function logError($msg, $data = null) {
    error_log("[clock_face_list] " . $msg . ($data ? " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) : ""));
}

try {
    $pdo = pdo();
    
    // 檢查表是否存在
    try {
        $sql = "SELECT 
                    ef.id,
                    ef.employee_id,
                    ef.face_image,
                    ef.created_at,
                    ef.is_active,
                    e.`name` AS emp_name,
                    e.`position` AS emp_position
                FROM `employee_faces` ef
                LEFT JOIN `員工基本資料` e ON ef.employee_id = e.`id`
                WHERE ef.is_active = 1
                ORDER BY ef.created_at DESC";
        
        $stmt = $pdo->query($sql);
        $faces = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logError("Query success", ['count' => count($faces)]);
        
        ok($faces);
        
    } catch (PDOException $e) {
        // 表不存在,返回空數組
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            logError("Table doesn't exist, returning empty array");
            ok([]);
        } else {
            throw $e;
        }
    }
    
} catch(Throwable $ex) {
    logError("=== Exception ===", [
        'message' => $ex->getMessage()
    ]);
    err('查詢失敗', 500, ['detail' => $ex->getMessage()]);
}