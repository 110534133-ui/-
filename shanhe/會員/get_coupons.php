<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
include 'config.php';

if (!isset($_SESSION['member_phone'])) {
    echo json_encode(['success' => false, 'message' => '尚未登入']);
    exit;
}

$phone = $_SESSION['member_phone'];

try {
    $stmt = $conn->prepare("
        SELECT 優惠券編號, 優惠券名稱, 狀態, 到期日, 領取時間
        FROM ramen_coupons
        WHERE 電話 = ?
        ORDER BY 領取時間 DESC
    ");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    $coupons = [];
    while ($row = $result->fetch_assoc()) {
        $coupons[] = [
            '優惠券編號' => $row['優惠券編號'],
            '優惠券名稱' => $row['優惠券名稱'],
            '狀態' => $row['狀態'] ?: '未使用',
            '到期日' => $row['到期日'] ?: '-',
            '領取時間' => $row['領取時間'] ?: '-'
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($coupons),
        'data' => $coupons
    ]);
    exit;

} catch (Exception $e) {
    error_log("get_coupons error: ".$e->getMessage());
    echo json_encode(['success' => false, 'message' => '系統錯誤']);
    exit;
}
?>
