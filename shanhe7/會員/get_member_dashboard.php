<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'config.php';

// 檢查登入狀態
if (!isset($_SESSION['member_phone'])) {
    echo json_encode(['success' => false, 'message' => '尚未登入']);
    exit;
}

$member_phone = $_SESSION['member_phone'];

try {
    // 查會員基本資料
    $sql = "SELECT 姓名, 會員點數 FROM ramen_members WHERE 電話 = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $member_phone);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$member) {
        echo json_encode(['success' => false, 'message' => '找不到會員資料']);
        exit;
    }

    // 計算累計消費
    $sql = "SELECT IFNULL(SUM(total_amount), 0) AS total_spent, COUNT(*) AS total_orders
            FROM lamain_orders WHERE member_phone = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $member_phone);
    $stmt->execute();
    $orderStats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total_spent = (int)$orderStats['total_spent'];
    $total_orders = (int)$orderStats['total_orders'];

    // 查優惠券數量
    $sql = "SELECT COUNT(*) AS available_coupons
            FROM lamain_coupons
            WHERE member_phone = ? AND status = '未使用' AND expiry_date >= CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $member_phone);
    $stmt->execute();
    $couponData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $available_coupons = (int)$couponData['available_coupons'];

    // 🔸 計算會員等級
    if ($total_spent >= 1500) {
        $level = "白金會員";
        $nextLevel = "MAX";
        $nextTarget = 2000;
    } elseif ($total_spent >= 1000) {
        $level = "黃金會員";
        $nextLevel = "白金會員";
        $nextTarget = 1500;
    } elseif ($total_spent >= 600) {
        $level = "銀卡會員";
        $nextLevel = "黃金會員";
        $nextTarget = 1000;
    } elseif ($total_spent > 0) {
        $level = "一般會員";
        $nextLevel = "銀卡會員";
        $nextTarget = 600;
    } else {
        $level = "無會員";
        $nextLevel = null;
        $nextTarget = null;
    }

    // 🔹 計算升級進度百分比
    $progress = 0;
    if ($nextTarget && $total_spent < $nextTarget) {
        $prevTarget = 0;
        if ($level === "一般會員") $prevTarget = 0;
        elseif ($level === "銀卡會員") $prevTarget = 600;
        elseif ($level === "黃金會員") $prevTarget = 1000;
        elseif ($level === "白金會員") $prevTarget = 1500;
        $progress = round((($total_spent - $prevTarget) / ($nextTarget - $prevTarget)) * 100);
    } elseif ($level === "白金會員") {
        $progress = 100;
    }

    echo json_encode([
        'success' => true,
        'member' => [
            'name' => $member['姓名'],
            'points' => $member['會員點數'],
            'total_spent' => $total_spent,
            'total_orders' => $total_orders,
            'available_coupons' => $available_coupons,
            'level' => $level,
            'nextLevel' => $nextLevel,
            'nextTarget' => $nextTarget,
            'progress' => $progress
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '伺服器錯誤']);
    error_log("會員儀表板錯誤：" . $e->getMessage());
}
?>
