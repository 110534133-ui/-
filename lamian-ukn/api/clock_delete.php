<?php
// /lamian-ukn/api/clock_delete.php (修正版)
require __DIR__.'/config.php';

function logError($msg, $data = null) {
    error_log("[clock_delete] " . $msg . ($data ? " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) : ""));
}

try {
    $pdo = pdo();
    
    // 取得要刪除的 ID
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$id) {
        logError("ERROR: Missing ID parameter");
        err('缺少必要參數：id', 400);
    }
    
    logError("=== 開始刪除操作 ===", ['id' => $id]);
    
    // 先檢查記錄是否存在
    $checkSql = "SELECT id, user_id, clock_in FROM `attendance` WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        logError("ERROR: Record not found", ['id' => $id]);
        err('找不到此打卡記錄', 404);
    }
    
    logError("Found record to delete", $record);
    
    // 執行刪除
    $deleteSql = "DELETE FROM `attendance` WHERE id = :id";
    $deleteStmt = $pdo->prepare($deleteSql);
    $result = $deleteStmt->execute([':id' => $id]);
    
    if (!$result) {
        $errorInfo = $deleteStmt->errorInfo();
        logError("ERROR: Delete failed", $errorInfo);
        err('刪除失敗', 500, ['error' => $errorInfo]);
    }
    
    $affectedRows = $deleteStmt->rowCount();
    
    if ($affectedRows === 0) {
        logError("WARNING: No rows deleted", ['id' => $id]);
        err('刪除失敗：沒有符合的記錄', 404);
    }
    
    logError("=== 刪除成功 ===", ['id' => $id, 'affected_rows' => $affectedRows]);
    
    ok([
        'success' => true,
        'ok' => true,
        'message' => '打卡記錄已刪除',
        'deleted_id' => $id
    ]);
    
} catch(PDOException $ex) {
    logError("=== PDO Exception ===", [
        'message' => $ex->getMessage(),
        'code' => $ex->getCode(),
        'file' => $ex->getFile(),
        'line' => $ex->getLine()
    ]);
    err('資料庫操作失敗', 500, ['detail' => $ex->getMessage()]);
    
} catch(Throwable $ex) {
    logError("=== General Exception ===", [
        'message' => $ex->getMessage(),
        'file' => $ex->getFile(),
        'line' => $ex->getLine()
    ]);
    err('刪除操作失敗', 500, ['detail' => $ex->getMessage()]);
}