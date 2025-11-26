<?php
// 🔥 修正版：api_report_list.php
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

    // 取得操作類型
    $action = isset($_GET['action']) ? $_GET['action'] : 'list';

    // 租金設定資料預抓
    $rent_periods = [];
    $rent_sql = "SELECT rent_start, rent_end, rent_daily FROM rent_setting";
    if ($rent_result = $pdo->query($rent_sql)) {
        $rent_periods = $rent_result->fetchAll(PDO::FETCH_ASSOC);
    }

    switch ($action) {

      // === 1️⃣ 取得全部資料 ===
      case 'list':
        $sql = "SELECT 
                  id, report_date, filled_by,
                  cash_income, linepay_income, uber_income, other_income, total_income,
                  expense_food, expense_salary, expense_rent, expense_utilities, expense_delivery, expense_misc, total_expense
                FROM daily_report
                ORDER BY report_date DESC";

        $result = $pdo->query($sql);
        $data = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
          $report_date = $row["report_date"];
          $rent_daily = 0;
          foreach ($rent_periods as $period) {
            if ($report_date >= $period["rent_start"] && $report_date <= $period["rent_end"]) {
              $rent_daily = $period["rent_daily"];
              break;
            }
          }
          $row["rent_daily"] = $rent_daily;
          $data[] = $row;
        }
        
        // 🔥 [!! 關鍵修改 !!] 將 $data 包裝在 {"success":true, "data":...} 中
        ok(['success' => true, 'data' => $data]);
        break;

      // === 2️⃣ 取得單筆資料 ===
      case 'get':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
          err("缺少或錯誤的 ID");
        }

        // 🔥 修正：改為 PDO 預備語法
        $stmt = $pdo->prepare("SELECT 
                  id, report_date, filled_by,
                  cash_income, linepay_income, uber_income, other_income, total_income,
                  expense_food, expense_salary, expense_rent, expense_utilities, expense_delivery, expense_misc, total_expense
                FROM daily_report
                WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
          $report_date = $row["report_date"];
          $rent_daily = 0;
          
          // 查詢租金 (🔥 修正：改為 PDO)
          $stmt2 = $pdo->prepare("SELECT rent_daily FROM rent_setting WHERE ? BETWEEN rent_start AND rent_end LIMIT 1");
          $stmt2->execute([$report_date]);
          if ($rent_row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $rent_daily = $rent_row["rent_daily"];
          }

          $row["rent_daily"] = $rent_daily;
          
          // 🔥 [!! 關鍵修改 !!] 將 $row 包裝在 {"success":true, "data":...} 中
          ok(['success' => true, 'data' => $row]);
        } else {
          err("找不到該筆資料", 404);
        }
        break;

      // === 3️⃣ 刪除資料 ===
      case 'delete':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
          err("缺少或錯誤的 ID");
        }

        // 🔥 修正：改為 PDO 預備語法
        $stmt = $pdo->prepare("DELETE FROM daily_report WHERE id = ?");
        $success = $stmt->execute([$id]);

        if ($success) {
          // (此處格式本來就正確，JS 可以解析)
          ok(["success" => true, "message" => "刪除成功"]);
        } else {
          err("刪除失敗", 500);
        }
        break;

      // === 預設 ===
      default:
        err("未知的操作");
        break;
    }

} catch (Throwable $e) {
    // 🔥 修正：使用標準 err() 函數
    error_log("api_report_list.php Error: " . $e->getMessage());
    err('API 內部錯誤', 500, ['detail' => $e->getMessage()]);
}
?>