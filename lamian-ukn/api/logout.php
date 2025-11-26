<?php
// /lamian-ukn/api/logout.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

ini_set('session.cookie_path', '/');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, '/');
}
session_destroy();
echo json_encode(['ok'=>true,'message'=>'LOGOUT_OK'], JSON_UNESCAPED_UNICODE);
