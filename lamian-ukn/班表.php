<?php
// 🔥 最終修正版 - 班表.php
// 針對用戶 C130015 (aaa) 的環境優化

session_start();
header('Content-Type: application/json; charset=utf-8');

// ===== 開啟錯誤日誌 =====
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/班表_debug.log');

// 記錄開始
error_log("=== 班表.php 開始執行 === " . date('Y-m-d H:i:s'));

// ===== 資料庫連線 =====
$host = 'localhost';
$db   = 'lamian';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    error_log("✅ 資料庫連線成功");
} catch (\PDOException $e) {
    error_log("❌ 資料庫連線失敗: " . $e->getMessage());
    echo json_encode(['success'=>false, 'error'=>'資料庫連線失敗'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 確認登入 =====
if (!isset($_SESSION['uid']) || empty($_SESSION['uid'])) {
    error_log("❌ 未登入,SESSION: " . json_encode($_SESSION));
    echo json_encode(['success'=>false, 'error'=>'未登入,請重新登入'], JSON_UNESCAPED_UNICODE);
    exit;
}

$currentUserId = $_SESSION['uid'];
$currentUserName = $_SESSION['name'] ?? '未知用戶';

error_log("✅ 登入用戶: ID={$currentUserId}, Name={$currentUserName}");

// ===== 驗證用戶存在 =====
try {
    $stmtCheck = $pdo->prepare("SELECT id, name FROM 員工基本資料 WHERE id = ?");
    $stmtCheck->execute([$currentUserId]);
    $userExists = $stmtCheck->fetch();
    
    if (!$userExists) {
        error_log("❌ 用戶ID不存在於資料庫: {$currentUserId}");
        echo json_encode(['success'=>false, 'error'=>'用戶資料不存在'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    error_log("✅ 用戶驗證成功: {$userExists['name']}");
} catch (Exception $e) {
    error_log("❌ 用戶驗證失敗: " . $e->getMessage());
    echo json_encode(['success'=>false, 'error'=>'用戶驗證失敗'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== Helper 函數 =====
function getJsonPost() {
    $data = file_get_contents('php://input');
    error_log("📨 收到POST資料: " . substr($data, 0, 500)); // 只記錄前500字元
    return json_decode($data, true);
}

// ===== GET: 取得某週班表 =====
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $start = $_GET['start'] ?? null;
    
    error_log("📥 GET請求 - start: {$start}");
    
    if (!$start) {
        echo json_encode(['rows'=>[]], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $end = date('Y-m-d', strtotime($start . ' +6 days'));
    error_log("📅 查詢日期範圍: {$start} ~ {$end}");

    $stmt = $pdo->prepare("
        SELECT b.*, u.name, u.id as employee_id
        FROM 班表 b
        LEFT JOIN 員工基本資料 u ON b.user_id = u.id
        WHERE b.work_date BETWEEN ? AND ?
        ORDER BY u.name, b.work_date, b.start_time
    ");
    
    $stmt->execute([$start, $end]);
    $rows = $stmt->fetchAll();
    
    error_log("📊 查詢到 " . count($rows) . " 筆班表資料");

    // 重組資料: 以員工為主
    $data = [];
    foreach ($rows as $r) {
        $uid = $r['user_id'];
        $date = $r['work_date'];
        
        // 組合班次字串
        $shiftStr = '';
        if ($r['start_time'] && $r['end_time']) {
            $shiftStr = substr($r['start_time'], 0, 5) . '~' . substr($r['end_time'], 0, 5);
            if (!empty($r['note'])) {
                $shiftStr .= ' (' . $r['note'] . ')';
            }
        }
        
        // 初始化員工資料
        if (!isset($data[$uid])) {
            $data[$uid] = [
                'name' => $r['name'] ?? '未知員工',
                'shifts' => array_fill(0, 7, []),
            ];
        }
        
        // 計算是週幾
        $dayOfWeek = date('N', strtotime($date)); // 1=週一, 7=週日
        $dayIndex = ($dayOfWeek - 1) % 7;
        
        if ($shiftStr) {
            $data[$uid]['shifts'][$dayIndex][] = $shiftStr;
        }
    }

    echo json_encode(['rows' => array_values($data)], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== POST: 儲存填報班表 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post = getJsonPost();
    
    if (!$post) {
        error_log("❌ 無法解析POST資料");
        echo json_encode(['success'=>false, 'error'=>'無法解析請求資料'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $weekStart = $post['week_start'] ?? null;
    $availability = $post['availability'] ?? null;

    error_log("📝 POST請求 - week_start: {$weekStart}, 用戶: {$currentUserName}({$currentUserId})");

    if (!$weekStart || !is_array($availability)) {
        error_log("❌ 缺少必要參數 - week_start或availability");
        echo json_encode([
            'success'=>false, 
            'error'=>'缺少必要參數'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->beginTransaction();
    
    try {
        // 1. 刪除該週的舊資料
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
        
        error_log("🗑️ 準備刪除: {$weekStart} ~ {$weekEnd}, user_id: {$currentUserId}");
        
        $stmtDelete = $pdo->prepare("
            DELETE FROM 班表 
            WHERE user_id = ? 
            AND work_date BETWEEN ? AND ?
        ");
        $stmtDelete->execute([$currentUserId, $weekStart, $weekEnd]);
        
        $deletedRows = $stmtDelete->rowCount();
        error_log("✅ 刪除了 {$deletedRows} 筆舊資料");
        
        // 2. 插入新資料
        // 🔥 重要: shift_type 欄位為 NOT NULL,必須提供值
        $stmtInsert = $pdo->prepare("
            INSERT INTO 班表 (user_id, work_date, start_time, end_time, shift_type, note)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $insertCount = 0;
        $errorCount = 0;
        
        foreach ($availability as $date => $ranges) {
            if (empty($ranges) || !is_array($ranges)) {
                error_log("⚠️ 日期 {$date} 沒有時段資料,跳過");
                continue;
            }
            
            foreach ($ranges as $idx => $r) {
                // 驗證必要欄位
                if (empty($r['start']) || empty($r['end'])) {
                    error_log("⚠️ 略過無效時段: date={$date}, index={$idx}, data=" . json_encode($r));
                    $errorCount++;
                    continue;
                }
                
                // 確保時間格式正確
                $startTime = substr($r['start'], 0, 5);
                $endTime = substr($r['end'], 0, 5);
                
                // 驗證時間格式
                if (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
                    error_log("⚠️ 時間格式錯誤: start={$startTime}, end={$endTime}");
                    $errorCount++;
                    continue;
                }
                
                // 🔥 自動判斷班次類型
                $startHour = (int)substr($startTime, 0, 2);
                $shiftType = '正常班'; // 預設值
                
                if ($startHour >= 6 && $startHour < 14) {
                    $shiftType = '早班';
                } elseif ($startHour >= 14 && $startHour < 22) {
                    $shiftType = '晚班';
                } elseif ($startHour >= 22 || $startHour < 6) {
                    $shiftType = '大夜班';
                }
                
                $note = $r['note'] ?? '';
                
                error_log("➕ 準備插入: user_id={$currentUserId}, date={$date}, time={$startTime}~{$endTime}, type={$shiftType}");
                
                try {
                    $stmtInsert->execute([
                        $currentUserId,
                        $date,
                        $startTime,
                        $endTime,
                        $shiftType,
                        $note
                    ]);
                    $insertCount++;
                    error_log("  ✅ 插入成功 (第 {$insertCount} 筆)");
                } catch (PDOException $e) {
                    error_log("  ❌ 插入失敗: " . $e->getMessage());
                    $errorCount++;
                }
            }
        }
        
        $pdo->commit();
        
        error_log("✅✅✅ 班表儲存完成: 用戶={$currentUserName}, 成功={$insertCount}筆, 失敗={$errorCount}筆");
        
        $message = "班表儲存成功! 共新增 {$insertCount} 筆資料";
        if ($errorCount > 0) {
            $message .= " (跳過 {$errorCount} 筆無效資料)";
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'inserted' => $insertCount,
            'skipped' => $errorCount,
            'user_id' => $currentUserId,
            'user_name' => $currentUserName
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("❌❌❌ 班表儲存失敗: " . $e->getMessage());
        error_log("錯誤詳情: " . $e->getTraceAsString());
        
        echo json_encode([
            'success' => false, 
            'error' => '儲存失敗: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    exit;
}

// ===== 其他方法 =====
error_log("⚠️ 不支援的請求方法: " . $_SERVER['REQUEST_METHOD']);
http_response_code(405);
echo json_encode([
    'success' => false, 
    'error' => '不支援的請求方法'
], JSON_UNESCAPED_UNICODE);
?>