<?php
// /lamian-ukn/api/me.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth_core.php';
require_once __DIR__ . '/config.php';

// 🔥 除錯
error_log("=== me.php called ===");
error_log("Session ID: " . session_id());
error_log("Session UID: " . ($_SESSION['uid'] ?? 'NOT SET'));

$uid = require_login_core();
error_log("✅ User logged in: " . $uid);


try {
    $pdo = pdo();
    // ★ 只選取存在的欄位
    $sql = "SELECT
            id,
            name,
            birth_date,
            role,
            Position,
            base_salary,
            hourly_rate,
            Telephone,
            address,
            ID_card,
            email,
            avatar_url,
            emergency_contact,
            emergency_phone,
            memo
        FROM `員工基本資料`
        WHERE id = ?";

    $st = $pdo->prepare($sql);
    $st->execute([$uid]);
    $u = $st->fetch(PDO::FETCH_ASSOC); // 建議使用 FETCH_ASSOC

    if (!$u) {
        http_response_code(404);
        echo json_encode(['error' => 'NOT_FOUND'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // === 處理頭像網址 (與之前相同) ===
    $avatar_url = null;
    if (!empty($u['avatar_url'])) {
        // ... (處理 avatar_url 的邏輯不變) ...
         if (strpos($u['avatar_url'], 'http') === 0) {
            $avatar_url = $u['avatar_url'];
        } else {
            // 假設 API 在 /api， uploads 在 /uploads
            // 組合絕對 URL 或相對於網站根目錄的路徑
            // $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
            // $script_dir = dirname($_SERVER['SCRIPT_NAME']); // /lamian-ukn/api
            // $project_root = dirname($script_dir); // /lamian-ukn
            // $avatar_url = $base_url . $project_root . $u['avatar_url'];

             // --- 修正：使用相對於網站根目錄的路徑 ---
             // 假設您的 avatar_url 欄位存的是像 /lamian-ukn/uploads/avatar/xxx.png 的路徑
             // 或者 uploads 資料夾就在網站根目錄下，存的是 /uploads/avatar/xxx.png
             // 前端可以直接使用這個相對路徑，不需要加上 http://localhost
             $avatar_url = $u['avatar_url'];
        }
    } else {
        $avatar_url = 'https://i.pravatar.cc/240?img=12'; // 預設頭像
    }


    // === 組合輸出 ===
    echo json_encode([
        'id'                => (int)$u['id'],
        'username'          => $u['id'] ?? '',  // 🔥 用 id 代替 account
        'name'              => $u['name'] ?? '',
        'birth_date'        => $u['birth_date'],
        'role'              => $u['role'] ?? '',
        'Position'          => $u['Position'] ?? '',
        'base_salary'       => $u['base_salary'],
        'hourly_rate'       => $u['hourly_rate'],
        'Telephone'         => $u['Telephone'] ?? '',
        'address'           => $u['address'] ?? '',
        'ID_card'           => $u['ID_card'] ?? '',
        'email'             => $u['email'] ?? '',
        'avatar_url'        => $avatar_url,
        'emergency_contact' => $u['emergency_contact'] ?? '',
        'emergency_phone'   => $u['emergency_phone'] ?? '',
        'memo'              => $u['memo'] ?? '',
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}