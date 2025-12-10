<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
include 'config.php';

if (!isset($_SESSION['member_phone'])) {
    echo json_encode(['success' => false, 'message' => '尚未登入，請先登入帳號']);
    exit;
}

$phone = $_SESSION['member_phone'];

if (!isset($_POST['優惠券編號'])) {
    echo json_encode(['success' => false, 'message' => '缺少優惠券編號']);
    exit;
}

$rewardId = intval($_POST['優惠券編號']);

try {
    // 1️⃣ 取商品名稱和需要點數
    $stmt = $conn->prepare("SELECT 商品名稱, 需要點數 FROM ramen_rewards WHERE 優惠券編號 = ?");
    $stmt->bind_param("i", $rewardId);
    $stmt->execute();
    $reward = $stmt->get_result()->fetch_assoc();

    if (!$reward) {
        echo json_encode(['success' => false, 'message' => '找不到商品']);
        exit;
    }

    $neededPoints = intval($reward['需要點數']);

    // 2️⃣ 計算會員總點數
    $stmt = $conn->prepare("SELECT 會員點數 FROM ramen_members WHERE 電話 = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
    $memberPoints = intval($member['會員點數'] ?? 0);

    if ($memberPoints < $neededPoints) {
        echo json_encode([
            'success' => false,
            'message' => "點數不足，無法兌換！需要 {$neededPoints} 點，您目前有 {$memberPoints} 點"
        ]);
        exit;
    }

    // 3️⃣ 開始事務
    $conn->begin_transaction();

    // 扣點數
    $stmt = $conn->prepare("UPDATE ramen_members SET 會員點數 = 會員點數 - ? WHERE 電話 = ?");
    $stmt->bind_param("is", $neededPoints, $phone);
    $stmt->execute();

    // 寫入優惠券
    $couponName = $reward['商品名稱'];
    $receiveTime = date('Y-m-d H:i:s');
    $expireDate = date('Y-m-d', strtotime('+3 months'));

    $stmt = $conn->prepare("
        INSERT INTO ramen_coupons (電話, 優惠券名稱, 狀態, 到期日, 領取時間)
        VALUES (?, ?, '未使用', ?, ?)
    ");
    $stmt->bind_param("ssss", $phone, $couponName, $expireDate, $receiveTime);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => '兌換成功！優惠券已加入您的帳號',
        'coupon' => [
            '優惠券名稱' => $couponName,
            '狀態' => '未使用',
            '到期日' => $expireDate,
            '領取時間' => $receiveTime,
            '使用點數' => $neededPoints
        ]
    ]);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log("redeem_reward error: ".$e->getMessage());
    echo json_encode(['success' => false, 'message' => '兌換失敗']);
    exit;
}
?>
