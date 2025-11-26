<?php
//用於{日報表記錄}


header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 取得 JSON 資料
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => '無效的資料']);
    exit;
}

// 檢查必要欄位
if (empty($input['id']) || empty($input['report_date']) || empty($input['filled_by'])) {
    echo json_encode(['success' => false, 'message' => '缺少必要欄位']);
    exit;
}

// 資料庫連線設定
$host = 'localhost';
$dbname = 'lamian';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 更新資料 SQL
    $sql = "UPDATE daily_report SET
        report_date = :report_date,
        filled_by = :filled_by,
        cash_income = :cash_income,
        linepay_income = :linepay_income,
        uber_income = :uber_income,
        other_income = :other_income,
        total_income = :total_income,
        expense_food = :expense_food,
        expense_salary = :expense_salary,
        expense_rent = :expense_rent,
        expense_rent_month = :expense_rent_month,
        expense_utilities = :expense_utilities,
        expense_delivery = :expense_delivery,
        expense_misc = :expense_misc,
        total_expense = :total_expense
        WHERE id = :id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':report_date' => $input['report_date'],
        ':filled_by' => $input['filled_by'],
        ':cash_income' => $input['cash_income'] ?: 0,
        ':linepay_income' => $input['linepay_income'] ?: 0,
        ':uber_income' => $input['uber_income'] ?: 0,
        ':other_income' => $input['other_income'] ?: 0,
        ':total_income' => $input['total_income'] ?: 0,
        ':expense_food' => $input['expense_food'] ?: 0,
        ':expense_salary' => $input['expense_salary'] ?: 0,
        ':expense_rent' => $input['expense_rent'] ?: 0,
        ':expense_rent_month' => $input['expense_rent_month'] ?: 0,
        ':expense_utilities' => $input['expense_utilities'] ?: 0,
        ':expense_delivery' => $input['expense_delivery'] ?: 0,
        ':expense_misc' => $input['expense_misc'] ?: 0,
        ':total_expense' => $input['total_expense'] ?: 0,
        ':id' => $input['id']
    ]);

    echo json_encode(['success' => true, 'message' => '修改成功']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
