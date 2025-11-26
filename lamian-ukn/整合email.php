<?php
/**
 * API: 審核請假申請 (已整合 Email 通知功能)
 * Method: POST
 * Input: JSON { "id": 1, "action": "approved" | "rejected" }
 * Response: Success message or error
 */

// 錯誤處理
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// 設定 header
header('Content-Type: application/json; charset=utf-8');

// 引入必要檔案
try {
    if (!file_exists(__DIR__ . '/db_config.php')) {
        throw new Exception('找不到 db_config.php 檔案');
    }
    require_once __DIR__ . '/db_config.php';
    
    if (!file_exists(__DIR__ . '/send_review_notification.php')) {
        throw new Exception('找不到 send_review_notification.php 檔案');
    }
    require_once __DIR__ . '/send_review_notification.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '系統檔案載入失敗: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 只接受 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '方法不允許'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 讀取 JSON 輸入
    $input = file_get_contents('php://input');
    error_log("Received input: " . $input);
    
    // 解析 JSON
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'JSON 格式錯誤: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    error_log("Parsed data: " . print_r($data, true));
    
    // 驗證必要欄位
    if (!isset($data['id']) || !isset($data['action'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => '缺少必要參數 (id 或 action)'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $id = intval($data['id']);
    $action = $data['action'];
    
    // 驗證 ID
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '無效的 ID'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 驗證 action 值
    if (!in_array($action, ['approved', 'rejected'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => '無效的操作: ' . $action
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 轉換 action 為資料庫的 status 值
    // approved = 2 (通過)
    // rejected = 3 (駁回)
    $status = ($action === 'approved') ? 2 : 3;
    
    // 取得資料庫連線
    $pdo = getDbConnection();
    
    // ========== 查詢請假申請的完整資料 (包含員工 Email) ==========
    $checkSql = "
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
    ";
    
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$id]);
    $record = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => '找不到該請假記錄 (ID: ' . $id . ')'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    error_log("Found record: " . print_r($record, true));
    
    // ========== 更新審核狀態 ==========
    $updateSql = "UPDATE leave_system 
                  SET status = ?
                  WHERE request_id = ?";
    
    $updateStmt = $pdo->prepare($updateSql);
    $result = $updateStmt->execute([$status, $id]);
    
    if (!$result) {
        throw new Exception('更新審核狀態失敗');
    }
    
    error_log("Updated status to: " . $status);
    
    // ========== 發送 Email 通知給員工 ==========
    $emailSent = false;
    try {
        // 從資料庫取得員工 Email
        $employeeEmail = $record['employee_email'];
        
        // 如果沒有找到 email，記錄警告但繼續流程
        if (empty($employeeEmail)) {
            error_log("警告: 員工「{$record['employee_name']}」的 email 為空");
            $emailSent = false;
        } else {
            $emailData = [
                'leaveId' => $record['request_id'],
                'employeeName' => $record['employee_name'],
                'employeeEmail' => $employeeEmail,
                'leaveType' => $record['leave_type_name'] ?? '未知假別',
                'startDate' => $record['start_date'],
                'endDate' => $record['end_date'],
                'totalDays' => $record['total_days']
            ];
            
            error_log("Sending email with data: " . print_r($emailData, true));
            
            // 發送通知
            $emailSent = sendReviewNotification($emailData, $action);
            
            error_log("Email sent to {$employeeEmail}: " . ($emailSent ? 'success' : 'failed'));
        }
    } catch (Exception $e) {
        error_log('Email 發送失敗: ' . $e->getMessage());
        // Email 失敗不影響審核流程
    }
    
    // ========== 回傳成功結果 ==========
    $actionText = ($action === 'approved') ? '通過' : '駁回';
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "已成功{$actionText}「{$record['employee_name']}」的請假申請",
        'emailSent' => $emailSent
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '資料庫錯誤: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error in 整合email.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '審核操作失敗: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>