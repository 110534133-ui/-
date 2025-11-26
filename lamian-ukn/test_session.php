<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
    'permission' => $_SESSION['permission'] ?? 'NOT SET',
    'username' => $_SESSION['username'] ?? 'NOT SET',
    'all_session' => $_SESSION
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>