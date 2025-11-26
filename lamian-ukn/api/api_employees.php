<?php
// /lamian-ukn/api/api_employees.php
// 🔥 修正：改為讀取標準的 db_config.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// 🔒 權限檢查：只有 A 級可以操作員工資料
require_once __DIR__ . '/../includes/auth_check.php';
if (!check_user_level('A', false)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => '權限不足：只有老闆可以管理員工資料'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 🔥 修正：載入標準資料庫設定
require_once __DIR__ . '/db_config.php';

try {
    // 🔥 修正：使用 getDbConnection()
    $pdo = getDbConnection(); 
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '資料庫連線失敗',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$table = "員工基本資料";

// ( ... 以下的 function read_json(), validateIdCard(), generateNewEmployeeID() ... )
// ( ... 還有 GET, POST, PUT, DELETE 的邏輯 ... )
// ( ... 全部保持不變 ... )

// 讀取 JSON 輸入
function read_json() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// 台灣身分證驗證函式
function validateIdCard($id) {
    if (!preg_match('/^[A-Z][12]\d{8}$/', $id)) return false;
    
    $letters = [
        'A'=>10,'B'=>11,'C'=>12,'D'=>13,'E'=>14,'F'=>15,'G'=>16,'H'=>17,
        'I'=>34,'J'=>18,'K'=>19,'L'=>20,'M'=>21,'N'=>22,'O'=>35,'P'=>23,
        'Q'=>24,'R'=>25,'S'=>26,'T'=>27,'U'=>28,'V'=>29,'W'=>32,'X'=>30,
        'Y'=>31,'Z'=>33
    ];
    
    $first_value = $letters[$id[0]];
    $sum = intval($first_value/10) + ($first_value%10)*9;
    
    for ($i=1; $i<=8; $i++) {
        $sum += intval($id[$i]) * (9-$i);
    }
    
    $check_digit = (10 - ($sum%10)) % 10;
    return $check_digit == intval($id[9]);
}

function generateNewEmployeeID($pdo, $table, $role) {
    $prefix = '';
    $startNum = 0;
    $padding = 4;

    switch ($role) {
        case 'A': $prefix = 'A10'; break;
        case 'B': $prefix = 'B12'; break;
        case 'C': $prefix = 'C13'; break;
        default: throw new Exception("無效的權限等級");
    }

    $sql = "SELECT MAX(CAST(SUBSTR(id, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) 
            FROM `$table` 
            WHERE id LIKE ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$prefix . '%']);
    $maxNum = $stmt->fetchColumn();

    if ($maxNum === null) {
        $newNum = $startNum;
    } else {
        $newNum = intval($maxNum) + 1;
    }

    $newID = $prefix . str_pad($newNum, $padding, '0', STR_PAD_LEFT);
    return $newID;
}


$method = strtoupper($_SERVER['REQUEST_METHOD']);

try {
    // ==================== GET：取得所有員工 ====================
    if ($method === 'GET') {
        $keyword = $_GET['keyword'] ?? '';
        $field = $_GET['searchField'] ?? 'name';
        $allowedFields = ['name', 'id', 'email', 'telephone', 'ID_card'];
        
        if (!in_array($field, $allowedFields)) $field = 'name';

        try {
            if ($keyword) {
                $sql = "SELECT id, name, birth_date, Position, role, base_salary, hourly_rate, 
                               Telephone, email, address, ID_card
                        FROM `$table` 
                        WHERE $field LIKE ?
                        ORDER BY id ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(["%$keyword%"]);
            } else {
                $sql = "SELECT id, name, birth_date, Position, role, base_salary, hourly_rate, 
                               Telephone, email, address, ID_card
                        FROM `$table`
                        ORDER BY id ASC";
                $stmt = $pdo->query($sql);
            }

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = array_map(function($row) {
                $row['position'] = $row['Position'];
                $row['telephone'] = $row['Telephone'];
                $row['id_card'] = $row['ID_card'];
                return $row;
            }, $data);
            
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => '查詢失敗：' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ==================== POST：新增員工 ====================
    if ($method === 'POST') {
        $data = read_json();
        if (!$data || !is_array($data)) {
            $data = $_POST;
        }

        $name        = trim($data['name'] ?? '');
        $birth_date  = trim($data['birth_date'] ?? '');
        $role        = trim($data['role'] ?? '');
        $position    = trim($data['position'] ?? $data['Position'] ?? '');
        $base_salary = isset($data['base_salary']) ? (floatval($data['base_salary']) > 0 ? floatval($data['base_salary']) : null) : null;
        $hourly_rate = isset($data['hourly_rate']) ? (floatval($data['hourly_rate']) > 0 ? floatval($data['hourly_rate']) : null) : null;
        $telephone   = trim($data['telephone'] ?? $data['Telephone'] ?? '');
        $email       = trim($data['email'] ?? '');
        $address     = trim($data['address'] ?? '');
        $id_card     = strtoupper(trim($data['id_card'] ?? $data['ID_card'] ?? ''));

        $missing = [];
        if (!$name) $missing[] = 'name';
        if (!$birth_date) $missing[] = 'birth_date';
        if (!$role) $missing[] = 'role';
        if (!$position) $missing[] = 'position';
        if (!$telephone) $missing[] = 'telephone';
        if (!$address) $missing[] = 'address';
        if (!$id_card) $missing[] = 'id_card';
        
        if ($missing) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '資料不完整，缺少：' . implode(', ', $missing)
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Email 格式不正確'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '出生年月日格式錯誤，應為 YYYY-MM-DD'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        list($year, $month, $day) = explode('-', $birth_date);
        if (!checkdate($month, $day, $year)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '日期無效'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!validateIdCard($id_card)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '身份證格式或檢查碼錯誤'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!preg_match('/^09\d{8}$/', $telephone)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '電話格式錯誤，應為 09XXXXXXXX'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo->beginTransaction();
        try {
            $checkStmt = $pdo->prepare("SELECT id FROM `$table` WHERE ID_card=?");
            $checkStmt->execute([$id_card]);
            if ($checkStmt->fetch()) {
                throw new Exception('身份證號碼已存在');
            }

            if (!empty($email)) {
                $checkEmailStmt = $pdo->prepare("SELECT id FROM `$table` WHERE email=?");
                $checkEmailStmt->execute([$email]);
                if ($checkEmailStmt->fetch()) {
                    throw new Exception('Email 已被使用');
                }
            }

            $new_employee_id = generateNewEmployeeID($pdo, $table, $role);

            $sql = "INSERT INTO `$table` 
                    (id, name, birth_date, role, Position, base_salary, hourly_rate, 
                     Telephone, email, address, ID_card)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            
            $default_password = substr($id_card, -4);
            
            $stmt->execute([
                $new_employee_id,
                $name, $birth_date, $role, $position, $base_salary, $hourly_rate,
                $telephone, $email, $address, $id_card
            ]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => '新增成功',
                'data' => [
                    'employee_id' => $new_employee_id,
                    'account' => $id_card,
                    'default_password' => $default_password
                ]
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            
            http_response_code($e instanceof PDOException ? 500 : 400);
            echo json_encode([
                'success' => false,
                'message' => '新增失敗：' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ==================== PUT：更新員工 ====================
    if ($method === 'PUT') {
        $data = read_json();
        if ($data === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'JSON 解析失敗'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id          = $data['id'] ?? null;
        $name        = trim($data['name'] ?? '');
        $birth_date  = trim($data['birth_date'] ?? '');
        $role        = trim($data['role'] ?? '');
        $position    = trim($data['position'] ?? $data['Position'] ?? '');
        $base_salary = isset($data['base_salary']) ? floatval($data['base_salary']) : null;
        $hourly_rate = isset($data['hourly_rate']) ? floatval($data['hourly_rate']) : null;
        $telephone   = trim($data['telephone'] ?? $data['Telephone'] ?? '');
        $email       = trim($data['email'] ?? '');
        $address     = trim($data['address'] ?? '');
        $id_card     = strtoupper(trim($data['id_card'] ?? $data['ID_card'] ?? ''));

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '缺少員工 ID'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email 格式不正確'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!empty($email)) {
             $checkEmailStmt = $pdo->prepare("SELECT id FROM `$table` WHERE email=? AND id!=?");
             $checkEmailStmt->execute([$email, $id]);
             if ($checkEmailStmt->fetch()) {
                 http_response_code(400);
                 echo json_encode(['success' => false, 'message' => 'Email 已被其他員工使用'], JSON_UNESCAPED_UNICODE);
                 exit;
             }
        }
        if ($id_card && !validateIdCard($id_card)) {
             http_response_code(400);
             echo json_encode(['success' => false, 'message' => '身份證格式或檢查碼錯誤'], JSON_UNESCAPED_UNICODE);
             exit;
        }
        if ($telephone && !preg_match('/^09\d{8}$/', $telephone)) {
             http_response_code(400);
             echo json_encode(['success' => false, 'message' => '電話格式錯誤'], JSON_UNESCAPED_UNICODE);
             exit;
        }
        
        // 🔥 移除：錯誤的薪資處理 (role 應為 A/B/C)
        // if ($role === '正職') {
        //     $hourly_rate = null;
        // } elseif ($role === '臨時員工') {
        //     $base_salary = null;
        // }

        try {
            $sql = "UPDATE `$table` 
                    SET name=?, birth_date=?, role=?, Position=?, base_salary=?, hourly_rate=?, 
                        Telephone=?, email=?, address=?, ID_card=?
                    WHERE id=?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $name, $birth_date, $role, $position, $base_salary, $hourly_rate,
                $telephone, $email, $address, $id_card, $id
            ]);

            echo json_encode(['success' => true, 'message' => '更新成功'], JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '更新失敗：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ==================== DELETE：刪除員工 ====================
    if ($method === 'DELETE') {
        $data = read_json();
        $id = $data['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '缺少員工 ID'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id=?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => '刪除成功'], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => '找不到該員工'], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '刪除失敗：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '伺服器錯誤',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>