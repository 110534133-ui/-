<?php
// /lamian-ukn/api/face_api.php
// 整合的人臉識別 API - 統一入口
require __DIR__.'/config.php';

// 日誌函數
function logError($msg, $data = null) {
    $action = $_GET['action'] ?? 'unknown';
    error_log("[face_api:{$action}] " . $msg . ($data ? " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) : ""));
}

// 計算歐氏距離
function euclideanDistance($desc1, $desc2) {
    $sum = 0;
    for($i = 0; $i < count($desc1); $i++) {
        $diff = $desc1[$i] - $desc2[$i];
        $sum += $diff * $diff;
    }
    return sqrt($sum);
}

// 取得請求動作
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = pdo();
    
    switch($action) {
        
        // ==================== 人臉註冊 ====================
        case 'register':
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
            
            // 驗證每個 descriptor
            foreach($descriptors as $descriptor) {
                if(!is_array($descriptor) || count($descriptor) !== 128) {
                    logError("ERROR: Invalid descriptor format", ['count' => count($descriptor)]);
                    err('人臉特徵格式錯誤', 400);
                }
            }
            
            // 查詢員工
            $sqlEmp = "SELECT `id`, `name`, `position` FROM `員工基本資料` WHERE `id` = :code LIMIT 1";
            $st = $pdo->prepare($sqlEmp);
            $st->execute([':code' => $emp_code]);
            $emp = $st->fetch(PDO::FETCH_ASSOC);
            
            if(!$emp) {
                logError("ERROR: Employee not found", ['emp_code' => $emp_code]);
                err('找不到員工編號：' . $emp_code, 404);
            }
            
            logError("Found employee", $emp);
            
            // 計算平均 descriptor
            $avgDescriptor = array_fill(0, 128, 0);
            foreach($descriptors as $descriptor) {
                for($i = 0; $i < 128; $i++) {
                    $avgDescriptor[$i] += $descriptor[$i];
                }
            }
            for($i = 0; $i < 128; $i++) {
                $avgDescriptor[$i] /= count($descriptors);
            }
            
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
            break;
        
        // ==================== 人臉識別 ====================
        case 'recognize':
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $descriptor = $body['descriptor'] ?? [];
            
            if(empty($descriptor) || !is_array($descriptor) || count($descriptor) !== 128) {
                logError("ERROR: Invalid descriptor", ['count' => count($descriptor)]);
                err('人臉特徵資料無效', 400);
            }
            
            logError("=== 開始人臉識別 ===");
            
            // 取得所有已註冊的人臉資料
            $sql = "SELECT 
                        f.id,
                        f.user_id,
                        f.descriptor,
                        e.`name` AS emp_name,
                        e.`id` AS emp_code,
                        e.`position`
                    FROM `face_data` f
                    JOIN `員工基本資料` e ON f.user_id = e.`id`
                    ORDER BY f.updated_at DESC";
            
            $st = $pdo->query($sql);
            $faceData = $st->fetchAll(PDO::FETCH_ASSOC);
            
            if(empty($faceData)) {
                logError("WARNING: No face data in database");
                ok([
                    'success' => false,
                    'message' => '系統中尚無註冊的人臉資料'
                ]);
                exit;
            }
            
            logError("Found face data records", ['count' => count($faceData)]);
            
            // 比對每一筆人臉資料
            $bestMatch = null;
            $minDistance = PHP_FLOAT_MAX;
            $threshold = 0.6;
            
            foreach($faceData as $record) {
                $storedDescriptor = json_decode($record['descriptor'], true);
                
                if(!is_array($storedDescriptor) || count($storedDescriptor) !== 128) {
                    logError("WARNING: Invalid stored descriptor", ['user_id' => $record['user_id']]);
                    continue;
                }
                
                $distance = euclideanDistance($descriptor, $storedDescriptor);
                
                logError("Comparing with user", [
                    'user_id' => $record['user_id'],
                    'name' => $record['emp_name'],
                    'distance' => round($distance, 4)
                ]);
                
                if($distance < $minDistance) {
                    $minDistance = $distance;
                    $bestMatch = $record;
                }
            }
            
            // 判斷是否匹配成功
            if($bestMatch && $minDistance < $threshold) {
                logError("=== 識別成功 ===", [
                    'user_id' => $bestMatch['user_id'],
                    'name' => $bestMatch['emp_name'],
                    'distance' => round($minDistance, 4),
                    'threshold' => $threshold
                ]);
                
                ok([
                    'success' => true,
                    'employee' => [
                        'code' => $bestMatch['emp_code'],
                        'name' => $bestMatch['emp_name'],
                        'position' => $bestMatch['position']
                    ],
                    'confidence' => round((1 - $minDistance) * 100, 2)
                ]);
            } else {
                logError("=== 識別失敗 ===", [
                    'min_distance' => $minDistance,
                    'threshold' => $threshold,
                    'best_match_name' => $bestMatch ? $bestMatch['emp_name'] : 'none'
                ]);
                
                ok([
                    'success' => false,
                    'message' => '未識別到註冊的人臉'
                ]);
            }
            break;
        
        // ==================== 人臉打卡（含 token 驗證） ====================
case 'punch':
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    
    logError("=== 開始人臉打卡 ===");
    logError("Received data", $body);
    
    $emp_code     = trim($body['emp_code'] ?? '');
    $action_type  = strtolower(trim($body['action'] ?? ''));
    $descriptor   = $body['descriptor'] ?? [];
    $note         = trim($body['note'] ?? '');
    $device_token = trim($_SERVER['HTTP_X_DEVICE_TOKEN'] ?? '');

    if ($emp_code === '') err('員工編號不可為空', 400);
    if (!in_array($action_type, ['in','out'], true)) err('action must be in|out', 400);
    if (empty($descriptor) || !is_array($descriptor) || count($descriptor) !== 128)
        err('人臉特徵資料無效', 400);
    if ($device_token === '') err('裝置 token 缺失', 403);

   // 🔹 驗證裝置 token（必須綁定 B 級管理者）
$sqlToken = "SELECT id, name FROM `員工基本資料`
             WHERE role='B' AND device_token=:token
             LIMIT 1";
$stToken = $pdo->prepare($sqlToken);
$stToken->execute([':token' => $device_token]);
$empToken = $stToken->fetch(PDO::FETCH_ASSOC);

if (!$empToken) {
    logError("ERROR: Invalid device token", [
        'emp_code' => $emp_code,
        'token'    => $device_token
    ]);
    err('裝置授權失敗，請確認打卡裝置是否正確', 403);
}

    // 🔹 驗證人臉
    $sqlFace = "SELECT f.descriptor, e.name 
                FROM `face_data` f
                JOIN `員工基本資料` e ON f.user_id = e.id
                WHERE e.id = :code LIMIT 1";
    $stFace = $pdo->prepare($sqlFace);
    $stFace->execute([':code' => $emp_code]);
    $faceRecord = $stFace->fetch(PDO::FETCH_ASSOC);

    if (!$faceRecord) err('此員工尚未註冊人臉資料', 403);

    $storedDescriptor = json_decode($faceRecord['descriptor'], true);
    $distance = euclideanDistance($descriptor, $storedDescriptor);
    $threshold = 0.6;

    if ($distance >= $threshold) {
        logError("ERROR: Face verification failed", ['distance'=>$distance]);
        err('人臉驗證失敗,請重新識別', 403);
    }

    logError("Face verified", ['distance'=>$distance, 'emp_name'=>$faceRecord['name']]);

    // 🔹 查詢是否有未下班紀錄
    $sqlOpen = "SELECT * FROM `attendance`
                WHERE user_id = :uid AND clock_out IS NULL
                ORDER BY clock_in DESC LIMIT 1";
    $st2 = $pdo->prepare($sqlOpen);
    $st2->execute([':uid' => $emp_code]);
    $open = $st2->fetch(PDO::FETCH_ASSOC);

    if($action_type === 'in') {
        if($open) err('已有未下班紀錄,請先下班', 409);

        $sqlIns = "INSERT INTO `attendance` (user_id, clock_in, note)
                   VALUES (:uid, NOW(), :note)";
        $pdo->prepare($sqlIns)->execute([':uid'=>$emp_code, ':note'=>$note ?: '人臉識別上班']);
        $newId = $pdo->lastInsertId();

        logError("=== 上班打卡成功 ===", ['attendance_id'=>$newId]);
        ok(['ok'=>true, 'success'=>true, 'message'=>'上班打卡成功', 'emp'=>$empToken]);

    } else {
        if(!$open) err('找不到未下班紀錄,請先上班', 409);

        $sqlUpd = "UPDATE `attendance`
                   SET clock_out = NOW(),
                       hours = ROUND(TIMESTAMPDIFF(MINUTE, clock_in, NOW())/60,2),
                       status = CASE WHEN TIMESTAMPDIFF(MINUTE, clock_in, NOW()) > 480 THEN '加班' ELSE '正常' END,
                       note = COALESCE(NULLIF(:note, ''), note)
                   WHERE id = :id";
        $pdo->prepare($sqlUpd)->execute([':note'=>$note ?: '人臉識別下班', ':id'=>$open['id']]);

        logError("=== 下班打卡成功 ===", ['attendance_id'=>$open['id']]);
        ok(['ok'=>true, 'success'=>true, 'message'=>'下班打卡成功', 'emp'=>$empToken]);
    }
    break;

            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            
            logError("=== 開始人臉打卡 ===");
            logError("Received data", $body);
            
            $emp_code = trim($body['emp_code'] ?? '');
            $action_type = strtolower(trim($body['action'] ?? ''));
            $descriptor = $body['descriptor'] ?? [];
            $note = trim($body['note'] ?? '');
            
            if($emp_code === '') {
                logError("ERROR: emp_code is empty");
                err('員工編號不可為空', 400);
            }
            
            if(!in_array($action_type, ['in', 'out'], true)) {
                logError("ERROR: Invalid action", ['action' => $action_type]);
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
            
            if($action_type === 'in') {
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
            break;
        
        // ==================== 人臉資料清單 ====================
        case 'list':
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
            break;
        
        // ==================== 刪除人臉資料 ====================
        case 'delete':
            logError("=== 開始刪除人臉資料 ===");
            logError("Request method", ['method' => $_SERVER['REQUEST_METHOD']]);
            
            // 支援多種參數傳遞方式
            $user_id = '';
            $face_id = null;
            
            // 方式1: DELETE/GET 請求從 URL 參數讀取
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
            break;
        
        // ==================== 錯誤的動作 ====================
        default:
            logError("ERROR: Invalid action", ['action' => $action]);
            err('無效的操作:' . $action, 400);
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
    err('操作失敗', 500, ['detail' => $ex->getMessage()]);
}