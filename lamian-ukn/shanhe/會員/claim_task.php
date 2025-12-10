<?php
require_once "config.php";
session_start();

header('Content-Type: application/json');

$phone = $_SESSION['phone'] ?? null;
$taskName = $_POST['taskName'] ?? null;
$points = intval($_POST['points'] ?? 0);

if (!$phone || !$taskName) {
    echo json_encode(["success" => false, "message" => "資料不完整"]);
    exit;
}

// 檢查是否已領取過
$sql_check = "SELECT COUNT(*) as cnt FROM ramen_coupons WHERE 電話 = ? AND 優惠券名稱 = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("ss", $phone, $taskName);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc()['cnt'];

if ($count > 0) {
    echo json_encode(["success" => false, "message" => "已領取過"]);
    exit;
}

// 新增領取紀錄
$sql_insert = "INSERT INTO ramen_coupons (電話, 優惠券名稱, 狀態, 到期日) VALUES (?, ?, '已使用', DATE_ADD(CURDATE(), INTERVAL 30 DAY))";
$stmt2 = $conn->prepare($sql_insert);
$stmt2->bind_param("ss", $phone, $taskName);
$stmt2->execute();

// 更新會員點數
$sql_update = "UPDATE ramen_members SET 會員點數 = 會員點數 + ? WHERE 電話 = ?";
$stmt3 = $conn->prepare($sql_update);
$stmt3->bind_param("is", $points, $phone);
$stmt3->execute();

echo json_encode(["success" => true, "message" => "任務領取成功！"]);
?>
