<?php
// 🔥 修正版：api/category_master_api.php
header('Content-Type: application/json; charset=utf-8');

// 引入設定檔和權限檢查
// (此路徑假定 api 在 /api/，includes 在 /includes/)
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/config.php';

try {
    // 使用 config.php 中的 pdo() 函數
    $pdo = pdo();
    
    // 決定操作 (GET 用 ?action=list, POST 用 body.action)
    $action = '';
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        $action = $_GET['action'];
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        if (isset($body['action'])) {
            $action = $body['action'];
        }
    }

    switch ($action) {
        // ------------------------------------
        // (R) 讀取分類列表
        // ------------------------------------
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                err('此操作僅支援 GET', 405);
            }
            // 🔥 修正：使用 check_user_level 並設定 $redirect = false
            // 允許所有登入的用戶查看列表
            if (!check_user_level(['A', 'B', 'C'], false)) {
                 err('未登入或無權限查看', 401);
            }
            
            $sql = "SELECT id, name FROM `商品分類` ORDER BY id ASC";
            $response = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            ok($response); // 🔥 修正：使用 ok() 函數
            break;

        // ------------------------------------
        // (C/U) 儲存分類
        // ------------------------------------
        case 'save':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                err('此操作僅支援 POST', 405);
            }
            // 🔥 修正：檢查 A 級權限，且 $redirect = false
            if (!check_user_level('A', false)) {
                 err('權限不足 (僅限 A 級)', 403);
            }
            
            $id = $body['id'] ?? null;
            $name = trim($body['name'] ?? '');

            if (empty($name)) {
                err('分類名稱不可為空', 422);
            }

            if ($id) {
                // 更新
                $sql = "UPDATE `商品分類` SET name = :name WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':name' => $name, ':id' => $id]);
                ok(['ok' => true, 'id' => $id, 'action' => 'update']);
            } else {
                // 新增
                $sql = "INSERT INTO `商品分類` (name) VALUES (:name)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':name' => $name]);
                $newId = $pdo->lastInsertId();
                ok(['ok' => true, 'id' => $newId, 'action' => 'insert']);
            }
            break;

        // ------------------------------------
        // (D) 刪除分類
        // ------------------------------------
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                err('此操作僅支援 POST', 405);
            }
            // 🔥 修正：檢查 A 級權限，且 $redirect = false
            if (!check_user_level('A', false)) {
                 err('權限不足 (僅限 A 級)', 403);
            }

            $id = $body['id'] ?? null;
            if (empty($id)) {
                err('缺少 ID', 422);
            }
            
            $sql = "DELETE FROM `商品分類` WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            ok(['ok' => true, 'id' => $id]);
            break;
            
        default:
            err('未知的操作', 400);
    }

} catch (Throwable $e) {
    // 🔥 修正：使用 err() 函數回報資料庫或程式錯誤
    error_log("category_master_api.php Error: " . $e->getMessage());
    err('API 內部錯誤', 500, ['detail' => $e->getMessage()]);
}