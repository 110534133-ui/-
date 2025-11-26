<?php
// /lamian-ukn/首頁/stats.php (v2 - 儀表板三卡片版)

// 引入設定檔和權限檢查
require_once __DIR__ . '/../includes/auth_check.php'; 
require_once __DIR__ . '/../api/config.php'; 

header('Content-Type: application/json');

// 檢查 A 級或 B 級權限 (老闆或經理)
if (!check_user_level(['A', 'B'], false)) {
    err('權限不足 (僅限 A/B 級)', 403);
}

// 接收 index.php 傳來的年月參數
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

$output = [
    'success' => false,
    'data' => [
        'today_revenue' => 0,
        'month_revenue' => 0,
        'attendance_count' => 0
    ]
];

try {
    $pdo = pdo(); // 取得資料庫連線

    // --- 1. 本日營收 ---
    // 來源: daily_report (同 get_daily_report.php)
    $stmt1 = $pdo->prepare("SELECT SUM(total_income) FROM daily_report WHERE DATE(report_date) = CURDATE()");
    $stmt1->execute();
    $output['data']['today_revenue'] = (int)$stmt1->fetchColumn();

    // --- 2. 本月營收 ---
    // 來源: daily_report (同 get_monthly_income.php)
    $stmt2 = $pdo->prepare("
        SELECT COALESCE(SUM(cash_income), 0) + COALESCE(SUM(linepay_income),0) + COALESCE(SUM(uber_income), 0)
        FROM daily_report
        WHERE YEAR(report_date) = :y AND MONTH(report_date) = :m
    ");
    $stmt2->execute([':y'=>$year, ':m'=>$month]);
    $output['data']['month_revenue'] = (int)$stmt2->fetchColumn();

    // --- 3. 今天上班人數 ---
    // 來源: attendance (同 image_38b89d.png)
    $stmt3 = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM attendance WHERE DATE(clock_in) = CURDATE()");
    $stmt3->execute();
    $output['data']['attendance_count'] = (int)$stmt3->fetchColumn();
    

    $output['success'] = true;

} catch (Exception $e) {
    // 捕捉 SQL 錯誤
    $output['message'] = '資料庫查詢錯誤: ' . $e->getMessage();
}

// 使用 config.php 的 ok() 或 err() 函數回傳
if ($output['success']) {
    ok($output);
} else {
    // 如果 catch 到錯誤，這裡會回傳錯誤訊息
    err($output['message'] ?? '未知錯誤', 500);
}
?>