<?php
require_once __DIR__ . '/config.php';
session_start();

function json($data, int $status=200){
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function current_user_id(): ?int {
  if (isset($_SESSION['uid'])) return (int)$_SESSION['uid'];
  return null;
}

function require_login(): int {
  $uid = current_user_id();
  if (!$uid) json(['error'=>'UNAUTHORIZED'], 401);
  return $uid;
}
