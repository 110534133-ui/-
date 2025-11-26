<?php
// 賬號系統專用（不動你原本的 config.php）
const DB_HOST = '127.0.0.1';
const DB_NAME = 'lamian';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

// 你的使用者表（照你截圖）
const TABLE_USER       = '員工基本資料';
const USER_PK_COL      = 'id';
const USER_ACCOUNT_COL = 'account';
const USER_NAME_COL    = 'name';

// session 安全
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
