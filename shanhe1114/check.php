<?php
session_start();
$conn = new mysqli("localhost", "root", "", "lamain");
if ($conn->connect_error) die("連線失敗: " . $conn->connect_error);

$username = $_POST['username'];
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);
$name     = $_POST['name'];
$phone    = $_POST['phone'];
$email    = $_POST['email'];
$birthday = $_POST['birthday'];
$address  = $_POST['address'];

// 手機驗證
if (!preg_match("/^\d{10}$/", $phone)) {
    die("手機號碼格式錯誤！");
}

// 檢查是否重複
$check = $conn->prepare("SELECT id FROM member WHERE 電話=? OR Email=? OR 帳號=?");
$check->bind_param("sss", $phone, $email, $username);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    die("此帳號/電話/Email 已被註冊，請重新輸入！");
}
$check->close();

// 暫存到 session 等待驗證碼
$_SESSION['pending_member'] = [
    "username" => $username,
    "password" => $password,
    "name"     => $name,
    "phone"    => $phone,
    "email"    => $email,
    "birthday" => $birthday,
    "address"  => $address
];

// 產生驗證碼
$code = rand(100000, 999999);
$_SESSION['verification_code'] = $code;

// 模擬發送 SMS
file_put_contents("sms_log.txt", "發送到 {$phone} 的驗證碼: $code\n", FILE_APPEND);

// 跳轉驗證頁
header("Location: verify.php");
exit;
