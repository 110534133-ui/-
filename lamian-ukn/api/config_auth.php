<?php
declare(strict_types=1);

/*
 * 登入模組專用設定（連到 DB: password）
 * 員工主檔實際在 lamian.員工基本資料（跨資料庫查）
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

// ★ 登入模組使用的資料庫（放忘記密碼暫存表）
const AUTH_DB_DSN  = 'mysql:host=127.0.0.1;dbname=password;charset=utf8mb4';
const AUTH_DB_USER = 'root';
const AUTH_DB_PASS = '';

// ★ 員工主檔所在的資料庫（請依你查到的結果，這裡是 lamian）
const EMP_DB = 'lamian';

// 忘記密碼用參數
const RESET_CODE_TTL_MIN = 10; // 驗證碼有效分鐘數
const RESET_MAX_ATTEMPTS = 5;  // 驗證碼錯誤上限

// 寄信（先用 mail()，之後可換 SMTP）
const MAIL_FROM_EMAIL = 'no-reply@example.com';
const MAIL_FROM_NAME  = '員工管理系統';
