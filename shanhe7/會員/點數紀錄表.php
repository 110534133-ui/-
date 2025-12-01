<?php
// 點數紀錄表.php
session_start();
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

// 🔸 檢查登入
if (!isset($_SESSION['member_phone'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

$phone = $_SESSION['member_phone'];

try {
    $records = [];

    // ✅ 1️⃣ 撈取「獲得點數」紀錄（來自 ramen_orders）
    $sql_earned = "
        SELECT 
            訂單日期 AS 日期, 
            '獲得點數' AS 類型, 
            獲得點數 AS 點數, 
            總金額 AS 金額, 
            商品明細 AS 備註
        FROM ramen_orders
        WHERE 電話 = ? AND 獲得點數 > 0
    ";
    $stmt1 = $conn->prepare($sql_earned);
    $stmt1->bind_param("s", $phone);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    while ($row = $result1->fetch_assoc()) {
        $records[] = $row;
    }

    // ✅ 2️⃣ 撈取「使用點數」紀錄（來自 ramen_coupons）
    $sql_used = "
        SELECT 
            領取時間 AS 日期, 
            '使用點數' AS 類型, 
            r.需要點數 AS 點數,
            NULL AS 金額,
            c.優惠券名稱 AS 備註
        FROM ramen_coupons c
        LEFT JOIN ramen_rewards r ON c.優惠券名稱 = r.商品名稱
        WHERE c.電話 = ? AND r.需要點數 IS NOT NULL
    ";
    $stmt2 = $conn->prepare($sql_used);
    $stmt2->bind_param("s", $phone);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $records[] = $row;
    }

    // ✅ 合併排序（日期由新到舊）
    usort($records, function ($a, $b) {
        return strtotime($b['日期']) - strtotime($a['日期']);
    });

    echo json_encode(['success' => true, 'data' => $records]);
    exit;

} catch (Exception $e) {
    error_log("點數紀錄表 error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系統錯誤']);
    exit;
}

$conn->close();
?>
