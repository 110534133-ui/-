<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'config.php';

if (!isset($_SESSION['member_phone'])) {
    echo json_encode(['success' => false, 'message' => '尚未登入']);
    exit;
}

$phone = $_SESSION['member_phone'];

if (!isset($_POST['useCouponId'])) {
    echo json_encode(['success' => false, 'message' => '缺少優惠券編號']);
    exit;
}

$couponId = intval($_POST['useCouponId']);

try {
    // 先檢查優惠券是否屬於此會員、未過期且未使用
    $stmt = $conn->prepare("
        SELECT 狀態, 到期日 
        FROM ramen_coupons 
        WHERE 優惠券編號 = ? AND 電話 = ?
        LIMIT 1
    ");
    $stmt->bind_param("is", $couponId, $phone);
    $stmt->execute();
    $coupon = $stmt->get_result()->fetch_assoc();

    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => '找不到此優惠券']);
        exit;
    }

    if ($coupon['狀態'] === '已使用') {
        echo json_encode(['success' => false, 'message' => '此優惠券已使用']);
        exit;
    }

    $today = date('Y-m-d');
    if ($coupon['到期日'] && $today > $coupon['到期日']) {
        echo json_encode(['success' => false, 'message' => '此優惠券已過期']);
        exit;
    }

    // 更新狀態為已使用
    $stmt = $conn->prepare("UPDATE ramen_coupons SET 狀態='已使用' WHERE 優惠券編號 = ? AND 電話 = ?");
    $stmt->bind_param("is", $couponId, $phone);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => '優惠券已成功使用']);

} catch (Exception $e) {
    error_log("use_coupon error: ".$e->getMessage());
    echo json_encode(['success' => false, 'message' => '系統錯誤']);
    exit;
}
?>
