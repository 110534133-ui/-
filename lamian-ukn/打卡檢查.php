<?php
header('Content-Type: application/json');
include "db.php";  // 你的PDO連線

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data["user_id"];
$action = $data["action"];
$token = $data["token"];

// 1. 檢查 token 是否在允許清單
$sql = $pdo->prepare("SELECT 1 FROM device_tokens WHERE token = ?");
$sql->execute([$token]);

if ($sql->rowCount() === 0) {
    echo json_encode(["error" => "此裝置未授權，請聯絡管理員"]);
    exit;
}

// 2. 寫入打卡紀錄
$stmt = $pdo->prepare("INSERT INTO attendance (user_id, action, punch_time, token)
                       VALUES (?, ?, NOW(), ?)");
$stmt->execute([$user_id, $action, $token]);

echo json_encode(["message" => "打卡成功"]);
?>
