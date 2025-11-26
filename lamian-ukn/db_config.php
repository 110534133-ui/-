<?php
/**
 * 資料庫連線設定檔
 */

// 資料庫設定
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'lamian');  // 你的資料庫名稱
define('DB_USER', 'root');     // 請改成你的資料庫使用者名稱
define('DB_PASS', '');         // 請改成你的資料庫密碼
define('DB_CHARSET', 'utf8mb4');

// 建立 PDO 連線
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // 記錄錯誤但不顯示詳細資訊給使用者
        error_log("Database Connection Error: " . $e->getMessage());
        http_response_code(500);
        die(json_encode(['error' => '資料庫連線失敗']));
    }
}

// 設定時區
date_default_timezone_set('Asia/Taipei');

// 設定錯誤回報(開發環境)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// CORS 設定
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>