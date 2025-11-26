<?php 
// 🔥 修正版：api_report_update.php
header("Content-Type: application/json; charset=utf-8");

// 🔥 修正：引入標準設定檔和權限檢查
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/config.php';

try {
    // 🔥 修正：檢查權限 (A 或 B 級)，且 $redirect = false (API模式)
    if (!check_user_level(['A', 'B'], false)) {
        err('權限不足 (僅限 A/B 級)', 403);
    }
    
    // 🔥 修正：使用標準 pdo() 連線
    $pdo = pdo();

    // 僅允許 POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        err("請使用 POST 方法", 405);
    }

    // 接收 JSON 請求
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) err("無法解析 JSON 請求");

    // 取得欄位
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) err("缺少或錯誤的 ID");

    $report_date        = $input['report_date'] ?? '';
    $filled_by          = $input['filled_by'] ?? '';
    $cash_income        = floatval($input['cash_income'] ?? 0);
    $linepay_income     = floatval($input['linepay_income'] ?? 0);
    $uber_income        = floatval($input['uber_income'] ?? 0);
    $other_income       = floatval($input['other_income'] ?? 0);
    $expense_food       = floatval($input['expense_food'] ?? 0);
    $expense_salary     = floatval($input['expense_salary'] ?? 0);
    $expense_rent       = floatval($input['expense_rent'] ?? 0);
    $expense_utilities  = floatval($input['expense_utilities'] ?? 0);
    $expense_delivery   = floatval($input['expense_delivery'] ?? 0);
    $expense_misc       = floatval($input['expense_misc'] ?? 0);

    // 計算 total_income 與 total_expense
    $total_income = $cash_income + $linepay_income + $uber_income + $other_income;
    $total_expense = $expense_food + $expense_salary + $expense_rent + $expense_utilities + $expense_delivery + $expense_misc;

    // 🔥 修正：改為 PDO 預備語法
    $stmt = $pdo->prepare("UPDATE daily_report SET 
        report_date=?, filled_by=?, 
        cash_income=?, linepay_income=?, uber_income=?, other_income=?, 
        total_income=?,
        expense_food=?, expense_salary=?, expense_rent=?, expense_utilities=?, expense_delivery=?, expense_misc=?, total_expense=?
        WHERE id=?");
    
    $success = $stmt->execute([
        $report_date,
        $filled_by,
        $cash_income,
        $linepay_income,
        $uber_income,
        $other_income,
        $total_income,
        $expense_food,
        $expense_salary,
        $expense_rent,
        $expense_utilities,
        $expense_delivery,
        $expense_misc,
        $total_expense,
        $id
    ]);

    if ($success) {
        ok(["success" => true, "message" => "修改成功"]);
    } else {
        err("修改失敗", 500);
    }
    
} catch (Throwable $e) {
    // 🔥 修正：使用標準 err() 函數
    error_log("api_report_update.php Error: " . $e->getMessage());
    err('API 內部錯誤', 500, ['detail' => $e->getMessage()]);
}
?>