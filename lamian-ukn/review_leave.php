<?php
/**
 * API: 審核請假申請 (整合 Email 通知)
 * Method: POST
 * Input: JSON { "leaveId": 1, "action": "approve|reject", "rejectReason": "" }
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/review_error.log');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 引入 PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// 資料庫連線
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "lamian";

try {
    // 只接受 POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('請使用 POST 方法');
    }
    
    // 讀取 JSON 輸入
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON 格式錯誤');
    }
    
    // 取得參數
    $leaveId = intval($data['leaveId'] ?? 0);
    $action = trim($data['action'] ?? '');
    $rejectReason = trim($data['rejectReason'] ?? ''); // 保留這個變數
    
    // 驗證參數
    if ($leaveId <= 0) {
        throw new Exception('無效的申請編號');
    }
    
    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception('無效的操作: ' . $action);
    }
    
    // 連接資料庫
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('資料庫連線失敗');
    }
    
    $conn->set_charset("utf8mb4");
    
    // 查詢請假申請資料 (包含員工 email)
    $stmt = $conn->prepare("
        SELECT 
            ls.request_id,
            ls.name as employee_name,
            ls.start_date,
            ls.end_date,
            ls.total_days,
            ls.reason,
            lt.name as leave_type_name,
            ls.status,
            e.email as employee_email
        FROM leave_system ls
        LEFT JOIN 假別 lt ON ls.leave_type_id = lt.id
        LEFT JOIN 員工基本資料 e ON ls.name = e.name
        WHERE ls.request_id = ?
    ");
    
    $stmt->bind_param("i", $leaveId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        throw new Exception('找不到該請假申請');
    }
    
    $leaveData = $result->fetch_assoc();
    $stmt->close();
    
    // 檢查是否已審核
    if ($leaveData['status'] != 1) {
        $conn->close();
        throw new Exception('該申請已經審核過了');
    }
    
    // 更新審核狀態 (2=通過, 3=駁回)
    $newStatus = ($action === 'approve') ? 2 : 3;
    
    $stmt = $conn->prepare("
        UPDATE leave_system 
        SET status = ?
        WHERE request_id = ?
    ");
    
    $stmt->bind_param("ii", $newStatus, $leaveId);
    
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        throw new Exception('審核失敗');
    }
    
    $stmt->close();
    $conn->close();
    
    // ========== 發送 Email 通知 ==========
    $emailSent = false;
    $emailMessage = '';
    
    try {
        // 檢查員工 Email
        $employeeEmail = trim($leaveData['employee_email'] ?? '');
        
        if (empty($employeeEmail)) {
            $emailMessage = '員工 Email 為空,無法發送通知';
            error_log("警告: 員工「{$leaveData['employee_name']}」沒有 Email");
        } else {
            $mail = new PHPMailer(true);
            
            // SMTP 設定
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'linghebouduo@gmail.com';
            $mail->Password = 'jrgp lxxq dcea vuxn';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            // 寄件者
            $mail->setFrom('linghebouduo@gmail.com', '請假審核系統');
            
            // 收件者
            $mail->addAddress($employeeEmail, $leaveData['employee_name']);
            
            // 審核結果設定
            $isApproved = ($action === 'approve');
            $statusText = $isApproved ? '已核准' : '已駁回';
            $statusColor = $isApproved ? '#28a745' : '#dc3545';
            $headerGradient = $isApproved 
                ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
                : 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
            
            $mail->Subject = "[請假審核通知] 您的{$leaveData['leave_type_name']}申請{$statusText}";
            
            // HTML 內容
            $mail->isHTML(true);
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='utf-8'>
                <style>
                    body { 
                        font-family: 'Microsoft JhengHei', Arial, sans-serif; 
                        line-height: 1.8; 
                        color: #333; 
                        margin: 0;
                        padding: 0;
                        background: #f5f5f5;
                    }
                    .container { 
                        max-width: 600px; 
                        margin: 20px auto; 
                        background: #ffffff;
                        border-radius: 10px;
                        overflow: hidden;
                        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
                    }
                    .header { 
                        background: {$headerGradient}; 
                        color: white; 
                        padding: 30px 20px; 
                        text-align: center; 
                    }
                    .header h2 { 
                        margin: 0; 
                        font-size: 24px; 
                    }
                    .status-badge {
                        display: inline-block;
                        margin-top: 10px;
                        padding: 8px 20px;
                        background: rgba(255,255,255,0.2);
                        border-radius: 20px;
                        font-size: 14px;
                    }
                    .content { 
                        padding: 30px 20px; 
                    }
                    .info-row { 
                        margin: 15px 0; 
                        padding: 15px; 
                        background: #f8f9fa; 
                        border-left: 4px solid {$statusColor}; 
                        border-radius: 5px; 
                    }
                    .label { 
                        font-weight: bold; 
                        color: {$statusColor}; 
                        display: inline-block; 
                        min-width: 100px; 
                    }
                    .reject-reason {
                        margin-top: 20px;
                        padding: 15px;
                        background: #fff3cd;
                        border: 1px solid #ffc107;
                        border-radius: 5px;
                        color: #856404;
                    }
                    .footer { 
                        text-align: center; 
                        padding: 20px; 
                        color: #999; 
                        font-size: 12px; 
                        background: #f8f9fa;
                        border-top: 1px solid #e0e0e0;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>請假審核通知</h2>
                        <div class='status-badge'>{$statusText}</div>
                    </div>
                    
                    <div class='content'>
                        <p>{$leaveData['employee_name']} 您好:</p>
                        
                        <p>您的請假申請已經審核完成。</p>
                        
                        <div class='info-row'>
                            <span class='label'>申請編號</span>
                            <span>#{$leaveData['request_id']}</span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>假別</span>
                            <span>{$leaveData['leave_type_name']}</span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>請假期間</span>
                            <span>{$leaveData['start_date']} 至 {$leaveData['end_date']}</span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>請假天數</span>
                            <span>{$leaveData['total_days']} 天</span>
                        </div>
                        
                        <div class='info-row'>
                            <span class='label'>審核結果</span>
                            <span style='color: {$statusColor}; font-weight: bold;'>{$statusText}</span>
                        </div>";
            
            if (!$isApproved && !empty($rejectReason)) {
                $mail->Body .= "
                        <div class='reject-reason'>
                            <strong>駁回原因:</strong><br>
                            " . htmlspecialchars($rejectReason) . "
                        </div>";
            }
            
            $mail->Body .= "
                        <p style='margin-top: 30px; color: #666;'>
                            如有任何疑問,請聯繫人事部門。
                        </p>
                    </div>
                    
                    <div class='footer'>
                        此為系統自動發送的郵件,請勿直接回覆<br>
                        " . date('Y-m-d H:i:s') . "
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // 純文字版本
            $mail->AltBody = "請假審核通知\n\n{$leaveData['employee_name']} 您好:\n\n";
            $mail->AltBody .= "您的請假申請已經審核完成。\n\n";
            $mail->AltBody .= "申請編號: #{$leaveData['request_id']}\n";
            $mail->AltBody .= "假別: {$leaveData['leave_type_name']}\n";
            $mail->AltBody .= "請假期間: {$leaveData['start_date']} 至 {$leaveData['end_date']}\n";
            $mail->AltBody .= "審核結果: {$statusText}\n";
            
            if (!$isApproved && !empty($rejectReason)) {
                $mail->AltBody .= "\n駁回原因: {$rejectReason}\n";
            }
            
            // 發送
            $mail->send();
            $emailSent = true;
            $emailMessage = "Email 已成功發送至 {$employeeEmail}";
            error_log("成功發送 Email 給: {$employeeEmail}");
        }
        
    } catch (Exception $e) {
        $emailMessage = "Email 發送失敗: " . $e->getMessage();
        error_log("Email 發送錯誤: " . $e->getMessage());
    }
    
    // 回傳結果
    $actionText = ($action === 'approve') ? '核准' : '駁回';
    
    echo json_encode([
        'success' => true,
        'message' => "已{$actionText}「{$leaveData['employee_name']}」的請假申請",
        'emailSent' => $emailSent,
        'emailMessage' => $emailMessage
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>