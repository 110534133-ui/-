<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
// 同步給舊系統用
if (isset($_SESSION['member_phone'])) {
    $_SESSION['phone'] = $_SESSION['member_phone'];
}
// 🔹 檢查是否已登入
if (!isset($_SESSION['member_phone'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

$phone = $_SESSION['member_phone'];

try {
    // 🔹 查詢會員基本資料
    $sql = "SELECT 姓名, 電話, Email, 生日, 地址, 會員點數, created_at 
            FROM ramen_members 
            WHERE 電話 = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => '找不到會員資料']);
        exit;
    }
    
    $member = $result->fetch_assoc();

    // 🔹 查 ramen_orders 的「獲得點數」加總
    $stmt2 = $conn->prepare("SELECT IFNULL(SUM(獲得點數), 0) AS earnedPoints FROM ramen_orders WHERE 電話 = ?");
    $stmt2->bind_param("s", $phone);
    $stmt2->execute();
    $earnedResult = $stmt2->get_result()->fetch_assoc();
    $earnedPoints = intval($earnedResult['earnedPoints'] ?? 0);

    // 🔹 合併點數：會員表 + 訂單加總
    $totalPoints = intval($member['會員點數'] ?? 0) + $earnedPoints;

    // 🔹 格式化註冊日期
    $regDate = '';
    if (!empty($member['created_at'])) {
        $regDate = date('Y-m-d', strtotime($member['created_at']));
    }

    // 🔹 回傳整理後資料
    $memberData = [
        'name' => $member['姓名'] ?? '',
        'phone' => $member['電話'] ?? '',
        'email' => $member['Email'] ?? '',
        'birthday' => $member['生日'] ?? '',
        'address' => $member['地址'] ?? '',
        'points' => $totalPoints, // ✅ 改成合併後的總點數
        'regDate' => $regDate
    ];

    echo json_encode([
        'success' => true,
        'member' => $memberData
    ]);

    $stmt->close();
    $stmt2->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤']);
    error_log("取得會員資料錯誤: " . $e->getMessage());
}

$conn->close();
?>
