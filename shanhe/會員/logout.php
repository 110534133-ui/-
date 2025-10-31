<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_unset();
    session_destroy();
    header("Location: ../login.html");
    exit;
} else {
    // 防止直接用網址 GET 進來
    header("HTTP/1.1 405 Method Not Allowed");
    echo "不允許的請求方式。";
    exit;
}
?>
