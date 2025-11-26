<?php
// 🔥 修正版 - 確認班表.php
// 移除不存在的 config.php 和 auth_check.php 引用
// 改用直接的資料庫連線和權限檢查

session_start();
header('Content-Type: application/json; charset=utf-8');

// ===== 資料庫連線設定 =====
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
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => '資料庫連線失敗: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 權限檢查 =====
// 🔥 統一檢查邏輯: 只有 A (老闆) 或 B (管理員) 可以操作確認班表
if (!isset($_SESSION['uid']) || empty($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => '未登入'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 取得用戶等級 (統一檢查多個可能的 session key)
$userLevel = $_SESSION['user_level'] ?? $_SESSION['role'] ?? $_SESSION['role_code'] ?? 'C';

// 只允許 A 或 B 級
if (!in_array($userLevel, ['A', 'B'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'error' => '權限不足 (僅限老闆或管理員)',
        'current_level' => $userLevel
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$currentUserId = $_SESSION['uid'];
$currentUserName = $_SESSION['name'] ?? '未知';

error_log("確認班表.php - 登入用戶: ID={$currentUserId}, Name={$currentUserName}, Level={$userLevel}");

// ===== 建立員工姓名 -> ID 對照表 =====
try {
    $stmtEmp = $pdo->query("SELECT id, name FROM 員工基本資料 ORDER BY id");
    $employeeMap = [];
    
    while ($row = $stmtEmp->fetch()) {
        $employeeMap[trim($row['name'])] = $row['id'];
    }
    
    error_log("確認班表.php - 員工對照表建立完成: " . count($employeeMap) . " 人");
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => '無法載入員工清單: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== POST: 儲存班表 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['assignments'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少必要資料 (assignments)'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $assignments = $input['assignments'];
    $weekStart = $input['week_start'] ?? null;
    
    if (!$weekStart) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少必要資料 (week_start)'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->beginTransaction();

    try {
        // 1. 先刪除該週的所有確認班表資料
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
        
        $stmtDel = $pdo->prepare("
            DELETE FROM 確認班表
            WHERE work_date BETWEEN ? AND ?
        ");
        $stmtDel->execute([$weekStart, $weekEnd]);
        
        $deletedCount = $stmtDel->rowCount();
        error_log("確認班表.php - 刪除舊資料: {$deletedCount} 筆");

        // 2. 準備插入語句
        $stmtInsert = $pdo->prepare("
            INSERT INTO 確認班表
            (user_id, work_date, start_time, end_time, shift_type, note, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $insertCount = 0;
        $userSchedule = []; // 用於檢查時段重疊

        // 3. 遍歷所有日期和時段
        foreach ($assignments as $date => $periods) {
            // 驗證日期格式
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                error_log("確認班表.php - 略過無效日期: {$date}");
                continue;
            }
            
            foreach ($periods as $shiftType => $list) {
                // 驗證時段類型
                if (!in_array($shiftType, ['上午', '晚上'])) {
                    error_log("確認班表.php - 略過無效時段類型: {$shiftType}");
                    continue;
                }
                
                foreach ($list as $item) {
                    $name = trim($item['name'] ?? '');
                    $time = trim($item['time'] ?? '');
                    
                    if (!$name || !$time) {
                        error_log("確認班表.php - 略過空白資料: date={$date}, type={$shiftType}");
                        continue;
                    }

                    // 解析時間 (格式: "10:00-18:00")
                    $parts = explode('-', $time);
                    if (count($parts) !== 2) {
                        error_log("確認班表.php - 時間格式錯誤: {$time}");
                        continue;
                    }
                    
                    $start = trim($parts[0]);
                    $end = trim($parts[1]);
                    
                    // 驗證時間格式
                    if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
                        error_log("確認班表.php - 時間格式不正確: {$start} - {$end}");
                        continue;
                    }

                    // 查找員工 ID
                    if (!isset($employeeMap[$name])) {
                        error_log("確認班表.php - 找不到員工: {$name}");
                        continue;
                    }
                    
                    $user_id = $employeeMap[$name];

                    // 檢查時段重疊
                    $key = $user_id . '_' . $date;
                    if (!isset($userSchedule[$key])) {
                        $userSchedule[$key] = [];
                    }

                    $overlap = false;
                    foreach ($userSchedule[$key] as $existing) {
                        // 檢查時段是否重疊
                        if (!($end <= $existing['start'] || $start >= $existing['end'])) {
                            $overlap = true;
                            error_log("確認班表.php - 時段重疊: {$name} 在 {$date} 的 {$time} 與其他班表重疊");
                            break;
                        }
                    }
                    
                    if ($overlap) {
                        $pdo->rollBack();
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => "錯誤: {$name} 在 {$date} 時段 {$time} 與其他班表重疊!"
                        ], JSON_UNESCAPED_UNICODE);
                        exit;
                    }

                    // 記錄該時段
                    $userSchedule[$key][] = ['start' => $start, 'end' => $end];

                    // 插入資料
                    $stmtInsert->execute([
                        $user_id,
                        $date,
                        $start,
                        $end,
                        $shiftType,
                        $item['note'] ?? ''
                    ]);
                    
                    $insertCount++;
                }
            }
        }

        $pdo->commit();
        
        error_log("確認班表.php - 儲存成功: 週 {$weekStart}, 共 {$insertCount} 筆");
        
        echo json_encode([
            'success' => true, 
            'message' => "班表已確認並儲存,共儲存 {$insertCount} 筆資料!",
            'inserted' => $insertCount,
            'deleted' => $deletedCount
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("確認班表.php - 儲存失敗: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => '儲存失敗: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    exit;
}

// ===== GET: 讀取本週班表 =====
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['date'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少 date 參數'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $date = $_GET['date'];
    
    // 計算該週的週一
    $timestamp = strtotime($date);
    $dayOfWeek = date('N', $timestamp); // 1=Monday, 7=Sunday
    $monday = date('Y-m-d', strtotime("-" . ($dayOfWeek - 1) . " days", $timestamp));
    $sunday = date('Y-m-d', strtotime("+6 days", strtotime($monday)));

    // 生成 7 天的日期陣列
    $dates = [];
    for ($i = 0; $i < 7; $i++) {
        $dates[] = date('Y-m-d', strtotime("+$i day", strtotime($monday)));
    }

    try {
        // 查詢該週的所有班表
        $stmt = $pdo->prepare("
            SELECT c.user_id, e.name, c.work_date, c.start_time, c.end_time, c.shift_type
            FROM 確認班表 c
            JOIN 員工基本資料 e ON c.user_id = e.id
            WHERE c.work_date BETWEEN ? AND ?
            ORDER BY c.work_date, c.start_time
        ");

        $stmt->execute([$dates[0], $dates[6]]);
        $rows = $stmt->fetchAll();

        // 重組資料: 按時段和日期分組
        $output = [];
        
        foreach (['上午', '晚上'] as $slot) {
            $weekData = [];
            
            // 初始化每一天為空陣列
            foreach ($dates as $d) {
                $weekData[$d] = [];
            }

            // 填充資料
            foreach ($rows as $r) {
                if ($r['shift_type'] === $slot) {
                    $text = "{$r['name']} ({$r['start_time']}-{$r['end_time']})";
                    $weekData[$r['work_date']][] = $text;
                }
            }

            // 轉換為最終格式
            $output[] = [
                'timeSlot' => $slot,
                'days' => array_map(function($names) {
                    return empty($names) ? '-' : implode('<br>', $names);
                }, array_values($weekData))
            ];
        }

        error_log("確認班表.php - GET 成功: 週 {$monday}, 共 " . count($rows) . " 筆");
        
        echo json_encode($output, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        error_log("確認班表.php - GET 失敗: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => '查詢失敗: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    exit;
}

// ===== 其他方法 =====
http_response_code(405);
echo json_encode([
    'success' => false, 
    'error' => '不支援的請求方法'
], JSON_UNESCAPED_UNICODE);
?>