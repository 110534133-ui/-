<?php
// /lamian-ukn/api/face_delete.php
require __DIR__.'/config.php';

function logError($msg, $data = null) {
    error_log("[face_delete] " . $msg . ($data ? " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) : ""));
}

try {
    $pdo = pdo();
    
    logError("=== 開始刪除人臉資料 ===");
    logError("Request method", ['method' => $_SERVER['REQUEST_METHOD']]);
    
    // 支援多種參數傳遞方式
    $user_id = '';
    $face_id = null;
    
    // 方式1: DELETE/GET 請求從 URL 參數讀取 (id 或 user_id)
    if(isset($_GET['id'])) {
        $face_id = (int)$_GET['id'];
        logError("Using face_data.id from GET", ['id' => $face_id]);
    } elseif(isset($_GET['user_id'])) {
        $user_id = trim($_GET['user_id']);
        logError("Using user_id from GET", ['user_id' => $user_id]);
    } else {
        // 方式2: POST 請求從 JSON body 讀取
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        logError("Received body data", $body);
        
        if(isset($body['id'])) {
            $face_id = (int)$body['id'];
            logError("Using face_data.id from body", ['id' => $face_id]);
        } elseif(isset($body['user_id'])) {
            $user_id = trim($body['user_id']);
            logError("Using user_id from body", ['user_id' => $user_id]);
        }
    }
    
    // 驗證參數
    if($face_id === null && $user_id === '') {
        logError("ERROR: No valid parameter provided");
        err('缺少必要參數:需要 id 或 user_id', 400);
    }
    
    // 根據不同參數構建查詢
    if($face_id !== null) {
        // 使用 face_data.id 查詢
        $checkSql = "SELECT f.id, f.user_id, e.`name` 
                     FROM `face_data` f
                     LEFT JOIN `員工基本資料` e ON f.user_id = e.`id`
                     WHERE f.id = :id 
                     LIMIT 1";
        $checkSt = $pdo->prepare($checkSql);
        $checkSt->execute([':id' => $face_id]);
        $record = $checkSt->fetch(PDO::FETCH_ASSOC);
        
        if(!$record) {
            logError("ERROR: Face data not found", ['face_id' => $face_id]);
            err('找不到此人臉記錄', 404);
        }
        
        logError("Found record to delete", $record);
        
        // 執行刪除
        $deleteSql = "DELETE FROM `face_data` WHERE id = :id";
        $deleteSt = $pdo->prepare($deleteSql);
        $result = $deleteSt->execute([':id' => $face_id]);
        
    } else {
        // 使用 user_id 查詢
        $checkSql = "SELECT f.id, e.`name` 
                     FROM `face_data` f
                     LEFT JOIN `員工基本資料` e ON f.user_id = e.`id`
                     WHERE f.user_id = :uid 
                     LIMIT 1";
        $checkSt = $pdo->prepare($checkSql);
        $checkSt->execute([':uid' => $user_id]);
        $record = $checkSt->fetch(PDO::FETCH_ASSOC);
        
        if(!$record) {
            logError("ERROR: Face data not found", ['user_id' => $user_id]);
            err('找不到此員工的人臉資料', 404);
        }
        
        logError("Found record to delete", $record);
        
        // 執行刪除
        $deleteSql = "DELETE FROM `face_data` WHERE user_id = :uid";
        $deleteSt = $pdo->prepare($deleteSql);
        $result = $deleteSt->execute([':uid' => $user_id]);
    }
    
    if(!$result) {
        $errorInfo = $deleteSt->errorInfo();
        logError("ERROR: Delete failed", $errorInfo);
        err('刪除失敗', 500, ['error' => $errorInfo]);
    }
    
    $affectedRows = $deleteSt->rowCount();
    
    if($affectedRows === 0) {
        logError("WARNING: No rows deleted");
        err('刪除失敗:沒有符合的記錄', 404);
    }
    
    logError("=== 刪除成功 ===", [
        'face_id' => $face_id,
        'user_id' => $record['user_id'] ?? $user_id,
        'emp_name' => $record['name'],
        'affected_rows' => $affectedRows
    ]);
    
    ok([
        'success' => true,
        'message' => '人臉資料已刪除',
        'deleted_id' => $face_id,
        'deleted_user_id' => $record['user_id'] ?? $user_id,
        'emp_name' => $record['name']
    ]);
    
} catch(PDOException $ex) {
    logError("=== PDO Exception ===", [
        'message' => $ex->getMessage(),
        'code' => $ex->getCode()
    ]);
    err('資料庫操作失敗', 500, ['detail' => $ex->getMessage()]);
    
} catch(Throwable $ex) {
    logError("=== General Exception ===", [
        'message' => $ex->getMessage()
    ]);
    err('刪除失敗', 500, ['detail' => $ex->getMessage()]);
}
