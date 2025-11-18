<?php
// 開啟所有錯誤顯示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "config.php";

// 記錄日誌函數
function logDebug($message) {
    $log = date('[Y-m-d H:i:s] ') . $message . "\n";
    file_put_contents('email_debug.log', $log, FILE_APPEND);
    return $log;
}

logDebug("========== 開始處理Email發送請求 ==========");

header('Content-Type: application/json; charset=utf-8');

$email = trim($_POST['reset-email'] ?? '');
logDebug("收到Email: " . $email);

// 基本驗證
if (empty($email)) {
    logDebug("錯誤: Email為空");
    echo json_encode(['success' => false, 'message' => '請輸入Email']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    logDebug("錯誤: Email格式不正確");
    echo json_encode(['success' => false, 'message' => 'Email格式不正確']);
    exit;
}

// 檢查會員存在
$check_stmt = $conn->prepare("SELECT id, 姓名 FROM ramen_members WHERE Email = ?");
if (!$check_stmt) {
    logDebug("SQL錯誤: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'SQL錯誤']);
    exit;
}

$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    logDebug("錯誤: Email未註冊");
    echo json_encode(['success' => false, 'message' => '此Email尚未註冊會員']);
    $check_stmt->close();
    exit;
}

$member = $result->fetch_assoc();
logDebug("找到會員: ID={$member['id']}, 姓名={$member['姓名']}");
$check_stmt->close();

// 產生驗證碼
$verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expiry_time = date('Y-m-d H:i:s', time() + 300);
logDebug("產生驗證碼: {$verification_code}, 過期時間: {$expiry_time}");

// 寫入資料庫
$update_stmt = $conn->prepare("UPDATE ramen_members SET 驗證碼=?, 驗證碼建立時間=? WHERE Email=?");
if (!$update_stmt) {
    logDebug("UPDATE錯誤: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'UPDATE錯誤']);
    exit;
}

$update_stmt->bind_param("sss", $verification_code, $expiry_time, $email);

if (!$update_stmt->execute()) {
    logDebug("UPDATE執行失敗: " . $update_stmt->error);
    echo json_encode(['success' => false, 'message' => '驗證碼產生失敗']);
    $update_stmt->close();
    exit;
}

logDebug("資料庫更新成功");
$update_stmt->close();

// ============ 檢查 PHPMailer 是否已安裝 ============
logDebug("檢查 PHPMailer...");

$phpmailer_installed = false;
$phpmailer_path = '';

// 檢查 Composer 安裝
if (file_exists('vendor/autoload.php')) {
    logDebug("找到 Composer 的 PHPMailer");
    $phpmailer_installed = true;
    $phpmailer_path = 'composer';
    require 'vendor/autoload.php';
}
// 檢查手動安裝
else if (file_exists('PHPMailer/src/PHPMailer.php')) {
    logDebug("找到手動安裝的 PHPMailer");
    $phpmailer_installed = true;
    $phpmailer_path = 'manual';
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
}

if (!$phpmailer_installed) {
    logDebug("錯誤: PHPMailer 未安裝");
    echo json_encode([
        'success' => false,
        'message' => 'PHPMailer 未安裝，請先安裝 PHPMailer',
        'test_code' => $verification_code,
        'install_guide' => '執行: composer require phpmailer/phpmailer'
    ]);
    exit;
}

logDebug("PHPMailer 已安裝 (來源: {$phpmailer_path})");

// ============ 發送Email ============
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    logDebug("開始建立 PHPMailer 物件");
    $mail = new PHPMailer(true);
    
    // 啟用除錯模式
    $mail->SMTPDebug = 2;  // 顯示詳細除錯訊息
    $mail->Debugoutput = function($str, $level) {
        logDebug("SMTP: " . $str);
    };
    
    logDebug("設定 SMTP 參數");
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    
    // ============================================
    // ⚠️ 請在這裡填入你的 Gmail 資料
    // ============================================
    $mail->Username   = 'linghebouduo@gmail.com';  // 👈 填入你的 Gmail (例如: yourname@gmail.com)
    $mail->Password   = 'jrgplxxqdceavuxn';  // 👈 填入應用程式密碼 (16碼)
    // ============================================
    
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    
    logDebug("SMTP設定: Host={$mail->Host}, Port={$mail->Port}, Username={$mail->Username}");
    
    // 檢查帳號密碼是否已設定
    if (empty($mail->Username) || empty($mail->Password)) {
        logDebug("錯誤: Gmail帳號密碼未設定");
        throw new Exception('請先在 send_email.php 中設定你的 Gmail 帳號和應用程式密碼');
    }
    
    logDebug("設定寄件人和收件人");
    $mail->setFrom($mail->Username, '拉麵會員系統');
    $mail->addAddress($email, $member['姓名']);
    
    logDebug("設定郵件內容");
    $mail->isHTML(true);
    $mail->Subject = '拉麵會員系統 - 密碼重設驗證碼';
    $mail->Body = "
    <!DOCTYPE html>
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; padding: 20px;'>
        <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;'>
                <h1>🍜 拉麵會員系統</h1>
                <p>密碼重設驗證</p>
            </div>
            <div style='padding: 40px 30px;'>
                <p>親愛的 <strong>{$member['姓名']}</strong>，您好：</p>
                <p>您申請了密碼重設，以下是您的驗證碼：</p>
                <div style='background: #f8f9fa; border: 2px dashed #667eea; border-radius: 10px; padding: 30px; text-align: center; margin: 30px 0;'>
                    <div style='font-size: 42px; font-weight: bold; color: #667eea; letter-spacing: 8px;'>{$verification_code}</div>
                    <p style='color: #666; margin-top: 15px;'>請在5分鐘內使用此驗證碼</p>
                </div>
                <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                    <strong>⚠️ 安全提醒：</strong><br>
                    • 此驗證碼將在 <strong>5分鐘</strong> 後失效<br>
                    • 如果這不是您的操作，請忽略此郵件
                </div>
            </div>
            <div style='background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px;'>
                <p>此郵件由系統自動發送，請勿直接回覆</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $mail->AltBody = "拉麵會員系統 - 密碼重設驗證碼\n\n親愛的 {$member['姓名']}，您好：\n\n您的驗證碼是: {$verification_code}\n\n此驗證碼將在5分鐘後過期。";
    
    logDebug("開始發送郵件...");
    $mail->send();
    logDebug("郵件發送成功！");
    
    echo json_encode([
        'success' => true,
        'message' => '驗證碼已發送至您的Email，請查收！',
        'debug' => [
            'email_sent_to' => $email,
            'smtp_host' => $mail->Host,
            'smtp_port' => $mail->Port,
            'time' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    logDebug("郵件發送失敗: " . $e->getMessage());
    logDebug("PHPMailer錯誤: " . $mail->ErrorInfo);
    
    echo json_encode([
        'success' => false,
        'message' => 'Email發送失敗',
        'error' => $e->getMessage(),
        'smtp_error' => $mail->ErrorInfo,
        'test_code' => $verification_code,
        'debug_tip' => '請查看 email_debug.log 檔案了解詳細錯誤'
    ]);
}

$conn->close();
logDebug("========== 處理完成 ==========\n");
?>