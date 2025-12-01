<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['member_id'])) {
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

// 使用您的登入系統的連線方式
require_once "config.php";

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => '無效的訂單資料']);
    exit;
}

try {
    // 開始交易
    $conn->begin_transaction();

    // 獲取最後一筆訂單編號
    $sql = "SELECT 訂單編號 FROM ramen_orders ORDER BY 訂單編號 DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $lastOrder = $result->fetch_assoc();
        $lastNumber = intval(substr($lastOrder['訂單編號'], 11));
        $newNumber = $lastNumber + 1;
        $orderNumber = 'ORD' . date('Ymd') . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    } else {
        $orderNumber = 'ORD' . date('Ymd') . '0001';
    }
    
    // 商品明細轉為「商品名稱 x數量」的格式
    $itemsText = '';
    foreach ($input['items'] as $item) {
        $itemsText .= $item['name'] . ' x' . $item['quantity'] . ', ';
    }
    // 去掉最後的逗號和空格
    $itemsText = rtrim($itemsText, ', ');
    
    // 獲取當前日期時間
    $orderDate = date('Y-m-d H:i:s');
    
    // 插入訂單（加入訂單日期欄位）
    $stmt = $conn->prepare("INSERT INTO ramen_orders (訂單編號, 電話, 總金額, 獲得點數, 商品明細, 訂單日期) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiss", 
        $orderNumber,
        $input['phone'],
        $input['totalAmount'],
        $input['totalPoints'],
        $itemsText,
        $orderDate
    );
    
    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'orderNumber' => $orderNumber,
            'message' => '訂單建立成功',
            'orderDate' => $orderDate
        ]);
    } else {
        throw new Exception("執行 SQL 失敗: " . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false, 
        'message' => '訂單建立失敗: ' . $e->getMessage()
    ]);
}

$conn->close();
?>