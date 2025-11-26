<?php
// /lamian-ukn/api/auth_guard.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// 登入後請把 $_SESSION['uid'] 或 $_SESSION['user_id'] 設起來
$uid = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? null;
if (!$uid) {
  header('Location: /lamian-ukn/login.php');
  exit;
}
// 正規化：統一用 uid
$_SESSION['uid'] = (int)$uid;

require_once __DIR__ . '/config.php'; // ← 注意這裡沒有多一層 api/

// 可選：取簡要個資讓頁面顯示
try {
  $pdo = pdo();
  $stmt = $pdo->prepare("SELECT id, name, email, Telephone AS phone, Position AS title
                         FROM `員工基本資料` WHERE id = ?");
  $stmt->execute([$_SESSION['uid']]);
  $ME = $stmt->fetch() ?: null;
} catch (Throwable $e) {
  $ME = null;
}
