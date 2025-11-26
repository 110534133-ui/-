<?php
declare(strict_types=1);
require_once __DIR__ . '/db_auth.php'; // 連接 password DB
require_once __DIR__ . '/config.php';  // 引入常數

header('Content-Type: application/json; charset=utf-8');

// 統一錯誤處理
set_exception_handler(function(Throwable $e){
  error_log("Password Verify Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
  err('伺服器處理時發生錯誤', 500);
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  err('請求方法錯誤', 405);
}

$in    = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
$email = trim($in['email'] ?? '');
$code  = trim($in['code'] ?? '');

// 驗證輸入格式
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{6}$/', $code)) {
  err('Email 或驗證碼格式錯誤');
}

try {
    $pdo_auth = pdo_auth();
    // 修改: 取得最新一筆 *未使用* 的記錄
    $stmt = $pdo_auth->prepare(
        "SELECT `id`, `code`, `token`, `expires_at`, `attempts`
         FROM `password`
         WHERE `email` = ? AND `used` = 0
         ORDER BY `id` DESC
         LIMIT 1"
    );
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    // 修改: 更清晰的錯誤
    if (!$row) {
        err('驗證碼錯誤或已失效'); // 找不到未使用的 code
    }

    $request_id = (int)$row['id'];

    // 檢查嘗試次數
    if ((int)$row['attempts'] >= RESET_MAX_ATTEMPTS) {
        // (可選) 也可以考慮直接將這筆設為 used=1
        err('嘗試次數過多，請重新申請驗證碼');
    }

    // 檢查是否過期
    try {
        if ((new DateTime($row['expires_at'])) < (new DateTime())) {
             // [!! 關鍵修正 !!] 
             // 這裡 *不應該* 將 'used' 設為 1
             // (已刪除錯誤的 UPDATE 語句)
            err('驗證碼已過期，請重新申請');
        }
    } catch (Exception $dateError) {
         error_log("Date comparison error in password_verify: " . $dateError->getMessage());
         err('驗證碼狀態錯誤，請聯繫管理員');
    }

    // *** 比對驗證碼 ***
    // 假設 password 表儲存的是明文 code
    if ($row['code'] !== $code) {
        // 增加嘗試次數
        $pdo_auth->prepare("UPDATE `password` SET `attempts` = `attempts` + 1 WHERE `id` = ?")
                 ->execute([$request_id]);
        $remaining_attempts = RESET_MAX_ATTEMPTS - ((int)$row['attempts'] + 1);
        // 修改: 更清晰的錯誤提示
        err('驗證碼錯誤' . ($remaining_attempts > 0 ? "，還可嘗試 {$remaining_attempts} 次" : "，請重新申請"));
    }

    // [!! 關鍵 !!]
    // 驗證成功，回傳 token
    // *不* 在這裡標記 used=1 (這是步驟 3 的工作)
    ok(['ok' => true, 'reset_token' => $row['token']]);

} catch (PDOException $e) {
    error_log("DB error during password verify for {$email}: " . $e->getMessage());
    err('驗證時發生資料庫錯誤', 500); // 改成 500 內部錯誤
}
?>