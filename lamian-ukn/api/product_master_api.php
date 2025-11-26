<?php
// 🔥 修正版：api/product_master_api.php
header('Content-Type: application/json; charset=utf-8');

// 引入設定檔和權限檢查
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/config.php';

try {
    // 僅限 POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        err('此 API 僅支援 POST', 405);
    }

    // 🔥 修正：檢查 A 級權限，且 $redirect = false
    if (!check_user_level('A', false)) {
         err('權限不足 (僅限 A 級)', 403);
    }

    // 使用 config.php 中的 pdo() 函數
    $pdo = pdo();
    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';

    switch ($action) {
        // ------------------------------------
        // (C/U) 儲存商品
        // ------------------------------------
        case 'save':
            $id = $body['id'] ?? null;
            $name = trim($body['name'] ?? '');
            $unit = trim($body['unit'] ?? '');
            $category_id = $body['category_id'] ?? null;

            if (empty($name) || empty($unit) || empty($category_id)) {
                err('品項名稱、單位、分類皆不可為空', 422);
            }

            if ($id) {
                // 更新
                $sql = "UPDATE `庫存商品` SET name = :name, unit = :unit, category_id = :cid WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':name' => $name,
                    ':unit' => $unit,
                    ':cid'  => $category_id,
                    ':id'   => $id
                ]);
                ok(['ok' => true, 'id' => $id, 'action' => 'update']);
            } else {
                // 新增
                $sql = "INSERT INTO `庫存商品` (name, unit, category_id) VALUES (:name, :unit, :cid)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':name' => $name,
                    ':unit' => $unit,
                    ':cid'  => $category_id
                ]);
                $newId = $pdo->lastInsertId();
                ok(['ok' => true, 'id' => $newId, 'action' => 'insert']);
            }
            break;

        // ------------------------------------
        // (D) 刪除商品
        // ------------------------------------
        case 'delete':
            $id = $body['id'] ?? null;
            if (empty($id)) {
                err('缺少 ID', 422);
            }
            
            $sql = "DELETE FROM `庫存商品` WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            ok(['ok' => true, 'id' => $id]);
            break;

        default:
            err('未知的操作', 400);
    }

} catch (Throwable $e) {
    // 🔥 修正：使用 err() 函數回報資料庫或程式錯誤
    error_log("product_master_api.php Error: " . $e->getMessage());
    err('API 內部錯誤', 500, ['detail' => $e->getMessage()]);
}