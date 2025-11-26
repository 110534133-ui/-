<?php
/**
 * 檔案位置: config/database_config.php
 * 資料庫連線設定檔
 */

// 資料庫配置
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'lamian');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 允許的來源（根據你的環境調整）
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:8080',
    'http://127.0.0.1:8080',
]);

/**
 * 建立資料庫連接
 * @return PDO 返回 PDO 物件
 * @throws PDOException
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log('資料庫連接失敗: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * 設定 CORS 標頭
 */
function setCORSHeaders() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // 開發環境允許所有來源
    if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
        header('Access-Control-Allow-Origin: *');
    } elseif (in_array($origin, ALLOWED_ORIGINS)) {
        header("Access-Control-Allow-Origin: $origin");
    }
    
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
}

/**
 * 設定 JSON 回應標頭
 */
function setJSONHeaders() {
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * 處理 OPTIONS 預檢請求
 */
function handleOptionsRequest() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        setCORSHeaders();
        http_response_code(200);
        exit();
    }
}

/**
 * 回傳 JSON 錯誤訊息
 */
function sendJSONError($message, $statusCode = 500, $additionalData = []) {
    http_response_code($statusCode);
    echo json_encode(array_merge([
        'success' => false,
        'error' => $message
    ], $additionalData), JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * 回傳 JSON 成功訊息
 */
function sendJSONSuccess($data = [], $message = null) {
    http_response_code(200);
    $response = ['success' => true];
    if ($message) $response['message'] = $message;
    if (!empty($data)) $response = array_merge($response, $data);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}
?>