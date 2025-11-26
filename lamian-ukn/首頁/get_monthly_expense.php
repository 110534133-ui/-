<?php
// 1. 引入唯一的設定檔
// (這個路徑假設 '首頁' 資料夾和 'api' 資料夾都在您的專案根目錄)
require_once __DIR__ . '/../api/config.php';

// 2. (所有舊的 header, ini_set, error_reporting, new PDO... 都已刪除)

try {
    // 3. 透過 config.php 的 pdo() 函數取得連線
    $pdo = pdo();

    $year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

    $start = sprintf('%04d-%02d-01', $year, $month);
    $end   = date('Y-m-t', strtotime($start));

    // 4. (SQL 邏輯保持不變)
    $sql = "
      SELECT
        COALESCE(SUM(expense_food),      0) AS expense_food,
        COALESCE(SUM(expense_salary),    0) AS expense_salary,
        COALESCE(SUM(expense_utilities), 0) AS expense_utilities,
        COALESCE(SUM(expense_delivery),  0) AS expense_delivery,
        COALESCE(SUM(expense_rent),      0) AS expense_rent,
        COALESCE(SUM(expense_misc),      0) AS expense_misc
      FROM daily_report
      WHERE DATE(report_date) BETWEEN :s AND :e
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':s'=>$start, ':e'=>$end]);
    $r = $st->fetch() ?: [];

    $data = [
      ['category'=>'食材成本',     'amount'=>(float)($r['expense_food']      ?? 0)],
      ['category'=>'人力成本',     'amount'=>(float)($r['expense_salary']    ?? 0)],
      ['category'=>'水電瓦斯',     'amount'=>(float)($r['expense_utilities'] ?? 0)],
      ['category'=>'外送平台抽成', 'amount'=>(float)($r['expense_delivery']  ?? 0)],
      ['category'=>'租金',         'amount'=>(float)($r['expense_rent']      ?? 0)],
      ['category'=>'雜項',         'amount'=>(float)($r['expense_misc']      ?? 0)],
    ];

    // 5. 使用 config.php 的 ok() 函數來回傳 JSON
    ok(['success'=>true,'year'=>$year,'month'=>$month,'data'=>$data]);

} catch(PDOException $e) {
    // 6. 使用 config.php 的 err() 函數來回傳錯誤
    err('查詢失敗: '.$e->getMessage(), 500);
}
?>