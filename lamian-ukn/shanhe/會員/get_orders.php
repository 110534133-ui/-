<?php //訂單紀錄
session_start();
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['member_phone'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

$phone = $_SESSION['member_phone'];

try {
    $stmt = $conn->prepare("SELECT 訂單編號, 訂單日期, 總金額, 獲得點數, 商品明細, 建立時間 FROM ramen_orders WHERE 電話 = ? ORDER BY 訂單日期 DESC");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $orders]);
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤']);
    error_log("取得訂單錯誤: " . $e->getMessage());
}

$conn->close();
?>
