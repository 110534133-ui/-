<?php
// 班表管理API.php
// 提供班表管理所需的 API 端點

session_start();
header('Content-Type: application/json; charset=utf-8');

// ===== 資料庫連線 =====
$host = 'localhost';
$db   = 'lamian';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '資料庫連線失敗'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 檢查登入 =====
if (!isset($_SESSION['uid']) || empty($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => '未登入'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 檢查權限 (只有 A 或 B 可使用) =====
$userLevel = $_SESSION['user_level'] ?? $_SESSION['role'] ?? $_SESSION['role_code'] ?? 'C';

if (!in_array($userLevel, ['A', 'B'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => '權限不足'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 處理請求 =====
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'employees':
        // 取得全體員工清單
        try {
            $stmt = $pdo->query("
                SELECT id, name, position, role
                FROM 員工基本資料
                ORDER BY name
            ");
            
            $employees = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'employees' => $employees
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => '查詢失敗: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        break;
    
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => '不支援的操作'
        ], JSON_UNESCAPED_UNICODE);
}
?>