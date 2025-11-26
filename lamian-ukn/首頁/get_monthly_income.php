<?php
// 1. 引入唯一的設定檔 (路徑從 /首頁 指向 /api)
require_once __DIR__ . '/../api/config.php';

// (舊的 header, ini_set, error_reporting, new PDO... 皆已刪除)

try {
  // 2. 透過 config.php 的 pdo() 函數取得連線
  $pdo = pdo();

  $year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
  $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

  // 3. (SQL 邏輯保持不變)
  $sql = "
    SELECT
      COALESCE(SUM(cash_income),   0) AS cash_income_total,
      COALESCE(SUM(linepay_income),0) AS linepay_income_total,
      COALESCE(SUM(uber_income),   0) AS uber_income_total
    FROM daily_report
    WHERE YEAR(report_date)=:y AND MONTH(report_date)=:m
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':y'=>$year, ':m'=>$month]);
  $row = $st->fetch() ?: [];

  // 4. 使用 config.php 的 ok() 函數來回傳 JSON
  ok([
    'success'=>true,
    'year'=>$year,
    'month'=>$month,
    'data'=>[
      'cash_income'    => (float)($row['cash_income_total']    ?? 0),
      'linepay_income' => (float)($row['linepay_income_total'] ?? 0),
      'uber_income'    => (float)($row['uber_income_total']    ?? 0),
    ]
  ]);

} catch(PDOException $e) {
  // 5. 使用 config.php 的 err() 函數來回傳錯誤
  err('查詢失敗: '.$e->getMessage(), 500);
}
?>