<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_auth.php';
try {
  $v = pdo_auth()->query('select version() as v')->fetch()['v'] ?? 'unknown';
  echo json_encode(['ok'=>true,'mysql_version'=>$v], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
