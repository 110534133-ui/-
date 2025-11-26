<?php 
// ===== 日報表紀錄2.php - 修改日報表 =====
header("Content-Type: application/json; charset=utf-8");
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ===== 資料庫連線 =====
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lamian";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "資料庫連線失敗：" . $conn->connect_error], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 統一 JSON 回傳函式 =====
function safeJson($success, $message, $data = [])
{
    echo json_encode(["success" => $success, "message" => $message, "data" => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 僅允許 POST =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    safeJson(false, "請使用 POST 方法");
}

// ===== 接收 JSON 請求 =====
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) safeJson(false, "無法解析 JSON 請求");

// ===== 取得欄位 =====
$id = intval($input['id'] ?? 0);
if ($id <= 0) safeJson(false, "缺少或錯誤的 ID");

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

// ===== 計算 total_income 與 total_expense =====
$total_income = $cash_income + $linepay_income + $uber_income + $other_income;
$total_expense = $expense_food + $expense_salary + $expense_rent + $expense_utilities + $expense_delivery + $expense_misc;

// ===== 更新資料 =====
$stmt = $conn->prepare("UPDATE daily_report SET 
    report_date=?, filled_by=?, 
    cash_income=?, linepay_income=?, uber_income=?, other_income=?, 
    total_income=?,
    expense_food=?, expense_salary=?, expense_rent=?, expense_utilities=?, expense_delivery=?, expense_misc=?, total_expense=?
    WHERE id=?");

if (!$stmt) safeJson(false, "SQL 準備錯誤：" . $conn->error);

// bind_param 型態 s=string, d=double (float), i=int
$stmt->bind_param(
    "ssdddddddddddii",
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
);

$success = $stmt->execute();
$stmt->close();
$conn->close();

if ($success) {
    safeJson(true, "修改成功");
} else {
    safeJson(false, "修改失敗");
}
?>
