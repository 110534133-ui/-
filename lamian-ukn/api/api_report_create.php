<?php    
// 🔥 修正版：api_report_create.php
header('Content-Type: application/json');

// 🔥 修正：引入標準設定檔和權限檢查
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/config.php'; // 裡面有 pdo(), ok(), err()

try {
    // 🔥 修正：檢查權限 (A 或 B 級)，且 $redirect = false (API模式)
    if (!check_user_level(['A', 'B'], false)) {
        err('權限不足 (僅限 A/B 級)', 403);
    }
    
    // 🔥 修正：使用標準 pdo() 連線
    $pdo = pdo();

    // ===== 取得 JSON 資料 =====
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        err('未接收到有效資料');
    }

    // ===== 必填欄位檢查 =====
    $required = ['report_date', 'weekday', 'filled_by'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            err("欄位 {$field} 為必填");
        }
    }

    // ===== utilities_month 重複檢查 =====
    if (!empty($data['utilities_month'])) {
        $utilities_month = trim($data['utilities_month']);
        $reportYear = date('Y', strtotime($data['report_date']));

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
            err("今年已存在 {$utilities_month} 的水電瓦斯資料");
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

    $data['created_at'] = date('Y-m-d H:i:s');

    $columns = implode(", ", $fields);
    $placeholders = ":" . implode(", :", $fields);

    $sql = "INSERT INTO daily_report ($columns) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);

    foreach ($fields as $field) {
        $value = isset($data[$field]) ? $data[$field] : null;
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
    $stmt->execute();

    // ===== 若有租金設定且租金總額大於 0，才寫入 rent_setting =====
    if (!empty($data['rent_setting'])) {
        $rent = json_decode($data['rent_setting'], true);
        $rent_total = isset($data['expense_rent']) ? floatval($data['expense_rent']) : 0;

        if ($rent && $rent_total > 0) {
            $rent_start = new DateTime($rent['start']);
            $rent_end   = new DateTime($rent['end']);
            $interval   = $rent_start->diff($rent_end)->days + 1;
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
    
    // 🔥 修正：使用 ok() 回應
    ok(['success' => true, 'message' => '日報表送出成功']);

} catch (Throwable $e) {
    // 🔥 修正：使用 err() 回應
    error_log("api_report_create.php Error: " . $e->getMessage());
    err('資料儲存失敗', 500, ['detail' => $e->getMessage()]);
}
?>