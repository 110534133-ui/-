<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once '../config.php'; // 根據實際路徑修改

if (!isset($_SESSION['member_phone'])) {
    echo json_encode(['success' => false, 'message' => '尚未登入']);
    exit;
}

$member_phone = $_SESSION['member_phone'];

try {
    // 1️⃣ 查會員姓名
    $stmt = $conn->prepare("SELECT 姓名 FROM ramen_members WHERE 電話 = ?");
    $stmt->bind_param('s', $member_phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();
    $stmt->close();

    if (!$member) {
        // 會員不存在
        echo json_encode(['success' => false, 'message' => '找不到會員資料']);
        exit;
    }

    // 2️⃣ 計算累計消費
    $stmt = $conn->prepare("SELECT IFNULL(SUM(總金額),0) AS total_spent FROM ramen_orders WHERE 電話 = ?");
    $stmt->bind_param('s', $member_phone);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total_spent = (int)($order['total_spent'] ?? 0);

    // 3️⃣ 判斷會員等級
    if ($total_spent >= 5000) {
        $level = "鑽石會員";
        $nextTarget = null;
    } elseif ($total_spent >= 2000) {
        $level = "白金會員";
        $nextTarget = 5000;
    } elseif ($total_spent >= 1) {
        $level = "黃金會員";
        $nextTarget = 2000;
    } else {
        $level = "一般會員";
        $nextTarget = 1;
    }

    // 4️⃣ 計算進度百分比
    if ($nextTarget) {
        $progress = round(($total_spent / $nextTarget) * 100);
        $remaining = $nextTarget - $total_spent;
    } else {
        $progress = 100;
        $remaining = 0;
    }

    // 5️⃣ 回傳 JSON
    echo json_encode([
        'success' => true,
        'name' => $member['姓名'],
        'level' => $level,
        'total_spent' => $total_spent,
        'nextTarget' => $nextTarget,
        'progress' => $progress,
        'remaining' => $remaining
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤: ' . $e->getMessage()]);
}
?>
