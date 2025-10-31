<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'config.php';

if (!isset($_SESSION['member_phone'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

$phone = $_SESSION['member_phone'];

try {
    // ✅ 先抓所有優惠券資料
    $stmt = $conn->prepare("
        SELECT 
            c.優惠券編號, 
            c.優惠券名稱, 
            c.狀態, 
            c.到期日, 
            c.領取時間, 
            r.需要點數
        FROM ramen_coupons c
        LEFT JOIN ramen_rewards r ON c.優惠券名稱 = r.商品名稱
        WHERE c.電話 = ?
        ORDER BY c.領取時間 DESC
    ");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    $coupons = [];
    while ($row = $result->fetch_assoc()) {
        $coupons[] = [
            '優惠券編號' => $row['優惠券編號'],
            '優惠券名稱' => $row['優惠券名稱'],
            '狀態' => $row['狀態'] ?: '未使用', // 如果狀態空就顯示「未使用」
            '到期日' => $row['到期日'] ?: '-',   // 空的到期日顯示 "-"
            '領取時間' => $row['領取時間'] ?: '-', 
            '使用點數' => $row['需要點數'] !== null ? intval($row['需要點數']) : '-' // 找不到商品就 "-"
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($coupons),
        'data' => $coupons
    ]);
    exit;

} catch (Exception $e) {
    error_log("get_coupons error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系統錯誤']);
    exit;
}
?>
