<?php
/**
 * 請假申請系統 - 整合版
 * 🔥 修正：使用 config.php 和 auth_check.php
 */

// 引入 PHPMailer (透過 config.php 裡的 vendor/autoload.php)
// 引入標準設定檔和權限檢查
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/config.php'; // 包含 pdo(), ok(), err(), send_email()

// 關閉 PHP 錯誤顯示，統一由 try-catch 處理
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ========== Email 相關函數 (使用 config.php 的版本) ==========

/**
 * 生成證明文件 URL
 */
function getProofFileUrl($proofFile) {
    if (empty($proofFile)) return '';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;
    // 🔥 修正：確保路徑正確
    return $baseUrl . '/lamian-ukn/uploads/leave/' . basename($proofFile);
}

/**
 * 生成審核頁面 URL
 * 🔥 修正：您的審核頁面應該是 .php 檔案
 */
function getReviewUrl($leaveId) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;
    return $baseUrl . '/lamian-ukn/假別管理.php?id=' . urlencode($leaveId);
}

/**
 * 生成 HTML Email 內容
 */
function generateEmailHTML($data) {
    $proofLink = '';
    if (!empty($data['proofFile'])) {
        $proofUrl = getProofFileUrl($data['proofFile']);
        $proofLink = '<tr>
            <td style="padding:8px;background:#f8f9fa;font-weight:600;">證明文件:</td>
            <td style="padding:8px;"><a href="' . htmlspecialchars($proofUrl) . '" target="_blank">🔎 查看檔案</a></td>
        </tr>';
    }
    $reviewUrl = getReviewUrl($data['leaveId']);
    
    // (HTML 模板內容與您原本的相同，保持不變)
    return '
    <!DOCTYPE html>
    <html>... (同您原檔，內容省略) ...
            <div class="content">
                ...
                <table>
                    <tr><td class="label">申請編號:</td><td>#' . htmlspecialchars($data['leaveId']) . '</td></tr>
                    <tr><td class="label">員工姓名:</td><td>' . htmlspecialchars($data['employeeName']) . '</td></tr>
                    <tr><td class="label">假別:</td><td><strong>' . htmlspecialchars($data['leaveType']) . '</strong></td></tr>
                    <tr><td class="label">開始日期:</td><td>' . htmlspecialchars($data['startDate']) . '</td></tr>
                    <tr><td class="label">結束日期:</td><td>' . htmlspecialchars($data['endDate']) . '</td></tr>
                    <tr><td class="label">請假天數:</td><td>' . htmlspecialchars($data['totalDays']) . ' 天</td></tr>
                    <tr><td class="label">請假原因:</td><td>' . nl2br(htmlspecialchars($data['reason'])) . '</td></tr>
                    ' . $proofLink . '
                    <tr><td class="label">申請時間:</td><td>' . date('Y-m-d H:i:s') . '</td></tr>
                </table>
                <div style="text-align:center;"><a href="' . htmlspecialchars($reviewUrl) . '" class="btn">立即審核 →</a></div>
            </div>
    ...</html>
    ';
}

/**
 * 生成純文字 Email
 */
function generateEmailText($data) {
    // (純文字模板內容與您原本的相同，保持不變)
    $reviewUrl = getReviewUrl($data['leaveId']);
    $text = "【新的請假申請】\n\n";
    $text .= "申請編號: #" . $data['leaveId'] . "\n";
    $text .= "員工姓名: " . $data['employeeName'] . "\n";
    $text .= "假別: " . $data['leaveType'] . "\n";
    // ... (etc.)
    return $text;
}

// ========== 主要處理邏輯 ==========
try {
    // 🔥 修正：使用標準 pdo() 連線
    $pdo = pdo();

    // 🔥 修正：檢查權限 (A, B, C 級都可申請)
    if (!check_user_level(['A', 'B', 'C'], false)) {
        err('您尚未登入，無法提交請假單', 401);
    }

    // 檢查請求方法
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        err("請使用 POST 方法送出資料", 405);
    }
    
    // ========== 🔥 修正：取得登入員工資訊 ==========
    $user = get_user_info();
    $employeeName = $user['name'];
    $employeeId = $user['uid']; // 雖然沒用到，但這是正確的 ID
    
    if (empty($employeeName) || $employeeName === '訪客') {
        err("無法識別您的身分，請重新登入", 401);
    }
    
    // 取得表單資料
    $leaveTypeName = trim($_POST["leaveType"] ?? '');
    $startDate = trim($_POST["startDate"] ?? '');
    $endDate = trim($_POST["endDate"] ?? '');
    $reason = trim($_POST["reason"] ?? '');
    
    // 驗證必填欄位
    if (empty($leaveTypeName) || empty($startDate) || empty($endDate)) {
        err("請填寫完整資料(假別、開始日期、結束日期)", 422);
    }
    
    // 查詢假別 ID
    $stmt = $pdo->prepare("SELECT id FROM 假別 WHERE name = ?");
    $stmt->execute([$leaveTypeName]);
    $leaveTypeResult = $stmt->fetch();
    
    if (!$leaveTypeResult) {
        err("找不到該假別:" . $leaveTypeName, 404);
    }
    $leaveTypeId = $leaveTypeResult["id"];
    
    // 處理檔案上傳
    $proofFileName = "";
    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/../uploads/leave/"; // 🔥 修正：使用絕對路徑
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                err("無法建立上傳目錄", 500);
            }
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/heic'];
        if (!in_array($_FILES["photo"]["type"], $allowedTypes)) {
            err("只支持 JPG、PNG、HEIC 格式", 400);
        }
        if ($_FILES["photo"]["size"] > 5 * 1024 * 1024) {
            err("檔案大小不可超過 5MB", 400);
        }
        
        $extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
        $proofFileName = 'leave_' . $employeeId . '_' . time() . "." . $extension; // 🔥 修正：檔名加入員工 ID
        
        $targetPath = $uploadDir . $proofFileName;
        if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $targetPath)) {
            err("檔案上傳失敗", 500);
        }
    }
    
    // 計算請假天數
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = $start->diff($end);
    $totalDays = $interval->days + 1;
    
    // 插入請假資料
    $stmt = $pdo->prepare("
        INSERT INTO leave_system 
        (name, leave_type_id, start_date, end_date, total_days, reason, proof, status)
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->execute([
        $employeeName,
        $leaveTypeId,
        $startDate, 
        $endDate, 
        $totalDays, 
        $reason, 
        $proofFileName
    ]);
    
    $insertId = $pdo->lastInsertId();
    
    // 發送 Email 通知
    $emailData = [
        'leaveId' => $insertId,
        'employeeName' => $employeeName,
        'leaveType' => $leaveTypeName,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'totalDays' => $totalDays,
        'reason' => $reason ?: '(未填寫)',
        'proofFile' => $proofFileName
    ];
    
    // 🔥 修正：使用 config.php 中的 send_email() 函數
    // 假設收件人是固定的，如果不是，您需要從資料庫查詢 A/B 級管理員的 Email
    $adminEmail = 'x140958@gmail.com';
    $adminName = '人事管理員';
    $subject = '【新請假申請】' . $employeeName . ' - ' . $leaveTypeName;
    $htmlBody = generateEmailHTML($emailData);
    $textBody = generateEmailText($emailData);
    
    $emailSent = send_email($adminEmail, $adminName, $subject, $htmlBody, $textBody);
    
    // 回傳結果 (🔥 修正：API 不應該 echo HTML，而是回傳純文字或 JSON)
    // 您的 JS 正在監聽 res.text()，所以我們回傳純文字
    if ($emailSent) {
        echo "✅ 請假申請成功! 申請編號:" . $insertId . " (已發送通知給管理員)";
    } else {
        echo "✅ 請假申請成功! 申請編號:" . $insertId . " (Email 通知發送失敗, 但申請已記錄)";
    }
    
} catch (Exception $e) {
    // 🔥 修正：回傳錯誤訊息
    http_response_code(400); // 可能是 400, 401, 403, 500
    echo "❌ 錯誤:" . $e->getMessage();
    error_log(date('[Y-m-d H:i:s] ') . $e->getMessage() . "\n", 3, "error.log");
}
