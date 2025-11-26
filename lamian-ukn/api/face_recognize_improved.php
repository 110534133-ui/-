<?php
// /lamian-ukn/api/face_recognize_improved.php
// 改進版人臉識別 API - 更寬鬆的閾值和更好的調試資訊
require __DIR__.'/config.php';

function logError($msg, $data = null) {
    error_log("[face_recognize_improved] " . $msg . ($data ? " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) : ""));
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

// 計算餘弦相似度 (另一種比對方法)
function cosineSimilarity($desc1, $desc2) {
    $dotProduct = 0;
    $mag1 = 0;
    $mag2 = 0;
    
    for($i = 0; $i < count($desc1); $i++) {
        $dotProduct += $desc1[$i] * $desc2[$i];
        $mag1 += $desc1[$i] * $desc1[$i];
        $mag2 += $desc2[$i] * $desc2[$i];
    }
    
    $mag1 = sqrt($mag1);
    $mag2 = sqrt($mag2);
    
    if($mag1 == 0 || $mag2 == 0) return 0;
    
    return $dotProduct / ($mag1 * $mag2);
}

try {
    $pdo = pdo();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    
    $descriptor = $body['descriptor'] ?? [];
    
    if(empty($descriptor) || !is_array($descriptor) || count($descriptor) !== 128) {
        logError("ERROR: Invalid descriptor", ['count' => count($descriptor)]);
        err('人臉特徵資料無效', 400);
    }
    
    logError("=== 開始人臉識別 (改進版) ===");
    
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
    $maxSimilarity = -1;
    
    // 改進的閾值設定
    $distanceThreshold = 0.8;  // 從 0.6 提高到 0.75 (更寬鬆)
    $similarityThreshold = 0.40; // 餘弦相似度閾值
    
    $allMatches = []; // 記錄所有比對結果
    
    foreach($faceData as $record) {
        $storedDescriptor = json_decode($record['descriptor'], true);
        
        if(!is_array($storedDescriptor) || count($storedDescriptor) !== 128) {
            logError("WARNING: Invalid stored descriptor", ['user_id' => $record['user_id']]);
            continue;
        }
        
        $distance = euclideanDistance($descriptor, $storedDescriptor);
        $similarity = cosineSimilarity($descriptor, $storedDescriptor);
        
        $matchInfo = [
            'user_id' => $record['user_id'],
            'name' => $record['emp_name'],
            'distance' => round($distance, 4),
            'similarity' => round($similarity, 4),
            'distance_pass' => $distance < $distanceThreshold,
            'similarity_pass' => $similarity > $similarityThreshold
        ];
        
        $allMatches[] = $matchInfo;
        
        logError("Comparing with user", $matchInfo);
        
        // 綜合評分: 使用歐氏距離和餘弦相似度
        $score = (1 - min($distance, 1)) * 0.6 + $similarity * 0.4;
        
        if($distance < $minDistance) {
            $minDistance = $distance;
            $maxSimilarity = $similarity;
            $bestMatch = $record;
            $bestMatch['match_score'] = $score;
        }
    }
    
    // 判斷是否匹配成功 - 使用更寬鬆的條件
    $isMatch = false;
    
    if($bestMatch) {
        // 方法1: 歐氏距離檢查
        $distanceOk = $minDistance < $distanceThreshold;
        
        // 方法2: 餘弦相似度檢查  
        $similarityOk = $maxSimilarity > $similarityThreshold;
        
        // 只要其中一個方法通過就算匹配
        $isMatch = $distanceOk || $similarityOk;
        
        logError("Match evaluation", [
            'distance' => round($minDistance, 4),
            'distance_threshold' => $distanceThreshold,
            'distance_ok' => $distanceOk,
            'similarity' => round($maxSimilarity, 4),
            'similarity_threshold' => $similarityThreshold,
            'similarity_ok' => $similarityOk,
            'final_match' => $isMatch
        ]);
    }
    
    if($isMatch) {
        logError("=== 識別成功 ===", [
            'user_id' => $bestMatch['user_id'],
            'name' => $bestMatch['emp_name'],
            'distance' => round($minDistance, 4),
            'similarity' => round($maxSimilarity, 4),
            'match_score' => round($bestMatch['match_score'], 4)
        ]);
        
        ok([
            'success' => true,
            'employee' => [
                'code' => $bestMatch['emp_code'],
                'name' => $bestMatch['emp_name'],
                'position' => $bestMatch['position']
            ],
            'confidence' => round($bestMatch['match_score'] * 100, 2),
            'debug_info' => [
                'distance' => round($minDistance, 4),
                'distance_threshold' => $distanceThreshold,
                'similarity' => round($maxSimilarity, 4),
                'similarity_threshold' => $similarityThreshold,
                'all_matches' => $allMatches
            ]
        ]);
    } else {
        logError("=== 識別失敗 ===", [
            'min_distance' => round($minDistance, 4),
            'distance_threshold' => $distanceThreshold,
            'max_similarity' => round($maxSimilarity, 4),
            'similarity_threshold' => $similarityThreshold,
            'best_match_name' => $bestMatch ? $bestMatch['emp_name'] : 'none'
        ]);
        
        ok([
            'success' => false,
            'message' => '未識別到註冊的人臉',
            'debug_info' => [
                'closest_match' => $bestMatch ? [
                    'name' => $bestMatch['emp_name'],
                    'distance' => round($minDistance, 4),
                    'similarity' => round($maxSimilarity, 4),
                    'reason' => $minDistance >= $distanceThreshold ? 
                        "距離過大 ({$minDistance} >= {$distanceThreshold})" : 
                        "相似度過低 ({$maxSimilarity} <= {$similarityThreshold})"
                ] : null,
                'all_matches' => $allMatches,
                'suggestion' => '請嘗試: 1) 調整光線 2) 正對鏡頭 3) 重新註冊人臉'
            ]
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