<?php
session_start();
require_once 'config.php';

// 無任何 HTML/空白輸出
header('Content-Type: application/json; charset=utf-8');

// 卻可登入
if (!isset($_SESSION['member_phone'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '請求方式錯誤']);
    exit;
}

$phone = $_SESSION['member_phone'];
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');

$address = trim($_POST['address'] ?? '');
$password = trim($_POST['password'] ?? '');

// 姓名必填
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => '請輸入姓名']);
    exit;
}

// Email 格式驗證
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email 格式不正確']);
    exit;
}

// 密碼驗證
if (!empty($password) && strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => '密碼長度至少需要6個字元']);
    exit;
}

try {
    $emailValue = empty($email) ? null : $email;
    $birthdayValue = empty($birthday) ? null : $birthday;
    $addressValue = empty($address) ? null : $address;

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE ramen_members SET 姓名 = ?, Email = ?, 生日 = ?, 地址 = ?, 密碼 = ? WHERE 電話 = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $name, $emailValue, $birthdayValue, $addressValue, $hashedPassword, $phone);
    } else {
        $sql = "UPDATE ramen_members SET 姓名 = ?, Email = ?, 生日 = ?, 地址 = ? WHERE 電話 = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $emailValue, $birthdayValue, $addressValue, $phone);
    }

    if ($stmt->execute()) {
        $_SESSION['member_name'] = $name;
        echo json_encode(['success' => true, 'message' => '資料已成功更新']);
    } else {
        echo json_encode(['success' => false, 'message' => '更新失敗：' . $conn->error]);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '系統錯誤']);
    error_log("更新會員資料錯誤: " . $e->getMessage());
}

$conn->close();
?>
