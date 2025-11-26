<?php
declare(strict_types=1);
require_once __DIR__ . '/db_auth.php';

header('Content-Type: application/json; charset=utf-8');

// 把未捕捉例外轉成 JSON（開發期保留 detail，正式可改為不回 detail）
set_exception_handler(function(Throwable $e){
  http_response_code(200);
  echo json_encode(['ok'=>false,'error'=>'SERVER_ERROR: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
  exit;
}

// 讀入 JSON
$in = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
$account  = trim($in['account'] ?? '');
$password = (string)($in['password'] ?? '');
if ($account === '' || $password === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'缺少帳號或密碼'], JSON_UNESCAPED_UNICODE);
  exit;
}

// ★ 跨資料庫：查 lamian.員工基本資料
$sql = "SELECT `id`,`account`,`email`,`name`,`password_hash`
        FROM `".EMP_DB."`.`員工基本資料`
        WHERE `account` = ?
        LIMIT 1";
$stmt = pdo_auth()->prepare($sql);
$stmt->execute([$account]);
$user = $stmt->fetch();

if (!$user) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'帳號或密碼錯誤'], JSON_UNESCAPED_UNICODE);
  exit;
}

// 兼容：可能存明碼或雜湊；明碼比對成功後立刻轉雜湊
$stored   = (string)$user['password_hash'];
$isHashed = (bool)preg_match('/^\$2[aby]\$|^\$argon2(id)?\$/', $stored);

$passOK = false;
if ($isHashed) {
  $passOK = password_verify($password, $stored);
  if ($passOK && password_needs_rehash($stored, PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    pdo_auth()->prepare("UPDATE `".EMP_DB."`.`員工基本資料` SET `password_hash`=? WHERE `id`=?")
              ->execute([$newHash, (int)$user['id']]);
  }
} else {
  $passOK = hash_equals($stored, $password);
  if ($passOK) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    pdo_auth()->prepare("UPDATE `".EMP_DB."`.`員工基本資料` SET `password_hash`=? WHERE `id`=?")
              ->execute([$newHash, (int)$user['id']]);
  }
}

if (!$passOK) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'帳號或密碼錯誤'], JSON_UNESCAPED_UNICODE);
  exit;
}

// 開 session 並回應
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>false,'path'=>'/']);
  session_start();
}
$_SESSION['uid']      = (int)$user['id'];
$_SESSION['account']  = $user['account'];
$_SESSION['name']     = $user['name'];
$_SESSION['email']    = $user['email'];
$_SESSION['login_at'] = date('Y-m-d H:i:s');

echo json_encode(['ok'=>true,'user'=>[
  'id'=>(int)$user['id'],
  'account'=>$user['account'],
  'name'=>$user['name'],
  'email'=>$user['email'],
]], JSON_UNESCAPED_UNICODE);
