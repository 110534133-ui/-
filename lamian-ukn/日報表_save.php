<?php    
//******送出資料到資料庫 */用於日報表
header('Content-Type: application/json');

// ===== 連線資料庫 =====
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
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗: ' . $e->getMessage()]);
    exit;
}

// ===== 取得 JSON 資料 =====
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => '未接收到有效資料']);
    exit;
}

// ===== 必填欄位檢查 =====
$required = ['report_date', 'weekday', 'filled_by'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "欄位 {$field} 為必填"]);
        exit;
    }
}

// ===== utilities_month 重複檢查 =====
if (!empty($data['utilities_month'])) {
    $utilities_month = trim($data['utilities_month']);
    $reportYear = date('Y', strtotime($data['report_date'])); // 以 report_date 為準，抓年份

    $check_sql = "SELECT COUNT(*) AS cnt 
                  FROM daily_report 
                  WHERE YEAR(report_date) = :year 
                    AND utilities_month = :utilities_month";
    $stmt = $pdo->prepare($check_sql);
    $stmt->bindValue(':year', $reportYear);
    $stmt->bindValue(':utilities_month', $utilities_month);
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result && $result['cnt'] > 0) {
        echo json_encode(['success' => false, 'message' => "今年已存在 {$utilities_month} 的水電瓦斯資料"]);
        exit;
    }
}

// ===== daily_report 資料表欄位對應 =====
$fields = [
    'report_date', 'weekday', 'filled_by',
    'cash_income', 'linepay_income', 'uber_income', 'other_income', 'total_income',
    'expense_salary', 'expense_utilities', 'utilities_month', 'expense_rent',
    'expense_food', 'expense_delivery', 'expense_misc',
    'cash_1000', 'cash_500', 'cash_100', 'cash_50', 'cash_10', 'cash_5', 'cash_1', 'cash_total',
    'deposit_to_bank', 'created_at' // ✅ 新增 created_at 欄位
];

// ===== 自動加入目前時間 =====
$data['created_at'] = date('Y-m-d H:i:s');

$columns = implode(", ", $fields);
$placeholders = ":" . implode(", :", $fields);

$sql = "INSERT INTO daily_report ($columns) VALUES ($placeholders)";
$stmt = $pdo->prepare($sql);

// ===== 綁定資料，若欄位不存在或空值就給 0 或空字串 =====
foreach ($fields as $field) {
    $value = isset($data[$field]) ? $data[$field] : null;

    // 強制 numeric 欄位為 0
    if (in_array($field, [
        'cash_income','linepay_income','uber_income','other_income','total_income',
        'expense_salary','expense_utilities','expense_rent','expense_food','expense_delivery','expense_misc',
        'cash_1000','cash_500','cash_100','cash_50','cash_10','cash_5','cash_1','cash_total','deposit_to_bank'
    ])) {
        $value = is_numeric($value) ? $value : 0;
    }

    $stmt->bindValue(":$field", $value);
}

// ===== 寫入 daily_report =====
try {
    $stmt->execute();

    // ===== 若有租金設定且租金總額大於 0，才寫入 rent_setting =====
    if (!empty($data['rent_setting'])) {
        $rent = json_decode($data['rent_setting'], true);
        $rent_total = isset($data['expense_rent']) ? floatval($data['expense_rent']) : 0;

        if ($rent && $rent_total > 0) {
            $rent_start = new DateTime($rent['start']);
            $rent_end   = new DateTime($rent['end']);
            $interval   = $rent_start->diff($rent_end)->days + 1; // 包含起始日
            $rent_daily = $interval > 0 ? round($rent_total / $interval, 2) : 0;

            $rent_sql = "INSERT INTO rent_setting 
                (rent_period, rent_start, rent_end, rent_total, rent_daily, created_at) 
                VALUES (:rent_period, :rent_start, :rent_end, :rent_total, :rent_daily, NOW())";
            $rent_stmt = $pdo->prepare($rent_sql);
            $rent_stmt->bindValue(":rent_period", $rent['period']);
            $rent_stmt->bindValue(":rent_start", $rent['start']);
            $rent_stmt->bindValue(":rent_end", $rent['end']);
            $rent_stmt->bindValue(":rent_total", $rent_total);
            $rent_stmt->bindValue(":rent_daily", $rent_daily);
            $rent_stmt->execute();
        }
    }

    echo json_encode(['success' => true, 'message' => '日報表送出成功']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料儲存失敗: ' . $e->getMessage()]);
}
?>
