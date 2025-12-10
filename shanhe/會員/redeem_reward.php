<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
include 'config.php';

if (!isset($_SESSION['member_id'])) {
    echo json_encode(['success' => false, 'message' => '尚未登入，請先登入帳號']);
    exit;
}

$memberId = $_SESSION['member_id'];

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

    // 2️⃣ 計算會員總點數（會員點數 + 訂單累積點數）
    // 抓會員原始點數
    $stmt = $conn->prepare("SELECT 會員點數, 電話 FROM ramen_members WHERE id = ?");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $memberData = $stmt->get_result()->fetch_assoc();
    $memberPoints = intval($memberData['會員點數'] ?? 0);
    $phone = $memberData['電話'];

    // 抓會員訂單累積點數
    $stmt = $conn->prepare("SELECT IFNULL(SUM(獲得點數),0) AS totalEarnedPoints FROM ramen_orders WHERE 電話 = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $orderData = $stmt->get_result()->fetch_assoc();
    $orderPoints = intval($orderData['totalEarnedPoints'] ?? 0);

    $totalPoints = $memberPoints + $orderPoints;

    if ($totalPoints < $neededPoints) {
        echo json_encode([
            'success' => false,
            'message' => "點數不足，無法兌換！需要 {$neededPoints} 點，您目前有 {$totalPoints} 點"
        ]);
        exit;
    }

    // 3️⃣ 開始事務
    $conn->begin_transaction();

    // 扣掉點數（這裡直接從會員點數扣，如果你要扣總點數，也可以調整）
    // ⚠️ 如果想扣掉訂單累積點數，需要自己寫邏輯分配扣哪部分
    $stmt = $conn->prepare("UPDATE ramen_members SET 會員點數 = 會員點數 - ? WHERE id = ?");
    $stmt->bind_param("ii", $neededPoints, $memberId);
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

    // 4️⃣ 回傳扣完點數後的總點數給前端
    echo json_encode([
        'success' => true,
        'message' => '兌換成功！優惠券已加入您的帳號',
        'memberPoints' => $totalPoints - $neededPoints,
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
