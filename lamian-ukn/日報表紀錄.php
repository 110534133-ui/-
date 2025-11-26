<?php
// 日報表紀錄.php - 給日報表紀錄.html使用
//****讀取全部資料（action=list）、讀取單筆資料（action=get）、刪除資料（action=delete） */


header("Content-Type: application/json; charset=utf-8");
ini_set('display_errors', 0); // 關閉HTML錯誤輸出，避免破壞JSON
error_reporting(E_ALL);

// ===== 資料庫連線設定 =====
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lamian";

$conn = new mysqli($servername, $username, $password, $dbname);

// ===== 檢查連線 =====
if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "資料庫連線失敗：" . $conn->connect_error], JSON_UNESCAPED_UNICODE);
  exit;
}

// ===== 統一錯誤處理 =====
function safeJson($success, $message, $data = []) {
  echo json_encode(["success" => $success, "message" => $message, "data" => $data], JSON_UNESCAPED_UNICODE);
  exit;
}

// ===== 取得操作類型 =====
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// ===== 租金設定資料預抓 =====
$rent_periods = [];
$rent_sql = "SELECT rent_start, rent_end, rent_daily FROM rent_setting";
if ($rent_result = $conn->query($rent_sql)) {
  while ($r = $rent_result->fetch_assoc()) {
    $rent_periods[] = $r;
  }
}

switch ($action) {

  // === 1️⃣ 取得全部資料 ===
  case 'list':
    $sql = "SELECT 
              id,
              report_date,
              filled_by,
              cash_income,
              linepay_income,
              uber_income,
              other_income,
              total_income,
              expense_food,
              expense_salary,
              expense_rent,
              expense_utilities,
              expense_delivery,
              expense_misc,
              total_expense
            FROM daily_report
            ORDER BY report_date DESC";

    $result = $conn->query($sql);
    if (!$result) {
      safeJson(false, "SQL 錯誤：" . $conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
      $report_date = $row["report_date"];
      $rent_daily = 0;

      // 根據報表日期比對租金區間
      foreach ($rent_periods as $period) {
        if ($report_date >= $period["rent_start"] && $report_date <= $period["rent_end"]) {
          $rent_daily = $period["rent_daily"];
          break;
        }
      }

      $row["rent_daily"] = $rent_daily; // 動態補上每日租金
      $data[] = $row;
    }

    safeJson(true, "讀取成功", $data);
    break;


  // === 2️⃣ 取得單筆資料 ===
  case 'get':
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
      safeJson(false, "缺少或錯誤的 ID");
    }

    $stmt = $conn->prepare("SELECT 
              id,
              report_date,
              filled_by,
              cash_income,
              linepay_income,
              uber_income,
              other_income,
              total_income,
              expense_food,
              expense_salary,
              expense_rent,
              expense_utilities,
              expense_delivery,
              expense_misc,
              total_expense
            FROM daily_report
            WHERE id = ?");
    if (!$stmt) safeJson(false, "SQL 錯誤：" . $conn->error);

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
      $report_date = $row["report_date"];
      $rent_daily = 0;

      // 查詢該日期是否在租金期間內
      $stmt2 = $conn->prepare("SELECT rent_daily FROM rent_setting WHERE ? BETWEEN rent_start AND rent_end LIMIT 1");
      if ($stmt2) {
        $stmt2->bind_param("s", $report_date);
        $stmt2->execute();
        $rent_result = $stmt2->get_result();
        if ($rent_row = $rent_result->fetch_assoc()) {
          $rent_daily = $rent_row["rent_daily"];
        }
        $stmt2->close();
      }

      $row["rent_daily"] = $rent_daily;
      safeJson(true, "讀取成功", $row);
    } else {
      safeJson(false, "找不到該筆資料");
    }
    break;


  // === 3️⃣ 刪除資料 ===
  case 'delete':
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
      safeJson(false, "缺少或錯誤的 ID");
    }

    $stmt = $conn->prepare("DELETE FROM daily_report WHERE id = ?");
    if (!$stmt) safeJson(false, "SQL 錯誤：" . $conn->error);

    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
      safeJson(true, "刪除成功");
    } else {
      safeJson(false, "刪除失敗");
    }
    break;


  // === 預設 ===
  default:
    safeJson(false, "未知的操作");
    break;
}

$conn->close();
?>
