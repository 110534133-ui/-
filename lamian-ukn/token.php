<?php
// token.php
header('Content-Type: application/json');

// 1. 確保使用 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '僅允許 POST 方法'
    ]);
    exit;
}

// 2. 取得 POST 資料
$input = json_decode(file_get_contents('php://input'), true);
$id = trim($input['id'] ?? '');

if (!$id) {
    echo json_encode([
        'success' => false,
        'message' => '缺少員工 ID'
    ]);
    exit;
}

// 3. 連接資料庫 (依你環境修改)
$host = 'localhost';
$db   = 'lamian';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '資料庫連線失敗: ' . $e->getMessage()
    ]);
    exit;
}

// 4. 執行更新，清空 device_token
$sql = "UPDATE `員工基本資料` SET `device_token` = NULL WHERE `id` = :id";
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute(['id' => $id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => "員工 $id 的 token 已清除"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "找不到員工或 token 已為空"
        ]);
    }
} catch (\PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '更新失敗: ' . $e->getMessage()
    ]);
}
