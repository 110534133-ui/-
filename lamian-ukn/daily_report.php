<?php 
header('Content-Type: application/json');
ini_set('display_errors', 0); // 關閉 HTML 錯誤輸出
error_reporting(0);

// 資料庫連線設定
$host = 'localhost';
$dbname = 'lamian';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗: ' . $e->getMessage()]);
    exit;
}

// 讀取 JSON 輸入資料
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => '未收到有效的資料']);
    exit;
}

// 取出必填欄位
$report_date = $data['report_date'] ?? null;
$weekday = $data['weekday'] ?? '';
$filled_by = $data['filled_by'] ?? '';

if (!$report_date || !$filled_by) {
    echo json_encode(['success' => false, 'message' => '日期與填表人不可為空']);
    exit;
}

// 計算支出
$expense_food = $data['expense_food'] ?? 0;
$expense_salary = $data['expense_salary'] ?? 0;
$expense_rent = $data['expense_rent'] ?? 0;
$expense_utilities = $data['expense_utilities'] ?? 0;
$expense_delivery = $data['expense_delivery'] ?? 0;
$expense_misc = $data['expense_misc'] ?? 0;
$total_expense = $expense_food + $expense_salary + $expense_rent + $expense_utilities + $expense_delivery + $expense_misc;

// 計算收入
$cash_income = $data['cash_income'] ?? 0;
$linepay_income = $data['linepay_income'] ?? 0;
$uber_income = $data['uber_income'] ?? 0;
$other_income = $data['other_income'] ?? 0;
$total_income = $cash_income + $linepay_income + $uber_income + $other_income;

// 現金總額
$cash_1000 = $data['cash_1000'] ?? 0;
$cash_500 = $data['cash_500'] ?? 0;
$cash_100 = $data['cash_100'] ?? 0;
$cash_50 = $data['cash_50'] ?? 0;
$cash_10 = $data['cash_10'] ?? 0;
$cash_5 = $data['cash_5'] ?? 0;
$cash_1 = $data['cash_1'] ?? 0;
$cash_total = ($cash_1000*1000)+($cash_500*500)+($cash_100*100)+($cash_50*50)+($cash_10*10)+($cash_5*5)+($cash_1*1);

$deposit_to_bank = $data['deposit_to_bank'] ?? 0;

// 檢查重複日期
try {
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM daily_report WHERE report_date = :report_date");
    $checkStmt->execute([':report_date' => $report_date]);
    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => '該日期的日報表已存在']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '檢查日期失敗: ' . $e->getMessage()]);
    exit;
}

// 寫入資料表
try {
    $stmt = $pdo->prepare("
        INSERT INTO daily_report (
            report_date, weekday, filled_by,
            cash_income, linepay_income, uber_income, other_income, total_income,
            expense_food, expense_salary, expense_rent, expense_utilities,
            expense_delivery, expense_misc, total_expense,
            cash_1000, cash_500, cash_100, cash_50, cash_10, cash_5, cash_1, cash_total,
            deposit_to_bank, created_at
        ) VALUES (
            :report_date, :weekday, :filled_by,
            :cash_income, :linepay_income, :uber_income, :other_income, :total_income,
            :expense_food, :expense_salary, :expense_rent, :expense_utilities,
            :expense_delivery, :expense_misc, :total_expense,
            :cash_1000, :cash_500, :cash_100, :cash_50, :cash_10, :cash_5, :cash_1, :cash_total,
            :deposit_to_bank, NOW()
        )
    ");

    $stmt->execute([
        ':report_date'=>$report_date,
        ':weekday'=>$weekday,
        ':filled_by'=>$filled_by,
        ':cash_income'=>$cash_income,
        ':linepay_income'=>$linepay_income,
        ':uber_income'=>$uber_income,
        ':other_income'=>$other_income,
        ':total_income'=>$total_income,
        ':expense_food'=>$expense_food,
        ':expense_salary'=>$expense_salary,
        ':expense_rent'=>$expense_rent,
        ':expense_utilities'=>$expense_utilities,
        ':expense_delivery'=>$expense_delivery,
        ':expense_misc'=>$expense_misc,
        ':total_expense'=>$total_expense,
        ':cash_1000'=>$cash_1000,
        ':cash_500'=>$cash_500,
        ':cash_100'=>$cash_100,
        ':cash_50'=>$cash_50,
        ':cash_10'=>$cash_10,
        ':cash_5'=>$cash_5,
        ':cash_1'=>$cash_1,
        ':cash_total'=>$cash_total,
        ':deposit_to_bank'=>$deposit_to_bank
    ]);

    echo json_encode(['success' => true, 'message' => '資料已成功寫入']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料寫入失敗: ' . $e->getMessage()]);
}
