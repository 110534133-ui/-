<?php
header('Content-Type: application/json');
ini_set('display_errors', 0); // 關閉 PHP 輸出錯誤，避免破壞 JSON

$host = 'localhost';
$dbname = 'lamian';
$user = 'root';
$pass = '';

// 取得 id
$id = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
} else {
    // DELETE 方法也可以從 php://input 取得
    parse_str(file_get_contents("php://input"), $data);
    if (isset($data['id'])) $id = intval($data['id']);
}

if (!$id) {
    echo json_encode(['success' => false, 'message' => '缺少ID']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $stmt = $pdo->prepare("DELETE FROM daily_report WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
