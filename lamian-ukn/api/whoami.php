<?php
// /lamian-ukn/api/whoami.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/auth_core.php';

if (empty($_SESSION['uid']) && empty($_SESSION['account'])) {
  echo json_encode(['login' => false, 'msg' => '未登入或 Session 無法識別'], JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode([
  'login'   => true,
  'uid'     => (int)($_SESSION['uid'] ?? 0),
  'account' => $_SESSION['account'] ?? null,
  'name'    => $_SESSION['name'] ?? null,
], JSON_UNESCAPED_UNICODE);
