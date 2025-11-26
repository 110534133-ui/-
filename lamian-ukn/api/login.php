<?php
// /lamian-ukn/api/login.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_core.php';

$raw = file_get_contents('php://input');
$req = json_decode($raw ?: '[]', true);
$account = trim($req['account'] ?? '');
$password = trim($req['password'] ?? '');

if ($account === '' || $password === '') {
  http_response_code(400);
  echo json_encode(['error'=>'MISSING_CREDENTIALS'], JSON_UNESCAPED_UNICODE);
  exit;
}

// 依你的資料表/欄位調整
$sql = "SELECT id, account, name, password_hash
        FROM `員工基本資料`
        WHERE account = ?
        LIMIT 1";
$st = pdo()->prepare($sql);
$st->execute([$account]);
$u = $st->fetch();

if (!$u || !password_verify($password, $u['password_hash'])) {
  http_response_code(401);
  echo json_encode(['error'=>'BAD_CREDENTIALS'], JSON_UNESCAPED_UNICODE);
  exit;
}

// ✅ 登入成功 → 開啟 session 並寫入必要欄位
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
session_regenerate_id(true);
$_SESSION['uid']     = (int)$u['id'];
$_SESSION['account'] = $u['account'];
$_SESSION['name']    = $u['name'];

echo json_encode([
  'ok'      => true,
  'user'    => ['id'=>(int)$u['id'],'account'=>$u['account'],'name'=>$u['name']],
  'message' => 'LOGIN_OK'
], JSON_UNESCAPED_UNICODE);
