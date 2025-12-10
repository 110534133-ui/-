<?php
// 🔥 完整修正版：api/product_master_api.php
error_reporting(E_ALL);
ini_set('display_errors', '0');

mb_internal_encoding('UTF-8');
header('Content-Type: application/json; charset=utf-8');

// ===== 錯誤處理函數 =====
function sendError($message, $code = 500, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge(['error' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function sendSuccess($data) {
    http_response_code(200);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 載入必要檔案 =====
try {
    if (!file_exists(__DIR__ . '/../includes/auth_check.php')) {
        throw new Exception('auth_check.php 不存在');
    }
    require_once __DIR__ . '/../includes/auth_check.php';
    
    if (!file_exists(__DIR__ . '/config.php')) {
        throw new Exception('config.php 不存在');
    }
    require_once __DIR__ . '/config.php';
} catch (Exception $e) {
    error_log("File load error: " . $e->getMessage());
    sendError('系統檔案載入失敗', 500, ['detail' => $e->getMessage()]);
}

// ===== 全域錯誤捕獲 =====
try {
    // 僅限 POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('此 API 僅支援 POST', 405);
    }

    // 權限檢查
    if (!function_exists('check_user_level')) {
        sendError('權限檢查函數不存在', 500);
    }
    
    if (!check_user_level('A', false)) {
        sendError('權限不足 (僅限 A 級)', 403);
    }

    // 取得資料庫連線
    if (!function_exists('pdo')) {
        sendError('資料庫連線函數不存在', 500);
    }
    
    $pdo = pdo();
    if (!$pdo) {
        sendError('無法連接資料庫', 500);
    }
    
    // 設定 PDO
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ===== 解析請求資料 =====
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);
    
    if (!is_array($body)) {
        $body = [];
    }

    $action = $body['action'] ?? ($_POST['action'] ?? '');
    
    if (empty($action)) {
        sendError('缺少 action 參數', 400);
    }

    // ===== 路由處理 =====
    switch ($action) {
        
        // ------------------------------------
        // (C/U) 儲存商品
        // ------------------------------------
        case 'save':
            $id          = $body['id']          ?? ($_POST['id']          ?? null);
            $name        = $body['name']        ?? ($_POST['name']        ?? '');
            $unit        = $body['unit']        ?? ($_POST['unit']        ?? '');
            $category_id = $body['category_id'] ?? ($_POST['category_id'] ?? null);

            $name = trim($name);
            $unit = trim($unit);

            if ($name === '') {
                sendError('品項名稱不可為空', 422);
            }
            if ($unit === '') {
                sendError('單位不可為空', 422);
            }
            if ($category_id === null || $category_id === '') {
                sendError('必須選擇分類', 422);
            }

            $category_id = (int)$category_id;

            // 檢查分類是否存在
            $catCheckSql = "SELECT id FROM `商品分類` WHERE id = :cid";
            $catCheckStmt = $pdo->prepare($catCheckSql);
            $catCheckStmt->execute([':cid' => $category_id]);
            if (!$catCheckStmt->fetch()) {
                sendError('選擇的分類不存在', 422);
            }

            if ($id !== null && $id !== '') {
                // === 更新 ===
                $id = (int)$id;
                
                $checkSql = "SELECT id FROM `庫存商品` WHERE id = :id";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([':id' => $id]);
                if (!$checkStmt->fetch()) {
                    sendError('找不到該商品', 404);
                }
                
                $sql = "UPDATE `庫存商品`
                        SET name = :name, unit = :unit, category_id = :cid
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':name' => $name,
                    ':unit' => $unit,
                    ':cid'  => $category_id,
                    ':id'   => $id,
                ]);
                
                sendSuccess([
                    'ok' => true, 
                    'id' => (int)$id,
                    'action' => 'update',
                    'affected' => $stmt->rowCount()
                ]);
                
            } else {
                // === 新增 ===
                $sql = "INSERT INTO `庫存商品` (name, unit, category_id)
                        VALUES (:name, :unit, :cid)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':name' => $name,
                    ':unit' => $unit,
                    ':cid'  => $category_id,
                ]);
                
                $newId = (int)$pdo->lastInsertId();
                
                sendSuccess([
                    'ok' => true, 
                    'id' => $newId,
                    'action' => 'insert'
                ]);
            }
            break;

        // ------------------------------------
        // (D) 刪除商品
        // ------------------------------------
        case 'delete':
            $id = $body['id'] ?? ($_POST['id'] ?? null);

            if ($id === null || $id === '') {
                sendError('缺少 ID 參數', 422);
            }

            $id = (int)$id;

            $checkSql = "SELECT id, name FROM `庫存商品` WHERE id = :id";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([':id' => $id]);
            $product = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                sendError('找不到該商品 (ID: ' . $id . ')', 404);
            }

            $sql = "DELETE FROM `庫存商品` WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([':id' => $id]);
            
            if (!$success) {
                sendError('刪除失敗', 500, ['db_error' => $stmt->errorInfo()]);
            }

            sendSuccess([
                'ok' => true, 
                'id' => $id,
                'deleted' => $stmt->rowCount(),
                'name' => $product['name']
            ]);
            break;

        default:
            sendError('未知的操作: ' . $action, 400);
    }

} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendError('資料庫錯誤', 500, ['detail' => $e->getMessage()]);
    
} catch (Throwable $e) {
    error_log("General Error: " . $e->getMessage());
    error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendError('API 內部錯誤', 500, [
        'detail' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}