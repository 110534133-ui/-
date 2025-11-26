<?php
declare(strict_types=1);
require_once __DIR__ . '/db_auth.php'; // 連接 password DB
require_once __DIR__ . '/config.php';  // 引入 PDO(lamian) 和常數

header('Content-Type: application/json; charset=utf-8');

// 統一錯誤處理
set_exception_handler(function(Throwable $e){
  error_log("Password Reset Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
  err('伺服器處理時發生錯誤', 500);
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  err('請求方法錯誤', 405);
}

$in = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
$email = trim($in['email'] ?? ''); // 仍需 email 查詢 token
$token = trim($in['reset_token'] ?? '');
$new_password = (string)($in['new_password'] ?? '');

// 基本參數驗證
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $token === '' || strlen($new_password) < 6) {
  err('參數錯誤（Email/Token 不可為空，密碼至少 6 碼）');
}

try {
    $pdo_auth = pdo_auth(); // 連接 password 資料庫

    // 1. 驗證 token 是否有效 (必須是最新且未使用，同時取得 user_id)
    $stmt = $pdo_auth->prepare(
        "SELECT `id`, `user_id`, `expires_at`
         FROM `password`
         WHERE `email` = ? AND `token` = ? AND `used` = 0
         ORDER BY `id` DESC
         LIMIT 1"
    );
    $stmt->execute([$email, $token]);
    $row = $stmt->fetch();

    // [!! 關鍵 !!]
    // 由於我們修正了 password_request.php，這裡的 $row['user_id'] 必定有值
    // 如果 $row 為 false，代表 token 錯誤，或已被使用 (used=1)
    if (!$row || empty($row['user_id'])) {
        err('重設連結無效或已使用');
    }

    $user_id_to_reset = $row['user_id'];
    $request_id = $row['id'];

    // 2. 檢查是否過期
    try {
        if ((new DateTime($row['expires_at'])) < (new DateTime())) {
             // [!! 關鍵修正 !!] 
             // 已刪除錯誤的 UPDATE `password` SET `used` = 1 ... 這一行
             // 只需要回傳錯誤，不需要更新資料庫
            err('重設連結已過期，請重新申請');
        }
    } catch (Exception $dateError) {
        error_log("Date comparison error in password_reset: " . $dateError->getMessage());
        err('連結狀態錯誤，請聯繫管理員');
    }

    // 3. 雜湊新密碼
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    if ($hash === false) {
        throw new Exception("Password hashing failed."); // 內部錯誤
    }

    // 4. 更新員工資料表的密碼 (使用 user_id)
    $pdo_lamian = pdo(); // 連接 lamian 資料庫
    $update_stmt = $pdo_lamian->prepare(
        "UPDATE `" . DB_NAME . "`.`" . EMP_TABLE . "`
         SET `password_hash` = ?
         WHERE `" . EMP_PK_COL . "` = ?" // 使用主鍵 (id) 更新
    );
    $update_stmt->execute([$hash, $user_id_to_reset]);

    // 檢查是否有更新成功
    if ($update_stmt->rowCount() === 0) {
         error_log("Password reset failed for user ID {$user_id_to_reset}: ID not found or password unchanged.");
    }

    // 5. [!! 關鍵 !!] 只有在成功更新密碼後，才將 token 標記為已使用
    $pdo_auth->prepare("UPDATE `password` SET `used` = 1 WHERE `id` = ?")
             ->execute([$request_id]);

    ok(['ok' => true, 'message' => '密碼已成功重設，請使用新密碼登入']);

} catch (PDOException $e) {
    error_log("DB error during password reset for {$email}: " . $e->getMessage());
    err('重設密碼時發生資料庫錯誤', 500);
} catch (Exception $e) {
    error_log("General error during password reset for {$email}: " . $e->getMessage());
    err('重設密碼時發生未預期錯誤', 500);
}
?>