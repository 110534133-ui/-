<?php
// /lamian-ukn/api/人事計算.php
header("Content-Type: application/json; charset=utf-8");

// 連線資料庫（依你 XAMPP root 無密碼）
$conn = new mysqli("localhost", "root", "", "lamian");

// 連線錯誤
if ($conn->connect_error) {
    echo json_encode(["error" => "資料庫連線失敗"]);
    exit;
}

// 今日日期（自動判定）
$today = date("Y-m-d");

// ---------------------------------------------
// 1. 取得今日有打卡紀錄的員工（避免正職被算兩次）
// ---------------------------------------------
$sql = "
    SELECT 
        a.user_id,
        a.hours,
        e.role,
        e.base_salary,
        e.hourly_rate
    FROM attendance a
    JOIN `員工基本資料` e ON a.user_id = e.id
    WHERE DATE(a.clock_in) = ?
      AND a.status = '正常'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

$used_fulltime = [];  // 用來避免正職被計算多次
$total_labor_cost = 0;

while ($row = $result->fetch_assoc()) {

    $user_id = $row["user_id"];
    $hours = floatval($row["hours"]);
    $base_salary = intval($row["base_salary"]);
    $hourly_rate = intval($row["hourly_rate"]);

    // 正職員工（有底薪）
    if ($base_salary > 0) {
        if (in_array($user_id, $used_fulltime)) continue;
        $daily_salary = $base_salary / 30;
        $total_labor_cost += $daily_salary;
        $used_fulltime[] = $user_id;
    }

    // 兼職員工（有時薪）
    if ($hourly_rate > 0) {
        $total_labor_cost += ($hours * $hourly_rate);
    }
}

echo json_encode([
    "date" => $today,
    "total_labor_cost" => round($total_labor_cost)
]);

$stmt->close();
$conn->close();
