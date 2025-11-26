<?php
// /lamian-ukn/api/face_punch.php
require __DIR__.'/config.php';

function logError($msg, $data = null) {
    error_log("[face_punch] " . $msg . ($data ? " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) : ""));
}

try {
    $pdo = pdo();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    
    logError("=== 開始人臉打卡 ===");
    logError("Received data", $body);
    
    $emp_code = trim($body['emp_code'] ?? '');
    $action = strtolower(trim($body['action'] ?? ''));
    $descriptor = $body['descriptor'] ?? [];
    $note = trim($body['note'] ?? '');
    
    if($emp_code === '') {
        logError("ERROR: emp_code is empty");
        err('員工編號不可為空', 400);
    }
    
    if(!in_array($action, ['in', 'out'], true)) {
        logError("ERROR: Invalid action", ['action' => $action]);
        err('action must be in|out', 400);
    }
    
    if(empty($descriptor) || !is_array($descriptor) || count($descriptor) !== 128) {
        logError("ERROR: Invalid descriptor");
        err('人臉特徵資料無效', 400);
    }
    
    // 再次驗證人臉 (防止前端偽造)
    $sqlFace = "SELECT f.descriptor, e.`name` 
                FROM `face_data` f
                JOIN `員工基本資料` e ON f.user_id = e.`id`
                WHERE e.`id` = :code
                LIMIT 1";
    $stFace = $pdo->prepare($sqlFace);
    $stFace->execute([':code' => $emp_code]);
    $faceRecord = $stFace->fetch(PDO::FETCH_ASSOC);
    
    if(!$faceRecord) {
        logError("ERROR: No face data for this employee", ['emp_code' => $emp_code]);
        err('此員工尚未註冊人臉資料', 403);
    }
    
    $storedDescriptor = json_decode($faceRecord['descriptor'], true);
    
    // 計算距離驗證
    function euclideanDistance($desc1, $desc2) {
        $sum = 0;
        for($i = 0; $i < count($desc1); $i++) {
            $diff = $desc1[$i] - $desc2[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }
    
    $distance = euclideanDistance($descriptor, $storedDescriptor);
    $threshold = 0.6;
    
    if($distance >= $threshold) {
        logError("ERROR: Face verification failed", [
            'distance' => $distance,
            'threshold' => $threshold
        ]);
        err('人臉驗證失敗,請重新識別', 403);
    }
    
    logError("Face verified", ['distance' => $distance, 'emp_name' => $faceRecord['name']]);
    
    // 查詢員工完整資訊
    $sqlEmp = "SELECT `id`, `name`, `position` FROM `員工基本資料` WHERE `id` = :c LIMIT 1";
    $st = $pdo->prepare($sqlEmp);
    $st->execute([':c' => $emp_code]);
    $emp = $st->fetch(PDO::FETCH_ASSOC);
    
    if(!$emp) {
        logError("ERROR: Employee not found", ['emp_code' => $emp_code]);
        err('找不到此員工', 404);
    }
    
    // 查詢是否有未下班紀錄
    $sqlOpen = "SELECT * FROM `attendance`
                WHERE user_id = :uid AND clock_out IS NULL
                ORDER BY clock_in DESC LIMIT 1";
    $st2 = $pdo->prepare($sqlOpen);
    $st2->execute([':uid' => $emp['id']]);
    $open = $st2->fetch(PDO::FETCH_ASSOC);
    
    if($action === 'in') {
        // 上班打卡
        if($open) {
            logError("ERROR: Already clocked in", ['open_record_id' => $open['id']]);
            err('已有未下班紀錄,請先下班', 409);
        }
        
        $sqlIns = "INSERT INTO `attendance` (user_id, clock_in, note)
                   VALUES (:uid, NOW(), :note)";
        $pdo->prepare($sqlIns)->execute([
            ':uid' => $emp['id'],
            ':note' => $note ?: '人臉識別上班'
        ]);
        
        $newId = $pdo->lastInsertId();
        logError("=== 上班打卡成功 ===", ['attendance_id' => $newId]);
        
        ok([
            'ok' => true,
            'success' => true,
            'message' => '上班打卡成功',
            'emp' => $emp
        ]);
        
    } else {
        // 下班打卡
        if(!$open) {
            logError("ERROR: No open record found");
            err('找不到未下班紀錄,請先上班', 409);
        }
        
        $sqlUpd = "UPDATE `attendance`
                   SET clock_out = NOW(),
                       hours = ROUND(TIMESTAMPDIFF(MINUTE, clock_in, NOW())/60, 2),
                       status = CASE
                         WHEN TIMESTAMPDIFF(MINUTE, clock_in, NOW()) > 480 THEN '加班'
                         ELSE '正常'
                       END,
                       note = COALESCE(NULLIF(:note, ''), note)
                   WHERE id = :id";
        $pdo->prepare($sqlUpd)->execute([
            ':note' => $note ?: '人臉識別下班',
            ':id' => $open['id']
        ]);
        
        logError("=== 下班打卡成功 ===", ['attendance_id' => $open['id']]);
        
        ok([
            'ok' => true,
            'success' => true,
            'message' => '下班打卡成功',
            'emp' => $emp
        ]);
    }
    
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
    err('打卡失敗', 500, ['detail' => $ex->getMessage()]);
}
