<?php
// ===== api/review_leave.php (完整修正版) =====

// [!! 修正 !!] 載入標準設定檔和權限檢查
// auth_check.php 會自動啟動 session 和檢查登入
require_once __DIR__ . '/../includes/auth_check.php';
// config.php 會自動載入 PHPMailer (via vendor/autoload.php) 和 pdo()
require_once __DIR__ . '/config.php';

// [!! 修正 !!] 將 use 語句移到 require 之後
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// [!! 修正 !!] 移除所有手動的 PHPMailer require 行 (已由 config.php 處理)
// require __DIR__ . '/PHPMailer/src/Exception.php'; (錯誤的)
// require __DIR__ . '/PHPMailer/src/PHPMailer.php'; (錯誤的)
// require __DIR__ . '/PHPMailer/src/SMTP.php';      (錯誤的)

try {
    // [!! 新增 !!] 檢查權限 (A 或 B 級)，且 $redirect = false (API模式)
    if (!check_user_level(['A', 'B'], false)) {
        err('權限不足 (僅限 A/B 級)', 403);
    }
    
    // 讀取 JSON 輸入
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // [!! 修正 !!] 使用標準 err() 函數
        err('JSON 格式錯誤');
    }
    
    // ( ... 以下邏輯保持不變 ... )
    $leaveId = intval($data['leaveId'] ?? 0);
    $action = trim($data['action'] ?? '');
    $rejectReason = trim($data['rejectReason'] ?? '');
    
    if ($leaveId <= 0) {
        err('無效的申請編號');
    }
    if (!in_array($action, ['approve', 'reject'])) {
        err('無效的操作: ' . $action);
    }
    
    // [!! 修正 !!] 使用 pdo() 而不是 getDbConnection()
    $pdo = pdo();
    
    // 查詢請假申請資料
    $stmt = $pdo->prepare("
        SELECT 
            ls.request_id, ls.name as employee_name, ls.start_date, ls.end_date,
            ls.total_days, ls.reason, lt.name as leave_type_name, ls.status,
            e.email as employee_email
        FROM leave_system ls
        LEFT JOIN 假別 lt ON ls.leave_type_id = lt.id
        LEFT JOIN 員工基本資料 e ON ls.name = e.name
        WHERE ls.request_id = ?
    ");
    
    $stmt->execute([$leaveId]);
    $leaveData = $stmt->fetch();
    
    if (!$leaveData) {
        err('找不到該請假申請');
    }
    if ($leaveData['status'] != 1) { // 假設 1 是 '待審核'
        err('該申請已經審核過了');
    }
    
    // 更新審核狀態
    $newStatus = ($action === 'approve') ? 2 : 3; // 假設 2=核准, 3=駁回
    $stmt = $pdo->prepare("UPDATE leave_system SET status = ? WHERE request_id = ?");
    $stmt->execute([$newStatus, $leaveId]);
    
    // ========== 發送 Email 通知 ==========
    $emailSent = false;
    $emailMessage = '';
    
    try {
        $employeeEmail = trim($leaveData['employee_email'] ?? '');
        
        if (empty($employeeEmail)) {
            $emailMessage = '員工 Email 為空,無法發送通知';
            error_log("警告: 員工「{$leaveData['employee_name']}」沒有 Email");
        } else {
            $mail = new PHPMailer(true);
            
            // [!! 修正 !!] SMTP 設定 (從 config.php 讀取常數)
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($employeeEmail, $leaveData['employee_name']);
            
            $isApproved = ($action === 'approve');
            $statusText = $isApproved ? '已核准' : '已駁回';
            // ( ... 以下 Email 內容保持不變 ... )
            $statusColor = $isApproved ? '#28a745' : '#dc3545';
            
            $mail->Subject = "[請假審核通知] 您的{$leaveData['leave_type_name']}申請{$statusText}";
            
            $mail->isHTML(true);
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='utf-8'>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; }
                    .container { width: 90%; max-width: 600px; margin: 20px auto; border: 1px solid #ddd; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
                    .header { padding: 25px; text-align: center; color: white; background: {$statusColor}; }
                    .header h2 { margin: 0; font-size: 24px; }
                    .status-badge { background: rgba(255,255,255,0.2); border-radius: 20px; padding: 5px 15px; display: inline-block; margin-top: 10px; font-weight: bold; }
                    .content { padding: 30px; }
                    .content p { margin-bottom: 20px; color: #333; }
                    .info-row { padding: 10px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
                    .info-row:last-child { border-bottom: none; }
                    .label { font-weight: bold; color: #555; }
                    .reject-reason { margin-top: 20px; padding: 15px; background: #fff8f8; border: 1px solid #f5c6cb; border-radius: 8px; color: #721c24; }
                    .footer { padding: 20px; background: #f9f9f9; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; }
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
                        <div class='info-row'><span class='label'>申請編號</span><span>#{$leaveData['request_id']}</span></div>
                        <div class='info-row'><span class='label'>假別</span><span>{$leaveData['leave_type_name']}</span></div>
                        <div class='info-row'><span class='label'>請假期間</span><span>{$leaveData['start_date']} 至 {$leaveData['end_date']}</span></div>
                        <div class='info-row'><span class='label'>請假天數</span><span>{$leaveData['total_days']} 天</span></div>
                        <div class='info-row'><span class='label'>審核結果</span><span style='color: {$statusColor}; font-weight: bold;'>{$statusText}</span></div>";
            
            if (!$isApproved && !empty($rejectReason)) {
                $mail->Body .= "<div class='reject-reason'><strong>駁回原因:</strong><br>" . htmlspecialchars($rejectReason) . "</div>";
            }
            
            $mail->Body .= "<p style='margin-top: 30px; color: #666;'>如有任何疑問,請聯繫人事部門。</p></div><div class='footer'>此為系統自動發送的郵件,請勿直接回覆<br>" . date('Y-m-d H:i:s') . "</div></div></body></html>";
            
            $mail->send();
            $emailSent = true;
            $emailMessage = "Email 已成功發送至 {$employeeEmail}";
            error_log("成功發送 Email 給: {$employeeEmail}");
        }
        
    } catch (Exception $e) {
        $emailMessage = "Email 發送失敗: " . $e->getMessage();
        error_log("Email 發送錯誤: " . $e->getMessage());
    }
    
    $actionText = ($action === 'approve') ? '核准' : '駁回';
    
    // [!! 修正 !!] 使用標準 ok() 函數
    ok([
        'success' => true,
        'message' => "已{$actionText}「{$leaveData['employee_name']}」的請假申請",
        'emailSent' => $emailSent,
        'emailMessage' => $emailMessage
    ]);
    
} catch (Throwable $e) { // [!! 修正 !!] 捕捉所有錯誤
    error_log("api/review_leave.php Error: " . $e->getMessage());
    // [!! 修正 !!] 使用標準 err() 函數
    err('API 內部錯誤', 500, ['detail' => $e->getMessage()]);
}
?>