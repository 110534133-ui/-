<?php
// db_auth.php

// 防止重複載入
if (defined('AUTH_DB_LOADED')) {
    return;
}
define('AUTH_DB_LOADED', true);

// 密碼重設資料庫設定（可以和主資料庫相同或分開）
if (!defined('AUTH_DB_HOST')) define('AUTH_DB_HOST', '127.0.0.1');
if (!defined('AUTH_DB_NAME')) define('AUTH_DB_NAME', 'lamian'); // 或 'password'
if (!defined('AUTH_DB_USER')) define('AUTH_DB_USER', 'root');
if (!defined('AUTH_DB_PASS')) define('AUTH_DB_PASS', '');

function pdo_auth() {
    static $pdo = null;
    if ($pdo) return $pdo;
    
    $dsn = 'mysql:host=' . AUTH_DB_HOST . ';dbname=' . AUTH_DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, AUTH_DB_USER, AUTH_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]);
        error_log("✅ Auth DB connected to: " . AUTH_DB_NAME);
    } catch (PDOException $e) {
        error_log("❌ Auth DB Connection Error: " . $e->getMessage());
        die(json_encode(['error' => '認證資料庫連線失敗'], JSON_UNESCAPED_UNICODE));
    }
    return $pdo;
}
?>

