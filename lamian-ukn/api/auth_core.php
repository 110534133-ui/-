<?php
// /lamian-ukn/api/auth_core.php
declare(strict_types=1);

ini_set('session.cookie_path', '/');

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require_once __DIR__ . '/config.php';

function pdo_core() {
  return pdo();
}

/**
 * 要求登入，回傳 uid
 */
function require_login_core(): string {
  if (empty($_SESSION['uid'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'UNAUTHORIZED'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  return (string)$_SESSION['uid'];
}

/**
 * 取得使用者角色
 */
function get_user_role(): string {
  return $_SESSION['role'] ?? 'employee';
}

/**
 * 取得使用者角色等級
 */
function get_user_role_level(): int {
  return (int)($_SESSION['role_level'] ?? 1);
}

/**
 * 檢查使用者權限
 * @param string $required_role boss|manager|employee
 */
function check_role(string $required_role): bool {
  $role_levels = [
    'boss'     => 3,
    'manager'  => 2,
    'employee' => 1,
  ];
  
  $user_level = get_user_role_level();
  $required_level = $role_levels[$required_role] ?? 1;
  
  return $user_level >= $required_level;
}

/**
 * 要求特定權限
 */
function require_role(string $required_role): void {
  if (!check_role($required_role)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'error' => 'FORBIDDEN',
      'message' => '權限不足'
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

/**
 * 是否為老闆
 */
function is_boss(): bool {
  return get_user_role() === 'boss';
}

/**
 * 是否為管理員或以上
 */
function is_manager(): bool {
  return check_role('manager');
}