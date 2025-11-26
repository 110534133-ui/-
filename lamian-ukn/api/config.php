<?php
// /lamian-ukn/api/config.php

// 防止重複載入
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

// === DB 連線設定 (lamian 資料庫 - 主要業務) ===
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'lamian');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// === 員工表 與 打卡表 ===
if (!defined('EMP_TABLE')) define('EMP_TABLE', '員工基本資料');
if (!defined('EMP_PK_COL')) define('EMP_PK_COL', 'id');
if (!defined('EMP_NAME_COL')) define('EMP_NAME_COL', 'name');
if (!defined('EMP_CODE_COL')) define('EMP_CODE_COL', 'id');
if (!defined('ATT_TABLE')) define('ATT_TABLE', 'attendance');

// === 密碼重設設定 ===
if (!defined('RESET_CODE_TTL_MIN')) define('RESET_CODE_TTL_MIN', 10);
if (!defined('RESET_MAX_ATTEMPTS')) define('RESET_MAX_ATTEMPTS', 5);

// === 郵件 (SMTP) 設定 ===
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', 'linghebouduo@gmail.com');
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', 'jrgp lxxq dcea vuxn');
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', 'tls');
if (!defined('MAIL_FROM_EMAIL')) define('MAIL_FROM_EMAIL', 'linghebouduo@gmail.com');
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', '拉麵店員工管理系統');

// === 共用 PDO 連線函數 (lamian 資料庫) ===
function pdo(){
  static $pdo = null;
  if ($pdo) return $pdo;
  $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
  try {
      $pdo = new PDO($dsn, DB_USER, DB_PASS, [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
      ]);
  } catch (PDOException $e) {
      error_log("PDO Connection Error (lamian): " . $e->getMessage());
      if (function_exists('err')) {
          err('資料庫連線失敗', 500);
      } else {
          die(json_encode(['error' => '資料庫連線失敗'], JSON_UNESCAPED_UNICODE));
      }
  }
  return $pdo;
}

// === 共用 JSON 回應函數 ===
if (!function_exists('ok')) {
    function ok($data){
      if (!headers_sent()) {
          header('Content-Type: application/json; charset=utf-8');
      }
      echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
      exit;
    }
}

if (!function_exists('err')) {
    function err($msg, $code = 400, $ext = []){
      if (!headers_sent()) {
          header('Content-Type: application/json; charset=utf-8');
          http_response_code($code);
      }
      echo json_encode(['error'=>$msg] + $ext, JSON_UNESCAPED_UNICODE);
      exit;
    }
}

// === 給 auth_core.php 和 password_login.php 使用的函數 ===
if (!function_exists('json_ok')) {
    function json_ok($data, $code = 200){
      if (!headers_sent()) {
          header('Content-Type: application/json; charset=utf-8');
          http_response_code($code);
      }
      echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
      exit;
    }
}

if (!function_exists('json_err')) {
    function json_err($msg, $code = 400, $extra = []){
      if (!headers_sent()) {
          header('Content-Type: application/json; charset=utf-8');
          http_response_code($code);
      }
      $response = array_merge(['error' => $msg], $extra);
      echo json_encode($response, JSON_UNESCAPED_UNICODE);
      exit;
    }
}

if (!function_exists('g')) {
    function g($k,$d=null){ 
      return isset($_GET[$k]) ? trim((string)$_GET[$k]) : $d; 
    }
}

// === 載入 PHPMailer ===
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * 使用 PHPMailer 發送郵件的通用函數
 * @param string $to 收件者 Email
 * @param string $toName 收件者名稱
 * @param string $subject 主旨 (需已編碼)
 * @param string $htmlBody HTML 內容
 * @param string $altBody 純文字內容
 * @return bool 是否成功
 */
if (!function_exists('send_email')) {
    function send_email(string $to, string $toName, string $subject, string $htmlBody, string $altBody = ''): bool {
        $mail = new PHPMailer(true);
        try {
            // 伺服器設定
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // ***** 除錯時可取消註解 *****
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = PHPMailer::CHARSET_UTF8;

            // 寄件者
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

            // 收件者
            $mail->addAddress($to, $toName);

            // 內容
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = ($altBody === '') ? strip_tags($htmlBody) : $altBody;

            $mail->send();
            error_log("✅ Email sent successfully to {$to}");
            return true;
            
        } catch (Exception $e) {
            error_log("❌ PHPMailer Error sending to {$to}: {$mail->ErrorInfo}");
            return false;
        }
    }
}
?>

