<?php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'lamian';   // ← 你的資料庫名
$DB_USER = 'root';
$DB_PASS = '';         // XAMPP 預設空字串

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

try {
  $pdo = new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
    $DB_USER, $DB_PASS, $options
  );
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'DB connect failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}
