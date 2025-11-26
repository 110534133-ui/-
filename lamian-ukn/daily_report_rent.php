<?php 
header('Content-Type: application/json; charset=utf-8');

// 1️⃣ 啟用錯誤顯示，方便除錯
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'db_connect.php';

    // 2️⃣ 讀取前端傳來的 JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) throw new Exception("無效的輸入資料");

    $report_date = $input['report_date'] ?? null;
    $weekday     = $input['weekday'] ?? null;
    $filled_by   = $input['filled_by'] ?? null;

   

    // 收入
    $cash_income  = $input['cash_income'] ?? 0;
    $linepay_income = $input['linepay_income'] ?? 0;
    $uber_income = $input['uber_income'] ?? 0;
    $other_income = $input['other_income'] ?? 0;
    $total_income = $input['total_income'] ?? 0;

    // 支出
    $expense_salary    = $input['expense_salary'] ?? 0;
    $expense_utilities = $input['expense_utilities'] ?? 0;
    $expense_food      = $input['expense_food'] ?? 0;
    $expense_delivery  = $input['expense_delivery'] ?? 0;
    $expense_misc      = $input['expense_misc'] ?? 0;

    // 現金
    $cash_1000 = $input['cash_1000'] ?? 0;
    $cash_500  = $input['cash_500'] ?? 0;
    $cash_100  = $input['cash_100'] ?? 0;
    $cash_50   = $input['cash_50'] ?? 0;
    $cash_10   = $input['cash_10'] ?? 0;
    $cash_5    = $input['cash_5'] ?? 0;
    $cash_1    = $input['cash_1'] ?? 0;
    $cash_total = $input['cash_total'] ?? 0;

    $deposit_to_bank = $input['deposit_to_bank'] ?? 0;
    $created_at = date('Y-m-d H:i:s');

    // ===== 取得租金設定 =====
    $rent_daily  = 0;
    $rent_total  = 0;
    $rent_period = 'month';

    $sql = "SELECT rent_total, rent_daily, rent_period FROM rent_setting WHERE ? BETWEEN rent_start AND rent_end LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $report_date);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $rent = $res->fetch_assoc();
            $rent_total  = floatval($rent['rent_total']);
            $rent_daily  = floatval($rent['rent_daily']);
            $rent_period = $rent['rent_period'];
        }
        $stmt->close();
    }

    // ===== 檢查日期是否重複 =====
    $checkStmt = $conn->prepare("SELECT id FROM daily_report WHERE report_date = ?");
    $checkStmt->bind_param("s", $report_date);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows > 0) {
        echo json_encode(['success'=>false,'message'=>"日期 {$report_date} 已存在資料"]);
        exit;
    }
    $checkStmt->close();

    // ===== 寫入 daily_report =====
    $stmt = $conn->prepare("
        INSERT INTO daily_report (
            report_date, weekday, filled_by,
            cash_income, linepay_income, uber_income, other_income, total_income,
            expense_salary, expense_utilities, expense_food, expense_delivery, expense_misc,
            cash_1000, cash_500, cash_100, cash_50, cash_10, cash_5, cash_1, cash_total,
            deposit_to_bank,
            rent_total, rent_daily, rent_period,
            created_at
        ) VALUES (
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?,
            ?, ?, ?,
            ?
        )
    ");

    $stmt->bind_param(
        "sssddddddddddiiiiiiidsds",
        $report_date, $weekday, $filled_by,
        $cash_income, $linepay_income, $uber_income, $other_income, $total_income,
        $expense_salary, $expense_utilities, $expense_food, $expense_delivery, $expense_misc,
        $cash_1000, $cash_500, $cash_100, $cash_50, $cash_10, $cash_5, $cash_1, $cash_total,
        $deposit_to_bank,
        $rent_total, $rent_daily, $rent_period,
        $created_at
    );

    if($stmt->execute()){
        echo json_encode(['success'=>true,'message'=>"日報表已成功儲存（{$report_date}）"]);
    } else {
        throw new Exception("儲存失敗：" . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    exit;
}
?>
