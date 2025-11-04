<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include 'config.php';
session_start();

// 如果收到 useCouponId，更新優惠券狀態
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['useCouponId'])) {
    $couponId = $_POST['useCouponId'];
    $phone = $_SESSION['phone'];

    // 1️⃣ 查詢優惠券的當前狀態
    $stmt = $conn->prepare("SELECT 優惠券編號, 狀態 FROM ramen_coupons WHERE 優惠券編號=? AND 電話=?");
    $stmt->bind_param("ss", $couponId, $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $coupon = $result->fetch_assoc();

    // 2️⃣ 檢查優惠券是否存在
    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => '優惠券不存在']);
        exit;
    }

    // 3️⃣ 檢查優惠券是否已經被使用
    if ($coupon['狀態'] === '已使用') {
        echo json_encode(['success' => false, 'message' => '此優惠券已經被使用']);
        exit;
    }

    // 4️⃣ 更新優惠券狀態為「已使用」
    $stmt = $conn->prepare("UPDATE ramen_coupons SET 狀態='已使用' WHERE 優惠券編號=? AND 電話=? AND 狀態='未使用'");
    $stmt->bind_param("ss", $couponId, $phone);
    $stmt->execute();

    // 5️⃣ 根據更新結果返回成功或失敗的訊息
    if ($stmt->affected_rows > 0) {
        // 更新成功，返回優惠券的最新狀態
        echo json_encode([
            'success' => true,
            'message' => '兌換成功！！',
            'couponId' => $couponId, // 返回優惠券編號
            'newStatus' => '已使用'   // 返回更新後的狀態
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '更新失敗，可能已使用或不存在']);
    }
    exit;
}


// 🔹 從登入 session 取會員電話
if (!isset($_SESSION['phone'])) {
    echo json_encode([
        'success' => false,
        'message' => '尚未登入或 session 已過期'
    ]);
    exit;
}

$phone = $_SESSION['phone'];

try {
    // 1️⃣ 查會員基本資料
    $stmt = $conn->prepare("SELECT 姓名, 會員點數, 生日, Email, 地址 FROM ramen_members WHERE 電話 = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();

    if (!$member) {
        echo json_encode([
            'success' => false,
            'message' => '找不到該會員資料'
        ]);
        exit;
    }

    $memberPoints = intval($member['會員點數'] ?? 0);
    $memberName   = $member['姓名'];
    $birthday     = $member['生日'] ?? null;

    // 2️⃣ 從 ramen_orders 計算訂單累計獲得點數、本月獲得點數
    $stmt = $conn->prepare("
        SELECT 
            IFNULL(SUM(獲得點數), 0) AS totalEarnedPoints,
            IFNULL(SUM(CASE WHEN YEAR(訂單日期)=YEAR(CURDATE()) AND MONTH(訂單日期)=MONTH(CURDATE()) THEN 獲得點數 ELSE 0 END), 0) AS monthEarned
        FROM ramen_orders
        WHERE 電話 = ?
    ");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $pointsData = $stmt->get_result()->fetch_assoc();
    $totalEarnedPoints = intval($pointsData['totalEarnedPoints'] ?? 0);
    $monthEarned       = intval($pointsData['monthEarned'] ?? 0);

    // 🔹 加總會員點數 + 訂單累計點數
    $totalPoints = $memberPoints + $totalEarnedPoints;

    // 3️⃣ 累計消費與本月消費次數
    $stmt = $conn->prepare("
        SELECT 
            IFNULL(SUM(總金額),0) AS totalSpent,
            IFNULL(SUM(CASE WHEN YEAR(訂單日期)=YEAR(CURDATE()) AND MONTH(訂單日期)=MONTH(CURDATE()) THEN 總金額 ELSE 0 END),0) AS monthSpent,
            COUNT(*) AS totalOrders,
            SUM(CASE WHEN YEAR(訂單日期)=YEAR(CURDATE()) AND MONTH(訂單日期)=MONTH(CURDATE()) THEN 1 ELSE 0 END) AS monthOrders
        FROM ramen_orders
        WHERE 電話 = ?
    ");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $spending = $stmt->get_result()->fetch_assoc();

    $totalSpent  = intval($spending['totalSpent'] ?? 0);
    $monthSpent  = intval($spending['monthSpent'] ?? 0);
    $monthOrders = intval($spending['monthOrders'] ?? 0);

    // 4️⃣ 計算本月使用點數（暫設 0）
    $monthUsed = 0;

    // 5️⃣ 判斷任務
    $tasks = [
        '完善個人資料' => !empty($member['Email']) && !empty($member['地址']),
        '首次消費'     => false,
        '生日優惠'     => false
    ];

    // 首次消費
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM ramen_orders WHERE 電話 = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $firstPurchase = intval($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $tasks['首次消費'] = $firstPurchase > 0;

    // 生日優惠
    if (!empty($birthday)) {
        $birthMonth = date('m', strtotime($birthday));
        $currentMonth = date('m');
        if ($birthMonth == $currentMonth) $tasks['生日優惠'] = true;
    }

    // 待領任務數量
    $pendingTasks = 0;
    foreach ($tasks as $t => $available) {
        if (!$available) $pendingTasks++;
    }

    // ✅ 回傳 JSON
    echo json_encode([
        'success'      => true,
        'memberName'   => $memberName,
        'totalPoints'  => $totalPoints,
        'monthEarned'  => $monthEarned,
        'monthUsed'    => $monthUsed,
        'tasks'        => $tasks,
        'pendingTasks' => $pendingTasks,
        'totalSpent'   => $totalSpent,
        'monthSpent'   => $monthSpent,
        'monthOrders'  => $monthOrders
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '程式錯誤：' . $e->getMessage()
    ]);
}
