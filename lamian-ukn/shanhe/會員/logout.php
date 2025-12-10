<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. 清掉所有 session 變數
    $_SESSION = [];

    // 2. 把 session cookie 也清掉（比較保險）
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // 3. 銷毀 session
    session_destroy();

    // 4. 導回登入頁
    header("Location: ../login.html");
    exit;
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    echo "不允許的請求方式。";
    exit;
}
