<?php
// /lamian-ukn/api/face_recognize.php
require __DIR__.'/config.php';

function logError($msg, $data = null) {
    error_log("[face_recognize] " . $msg . ($data ? " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) : ""));
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

try {
    $pdo = pdo();
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
    $threshold = 0.6; // 閾值,距離小於此值才算匹配
    
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
            'confidence' => round((1 - $minDistance) * 100, 2) // 信心度百分比
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
    err('識別失敗', 500, ['detail' => $ex->getMessage()]);
}
