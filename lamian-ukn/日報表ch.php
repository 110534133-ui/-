<?php
//******檢查、送出通知 */用於日報表
header('Content-Type: application/json; charset=utf-8');

// ===== 錯誤處理統一為 JSON 輸出 =====
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '伺服器錯誤：' . $e->getMessage()
    ]);
    exit;
});

// ===== 連線資料庫 =====
$host = 'localhost';
$db   = 'lamian';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗: ' . $e->getMessage()]);
    exit;
}

// ===== 取得前端 JSON =====
$raw = file_get_contents("php://input");
if ($raw === false || trim($raw) === '') {
    echo json_encode(['success' => false, 'message' => '未接收到任何資料']);
    exit;
}

$data = json_decode($raw, true);
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => '無效的 JSON：' . json_last_error_msg()]);
    exit;
}

/* -------------------------
   ✅ 日報表檢查（report_date）
   ------------------------- */
if (isset($data['report_date'])) {
    $required = ['report_date', 'weekday', 'filled_by'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "欄位 {$field} 為必填"]);
            exit;
        }
    }

    // 驗證日期格式
    $d = DateTime::createFromFormat('Y-m-d', $data['report_date']);
    $validDate = $d && $d->format('Y-m-d') === $data['report_date'];
    if (!$validDate) {
        echo json_encode(['success' => false, 'message' => '日期格式錯誤（需為 YYYY-MM-DD）']);
        exit;
    }

    // 檢查是否重複
    $sql = "SELECT COUNT(*) AS cnt FROM daily_report WHERE report_date = :report_date";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":report_date", $data['report_date']);
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result && $result['cnt'] > 0) {
        echo json_encode(['success' => false, 'message' => '此日期已填寫過日報表']);
        exit;
    }

    // ✅ 通過不回訊息
    echo json_encode(['success' => true]);
    exit;
}

/* -------------------------
   ✅ 租金日期檢查（rent_start, rent_end）
   ------------------------- */
if (isset($data['rent_start']) && isset($data['rent_end'])) {
    $rent_start = trim($data['rent_start']);
    $rent_end = trim($data['rent_end']);

    $ds = DateTime::createFromFormat('Y-m-d', $rent_start);
    $de = DateTime::createFromFormat('Y-m-d', $rent_end);
    $okDs = $ds && $ds->format('Y-m-d') === $rent_start;
    $okDe = $de && $de->format('Y-m-d') === $rent_end;

    if (!$okDs || !$okDe) {
        echo json_encode(['success' => false, 'message' => '租金日期格式錯誤（YYYY-MM-DD）']);
        exit;
    }

    if ($ds > $de) {
        echo json_encode(['success' => false, 'message' => '租金起始日不能晚於結束日']);
        exit;
    }

    $sql = "SELECT COUNT(*) AS cnt 
            FROM rent_setting 
            WHERE NOT (
                :rent_end < rent_start OR 
                :rent_start > rent_end
            )";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':rent_start', $rent_start);
    $stmt->bindValue(':rent_end', $rent_end);
    $stmt->execute();
    $rentOverlap = $stmt->fetch();

    if ($rentOverlap && $rentOverlap['cnt'] > 0) {
        echo json_encode(['success' => false, 'message' => '租金日期重複']);
        exit;
    }

    // ✅ 檢查通過不通知
    echo json_encode(['success' => true]);
    exit;
}

/* -------------------------
   ✅ 水電瓦斯月份檢查（utilities_month）
   ------------------------- */
if (isset($data['utilities_month'])) {
    $utilities_month = trim($data['utilities_month']);

    if ($utilities_month === '') {
        echo json_encode(['success' => false, 'message' => '水電瓦斯月份不得為空']);
        exit;
    }

    // 當前年份
    $currentYear = date('Y');

    $sql = "SELECT COUNT(*) AS cnt 
            FROM daily_report 
            WHERE YEAR(report_date) = :year 
              AND utilities_month = :utilities_month";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':year', $currentYear);
    $stmt->bindValue(':utilities_month', $utilities_month);
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result && $result['cnt'] > 0) {
        echo json_encode(['success' => false, 'message' => "今年已存在 {$utilities_month} 的水電瓦斯資料"]);
        exit;
    }

    // ✅ 通過不回訊息
    echo json_encode(['success' => true]);
    exit;
}

// 若無符合檢查類別
echo json_encode(['success' => false, 'message' => '請提供有效的檢查項目（report_date 或 rent_start/rent_end 或 utilities_month）']);
exit;
?>
