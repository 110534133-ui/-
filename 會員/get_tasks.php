<?php
require_once "config.php";
session_start();

header('Content-Type: application/json');

$phone = $_SESSION['phone'] ?? null;
if (!$phone) {
    echo json_encode(["error" => "未登入"]);
    exit;
}

// 抓取會員資料
$sql_member = "SELECT 姓名, 電話, 生日, Email, 地址, 會員點數 FROM ramen_members WHERE 電話 = ?";
$stmt = $conn->prepare($sql_member);
$stmt->bind_param("s", $phone);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

// 初始化任務列表
$tasks = [
    [
        "name" => "完善個人資料",
        "points" => 50,
        "canClaim" => false,
        "claimed" => false
    ],
    [
        "name" => "首次入會",
        "points" => 100,
        "canClaim" => false,
        "claimed" => false
    ],
    [
        "name" => "生日優惠",
        "points" => 200,
        "canClaim" => false,
        "claimed" => false
    ]
];

// 查詢已領取的優惠券
$sql_coupons = "SELECT 優惠券名稱, 狀態 FROM ramen_coupons WHERE 電話 = ?";
$stmt2 = $conn->prepare($sql_coupons);
$stmt2->bind_param("s", $phone);
$stmt2->execute();
$coupon_rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$claimed_names = array_column($coupon_rows, '優惠券名稱');

// 1️⃣ 完善個人資料
if (!empty($member['生日']) && !empty($member['地址']) && !empty($member['Email'])) {
    $tasks[0]['canClaim'] = true;
}
if (in_array("完善個人資料", $claimed_names)) {
    $tasks[0]['claimed'] = true;
}

// 2️⃣ 首次入會
$tasks[1]['canClaim'] = true; // 只要是會員就可以領
if (in_array("首次入會", $claimed_names)) {
    $tasks[1]['claimed'] = true;
}

// 3️⃣ 生日優惠
$currentMonth = date('m');
$birthMonth = $member['生日'] ? date('m', strtotime($member['生日'])) : null;
if ($birthMonth && $birthMonth === $currentMonth) {
    $tasks[2]['canClaim'] = true;
}
if (in_array("生日優惠", $claimed_names)) {
    $tasks[2]['claimed'] = true;
}

echo json_encode([
    "member" => $member,
    "tasks" => $tasks
]);
?>
