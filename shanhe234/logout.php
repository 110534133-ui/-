<?php
session_start();
session_unset(); // 清空 session 變數
session_destroy(); // 銷毀 session

// 跳回登入頁面
header("Location: login.html");
exit;
?>
