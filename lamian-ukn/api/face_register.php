<?php
// /lamian-ukn/api/face_register.php
require __DIR__.'/config.php';

function logError($msg, $data = null) {
    error_log("[face_register] " . $msg . ($data ? " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) : ""));
}

try {
    $pdo = pdo();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    
    logError("=== 開始人臉註冊 ===");
    logError("Received data", ['emp_code' => $body['emp_code'] ?? null, 'descriptors_count' => count($body['descriptors'] ?? [])]);
    
    $emp_code = trim($body['emp_code'] ?? '');
    $descriptors = $body['descriptors'] ?? [];
    
    if($emp_code === '') {
        logError("ERROR: emp_code is empty");
        err('員工編號不可為空', 400);
    }
    
    if(empty($descriptors) || !is_array($descriptors)) {
        logError("ERROR: Invalid descriptors");
        err('人臉特徵資料無效', 400);
    }
    
    // 驗證每個 descriptor 都是陣列且長度正確 (face-api.js 使用 128 維向量)
    foreach($descriptors as $descriptor) {
        if(!is_array($descriptor) || count($descriptor) !== 128) {
            logError("ERROR: Invalid descriptor format", ['count' => count($descriptor)]);
            err('人臉特徵格式錯誤', 400);
        }
    }
    
    // 查詢員工
    $sqlEmp = "SELECT `id`, `name`, `position` FROM `員工基本資料` WHERE `id` = :code LIMIT 1";
    logError("Executing employee query", ['sql' => $sqlEmp, 'code' => $emp_code]);
    
    $st = $pdo->prepare($sqlEmp);
    $st->execute([':code' => $emp_code]);
    $emp = $st->fetch(PDO::FETCH_ASSOC);
    
    if(!$emp) {
        logError("ERROR: Employee not found", ['emp_code' => $emp_code]);
        err('找不到員工編號：' . $emp_code, 404);
    }
    
    logError("Found employee", $emp);
    
    // 計算平均 descriptor (多次擷取的平均值可提高準確度)
    $avgDescriptor = array_fill(0, 128, 0);
    foreach($descriptors as $descriptor) {
        for($i = 0; $i < 128; $i++) {
            $avgDescriptor[$i] += $descriptor[$i];
        }
    }
    for($i = 0; $i < 128; $i++) {
        $avgDescriptor[$i] /= count($descriptors);
    }
    
    // 將 descriptor 轉為 JSON 儲存
    $descriptorJson = json_encode($avgDescriptor);
    
    // 檢查是否已有人臉資料
    $checkSql = "SELECT id FROM `face_data` WHERE user_id = :uid LIMIT 1";
    $checkSt = $pdo->prepare($checkSql);
    $checkSt->execute([':uid' => $emp['id']]);
    $existing = $checkSt->fetch();
    
    if($existing) {
        // 更新現有資料
        logError("=== UPDATE existing face data ===", ['face_data_id' => $existing['id']]);
        
        $updateSql = "UPDATE `face_data` 
                      SET descriptor = :desc, updated_at = NOW() 
                      WHERE user_id = :uid";
        $updateSt = $pdo->prepare($updateSql);
        $result = $updateSt->execute([
            ':desc' => $descriptorJson,
            ':uid' => $emp['id']
        ]);
        
        if(!$result) {
            logError("ERROR: Update failed", $updateSt->errorInfo());
            err('更新人臉資料失敗', 500);
        }
        
        logError("=== 更新成功 ===");
        $message = '人臉資料已更新';
        
    } else {
        // 新增資料
        logError("=== INSERT new face data ===");
        
        $insertSql = "INSERT INTO `face_data` (user_id, descriptor, created_at, updated_at) 
                      VALUES (:uid, :desc, NOW(), NOW())";
        $insertSt = $pdo->prepare($insertSql);
        $result = $insertSt->execute([
            ':uid' => $emp['id'],
            ':desc' => $descriptorJson
        ]);
        
        if(!$result) {
            logError("ERROR: Insert failed", $insertSt->errorInfo());
            err('儲存人臉資料失敗', 500);
        }
        
        logError("=== 註冊成功 ===", ['new_id' => $pdo->lastInsertId()]);
        $message = '人臉註冊成功';
    }
    
    ok([
        'success' => true,
        'message' => $message,
        'employee' => [
            'code' => $emp['id'],
            'name' => $emp['name'],
            'position' => $emp['position']
        ]
    ]);
    
} catch(PDOException $ex) {
    logError("=== PDO Exception ===", [
        'message' => $ex->getMessage(),
        'code' => $ex->getCode()
    ]);
    err('資料庫操作失敗', 500, ['detail' => $ex->getMessage()]);
    
} catch(Throwable $ex) {
    logError("=== General Exception ===", [
        'message' => $ex->getMessage(),
        'trace' => $ex->getTraceAsString()
    ]);
    err('註冊失敗', 500, ['detail' => $ex->getMessage()]);
}
