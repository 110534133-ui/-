<?php 
// /lamian-ukn/api/password_login.php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/config.php';
    
    // 啟動 session
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => false,
            'path' => '/'
        ]);
        session_start();
    }
    
    // 讀取輸入
    $raw_input = file_get_contents('php://input');
    $in = json_decode($raw_input ?: '{}', true) ?: [];
    
    $account       = trim((string)($in['account'] ?? ''));
    $password      = (string)($in['password'] ?? '');
    $device_token  = trim((string)($in['device_token'] ?? ''));   // 🔥 新增：接收裝置識別碼
    
    if ($account === '' || $password === '') {
        json_err('缺少帳號或密碼', 400);
    }
    
    // 連接資料庫
    $pdo = pdo();
    
    // 用 id 或 ID_card 登入
    $sql = "SELECT `id`, `name`, `password_hash`, `email`, `Position`, `ID_card`, `role`, `device_token`
            FROM `員工基本資料`
            WHERE `id` = ? OR `ID_card` = ?
            LIMIT 1";
    
    $st = $pdo->prepare($sql);
    $st->execute([$account, $account]);
    $u = $st->fetch();
    
    if (!$u) {
        json_err('帳號或密碼錯誤', 401);
    }
    
    // 驗證密碼
    $hash = (string)($u['password_hash'] ?? '');
    
    if (empty($hash)) {
        json_err('密碼未設定，請聯繫管理員', 401);
    }
    
    $ok = false;
    $isHashed = (bool)preg_match('/^\$2[aby]\$|^\$argon2(id)?\$/', $hash);
    
    if ($isHashed) {
        $ok = password_verify($password, $hash);
        
        if ($ok && password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE `員工基本資料` SET `password_hash` = ? WHERE `id` = ?")
                ->execute([$newHash, $u['id']]);
        }
    } else {
        $ok = hash_equals($hash, $password);
        
        if ($ok && $hash !== '') {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE `員工基本資料` SET `password_hash` = ? WHERE `id` = ?")
                ->execute([$newHash, $u['id']]);
        }
    }
    
    if (!$ok) {
        json_err('帳號或密碼錯誤', 401);
    }

    // ============================================================
    // 🔥🔥 裝置綁定邏輯（僅限 B 級） START
    // ============================================================
    
    // 判斷等級（先用 ID 開頭）
    $employeeId = strtoupper(trim((string)$u['id']));
    $firstChar = substr($employeeId, 0, 1);

    $userLevel = '';
    
    if ($firstChar === 'A')       $userLevel = 'A';
    elseif ($firstChar === 'B')  $userLevel = 'B';
    elseif ($firstChar === 'C' || is_numeric($firstChar)) $userLevel = 'C';
    else $userLevel = strtoupper(trim($u['role'] ?? 'C'));

    // 🔥 若是 B 級，啟動裝置綁定
    if ($userLevel === 'B') {

        if ($device_token === '') {
            json_err("缺少 device_token，無法登入", 400);
        }

        $savedToken = trim((string)$u['device_token']);

        if ($savedToken === '') {
            // 第一次登入 → 綁定
            $stmtBind = $pdo->prepare("UPDATE `員工基本資料` SET `device_token` = ? WHERE `id` = ?");
            $stmtBind->execute([$device_token, $u['id']]);

        } else {
            // 已綁定 → 驗證是否同一台裝置
            if ($savedToken !== $device_token) {
                http_response_code(403);
                echo json_encode([
                    "error" => "此帳號已綁定其他裝置，無法在此裝置登入"
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }

    // ============================================================
    // 🔥🔥 裝置綁定邏輯 END
    // ============================================================

    
    // 正常權限判斷與 redirect
    $redirectPage = '';
    
    if ($userLevel === 'A')       $redirectPage = 'index.php';
    elseif ($userLevel === 'B')  $redirectPage = 'indexB.php';
    else                         $redirectPage = 'indexC.php';
    
    $role_map = [
        'A' => ['code' => 'boss',     'name' => '老闆',   'level' => 3],
        'B' => ['code' => 'manager',  'name' => '管理員', 'level' => 2],
        'C' => ['code' => 'employee', 'name' => '員工',   'level' => 1],
    ];
    
    $role_info = $role_map[$userLevel] ?? $role_map['C'];
    
    // 設定 session
    $_SESSION['uid']        = $u['id'];
    $_SESSION['name']       = $u['name'];
    $_SESSION['email']      = $u['email'];
    $_SESSION['position']   = $u['Position'] ?? '';
    $_SESSION['ID_card']    = $u['ID_card'] ?? '';
    $_SESSION['role']       = $role_info['code'];
    $_SESSION['role_level'] = $role_info['level'];
    $_SESSION['role_name']  = $role_info['name'];
    $_SESSION['role_code']  = $userLevel;
    $_SESSION['user_level'] = $userLevel;
    $_SESSION['login_at']   = date('Y-m-d H:i:s');
    
    error_log("Login Success - ID: {$u['id']} (Level: {$userLevel}), Redirect: {$redirectPage}");
    
    json_ok([
        'ok'       => true,
        'redirect' => $redirectPage,
        'user'     => [
            'id'         => $u['id'],
            'name'       => $u['name'],
            'email'      => $u['email'],
            'position'   => $u['Position'] ?? '',
            'ID_card'    => $u['ID_card'] ?? '',
            'role'       => $role_info['code'],
            'role_code'  => $userLevel,
            'role_name'  => $role_info['name'],
            'role_level' => $role_info['level'],
            'level'      => $userLevel,
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'DATABASE_ERROR',
        'message' => '資料庫錯誤：' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
    
} catch (Throwable $e) {
    error_log("Login Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'SERVER_ERROR',
        'message' => '伺服器錯誤：' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
