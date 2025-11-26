<?php
/**
 * 請假申請系統 - 整合版
 * 功能:新增請假 + Email 通知
 */

// 啟動 Session (如果還沒啟動)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: text/html; charset=utf-8');

// ========== 資料庫設定 ==========
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "lamian";

// ========== Email 相關函數 ==========
// 引入 PHPMailer
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * 發送請假通知 Email
 */
function sendLeaveNotification($leaveData) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP 設定
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'linghebouduo@gmail.com';
        $mail->Password   = 'jrgp lxxq dcea vuxn';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // 寄件人與收件人
        $mail->setFrom('linghebouduo@gmail.com', '員工管理系統');
        $mail->addAddress('x140958@gmail.com', '人事管理員');
        
        // 郵件內容
        $mail->isHTML(true);
        $mail->Subject = '【新請假申請】' . $leaveData['employeeName'] . ' - ' . $leaveData['leaveType'];
        $mail->Body = generateEmailHTML($leaveData);
        $mail->AltBody = generateEmailText($leaveData);
        
        // 發送
        $mail->send();
        error_log('Email 發送成功: ' . $leaveData['employeeName']);
        return true;
        
    } catch (Exception $e) {
        error_log('Email 發送失敗: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * 生成證明文件 URL
 */
function getProofFileUrl($proofFile) {
    if (empty($proofFile)) {
        return '';
    }
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;
    
    return $baseUrl . '/lamian-ukn/uploads/leave/' . basename($proofFile);
}

/**
 * 生成審核頁面 URL
 */
function getReviewUrl($leaveId) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;
    
    return $baseUrl . '/lamian-ukn/%E5%81%87%E5%88%A5%E7%AE%A1%E7%90%86.html?id=' . urlencode($leaveId);
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
            <td style="padding:8px;">
                <a href="' . htmlspecialchars($proofUrl) . '" 
                   target="_blank" 
                   style="color:#667eea;text-decoration:none;font-weight:500;">
                    🔎 查看檔案
                </a>
            </td>
        </tr>';
    }
    
    $reviewUrl = getReviewUrl($data['leaveId']);
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: "Microsoft JhengHei", Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                      color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: white; padding: 30px; border: 1px solid #e0e0e0; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            td { padding: 12px; border-bottom: 1px solid #f0f0f0; }
            .label { font-weight: 600; background: #f8f9fa; width: 30%; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; 
                      font-size: 12px; color: #666; border-radius: 0 0 10px 10px; }
            .btn { display: inline-block; padding: 12px 30px; background: #667eea; 
                   color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2 style="margin:0;">📋 新的請假申請</h2>
                <p style="margin:10px 0 0 0;opacity:0.9;">員工管理系統通知</p>
            </div>
            
            <div class="content">
                <p>您好,</p>
                <p>有一筆新的請假申請需要您審核:</p>
                
                <table>
                    <tr>
                        <td class="label">申請編號:</td>
                        <td>#' . htmlspecialchars($data['leaveId']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">員工姓名:</td>
                        <td>' . htmlspecialchars($data['employeeName']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">假別:</td>
                        <td><strong>' . htmlspecialchars($data['leaveType']) . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">開始日期:</td>
                        <td>' . htmlspecialchars($data['startDate']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">結束日期:</td>
                        <td>' . htmlspecialchars($data['endDate']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">請假天數:</td>
                        <td>' . htmlspecialchars($data['totalDays']) . ' 天</td>
                    </tr>
                    <tr>
                        <td class="label">請假原因:</td>
                        <td>' . nl2br(htmlspecialchars($data['reason'])) . '</td>
                    </tr>
                    ' . $proofLink . '
                    <tr>
                        <td class="label">申請時間:</td>
                        <td>' . date('Y-m-d H:i:s') . '</td>
                    </tr>
                </table>
                
                <div style="text-align:center;">
                    <a href="' . htmlspecialchars($reviewUrl) . '" class="btn">
                        立即審核 →
                    </a>
                </div>
                
                <p style="margin-top:20px;padding:15px;background:#f8f9fa;border-radius:5px;font-size:14px;">
                    💡 <strong>提示:</strong> 請點擊上方按鈕進入系統進行審核操作
                </p>
            </div>
            
            <div class="footer">
                <p>此為系統自動發送的通知郵件,請勿直接回覆</p>
                <p>© 2025 員工管理系統 - Xxing0625</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

/**
 * 生成純文字 Email
 */
function generateEmailText($data) {
    $reviewUrl = getReviewUrl($data['leaveId']);
    
    $text = "【新的請假申請】\n\n";
    $text .= "申請編號: #" . $data['leaveId'] . "\n";
    $text .= "員工姓名: " . $data['employeeName'] . "\n";
    $text .= "假別: " . $data['leaveType'] . "\n";
    $text .= "開始日期: " . $data['startDate'] . "\n";
    $text .= "結束日期: " . $data['endDate'] . "\n";
    $text .= "請假天數: " . $data['totalDays'] . " 天\n";
    $text .= "請假原因: " . $data['reason'] . "\n";
    
    if (!empty($data['proofFile'])) {
        $proofUrl = getProofFileUrl($data['proofFile']);
        $text .= "證明文件: " . $proofUrl . "\n";
    }
    
    $text .= "申請時間: " . date('Y-m-d H:i:s') . "\n\n";
    $text .= "請至系統審核: " . $reviewUrl . "\n";
    
    return $text;
}

// ========== 主要處理邏輯 ==========
try {
    // 連線資料庫
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("連線失敗: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // 檢查請求方法
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("請使用 POST 方法送出資料");
    }
    
    // ========== 🔥 取得登入員工資訊 (修正版) ==========
    $employeeName = null;
    $employeeId = null;
    
    // 方法1: 從 Session 取得 (與 indexC.php 一致,優先使用 'name')
    if (isset($_SESSION['name']) && !empty($_SESSION['name'])) {
        $employeeName = $_SESSION['name'];
        $employeeId = $_SESSION['uid'] ?? null;
    }
    // 方法2: 備用 - 使用 'employee_name'
    elseif (isset($_SESSION['employee_name']) && !empty($_SESSION['employee_name'])) {
        $employeeName = $_SESSION['employee_name'];
        $employeeId = $_SESSION['employee_id'] ?? null;
    }
    // 方法3: 如果有 uid,從資料庫查詢
    elseif (isset($_SESSION['uid']) && !empty($_SESSION['uid'])) {
        $stmt = $conn->prepare("SELECT id, name FROM 員工基本資料 WHERE id = ?");
        $stmt->bind_param("s", $_SESSION['uid']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $employeeName = $row['name'];
            $employeeId = $row['id'];
        }
        $stmt->close();
    }
    
    // 如果還是沒有,回傳錯誤
    if (empty($employeeName)) {
        throw new Exception("無法取得員工資訊,請重新登入");
    }
    
    // 取得表單資料
    $leaveTypeName = trim($_POST["leaveType"] ?? '');
    $startDate = trim($_POST["startDate"] ?? '');
    $endDate = trim($_POST["endDate"] ?? '');
    $reason = trim($_POST["reason"] ?? '');
    
    // 驗證必填欄位
    if (empty($leaveTypeName) || empty($startDate) || empty($endDate)) {
        throw new Exception("請填寫完整資料(假別、開始日期、結束日期)");
    }
    
    // 查詢假別 ID
    $stmt = $conn->prepare("SELECT id FROM 假別 WHERE name = ?");
    if (!$stmt) {
        throw new Exception("查詢假別失敗: " . $conn->error);
    }
    
    $stmt->bind_param("s", $leaveTypeName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        throw new Exception("找不到該假別:" . $leaveTypeName);
    }
    
    $leaveTypeId = $result->fetch_assoc()["id"];
    $stmt->close();
    
    // 處理檔案上傳
    $proofFileName = "";
    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
        $uploadDir = "uploads/leave/";
        
        // 確保目錄存在
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // 檢查檔案類型
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/heic'];
        $fileType = $_FILES["photo"]["type"];
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("只支持 JPG、PNG、HEIC 格式");
        }
        
        // 檢查檔案大小
        if ($_FILES["photo"]["size"] > 5 * 1024 * 1024) {
            throw new Exception("檔案大小不可超過 5MB");
        }
        
        // 生成唯一檔名
        $extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
        $proofFileName = uniqid() . "_" . time() . "." . $extension;
        
        $targetPath = $uploadDir . $proofFileName;
        if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $targetPath)) {
            throw new Exception("檔案上傳失敗");
        }
    }
    
    // 計算請假天數
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = $start->diff($end);
    $totalDays = $interval->days + 1;
    
    // 插入請假資料 (使用真實員工姓名)
    $stmt = $conn->prepare("
        INSERT INTO leave_system 
        (name, leave_type_id, start_date, end_date, total_days, reason, proof, status)
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    if (!$stmt) {
        throw new Exception("準備插入語句失敗: " . $conn->error);
    }
    
    $stmt->bind_param(
        "sisssss", 
        $employeeName,  // 🔥 使用真實員工姓名
        $leaveTypeId,
        $startDate, 
        $endDate, 
        $totalDays, 
        $reason, 
        $proofFileName
    );
    
    if (!$stmt->execute()) {
        throw new Exception("資料插入失敗: " . $stmt->error);
    }
    
    $insertId = $stmt->insert_id;
    $stmt->close();
    
    // 發送 Email 通知
    $emailData = [
        'leaveId' => $insertId,
        'employeeName' => $employeeName,  // 🔥 使用真實員工姓名
        'leaveType' => $leaveTypeName,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'totalDays' => $totalDays,
        'reason' => $reason ?: '(未填寫)',
        'proofFile' => $proofFileName
    ];
    
    $emailSent = sendLeaveNotification($emailData);
    
    $conn->close();
    
    // 回傳結果
    if ($emailSent) {
        echo "✅ 請假申請成功!申請編號:" . $insertId . " (已發送通知給管理員)";
    } else {
        echo "✅ 請假申請成功!申請編號:" . $insertId . " (Email 通知發送失敗,但申請已記錄)";
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo "❌ 錯誤:" . $e->getMessage();
    error_log(date('[Y-m-d H:i:s] ') . $e->getMessage() . "\n", 3, "error.log");
}
?>