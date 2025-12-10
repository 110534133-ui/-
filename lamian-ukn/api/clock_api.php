<?php
// /lamian-ukn/api/clock_api.php - 整合打卡管理 API
require __DIR__.'/config.php';

function logError($msg, $data = null) {
    error_log("[clock_api] " . $msg . ($data ? " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) : ""));
}

function hhmm_or_null($t){
    $t = trim((string)$t);
    if($t==='' || !preg_match('/^\d{2}:\d{2}$/',$t)) return null;
    return $t;
}

function ymd_or_fail($d){
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d??'')) err('日期格式必須是 YYYY-MM-DD', 400);
    return $d;
}

try {
    $pdo = pdo();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = g('action') ?? ''; // 從 query string 取得 action
    
    logError("=== Request Start ===", ['method' => $method, 'action' => $action]);

    // ==================== GET 請求 ====================
    if ($method === 'GET') {
        
        // 【查詢打卡列表】
        if ($action === 'list' || $action === '') {
            $s = g('start_date');
            $e = g('end_date');
            $q = g('q');
            
            logError("查詢參數", ['start_date' => $s, 'end_date' => $e, 'q' => $q]);
            
            $sql = "
            SELECT
                a.id,
                a.user_id,
                DATE(a.clock_in) AS date,
                DATE_FORMAT(a.clock_in,  '%H:%i') AS clock_in,
                DATE_FORMAT(a.clock_out, '%H:%i') AS clock_out,
                ROUND(COALESCE(a.hours, TIMESTAMPDIFF(MINUTE, a.clock_in, a.clock_out)/60), 2) AS hours,
                COALESCE(
                    a.status,
                    CASE
                        WHEN a.clock_in IS NULL OR a.clock_out IS NULL THEN '缺卡'
                        WHEN TIMESTAMPDIFF(MINUTE, a.clock_in, a.clock_out) > 480 THEN '加班'
                        ELSE '正常'
                    END
                ) AS status,
                a.note,
                e.`name` AS emp_name,
                e.`id` AS employee_id,
                e.`position` AS emp_position
            FROM `attendance` a
            LEFT JOIN `員工基本資料` e ON CAST(a.user_id AS CHAR) = CAST(e.`id` AS CHAR)
            WHERE 1=1
            ";
            
            $p = [];
            
            if ($s) { 
                $sql .= " AND DATE(a.clock_in) >= :s"; 
                $p[':s'] = $s; 
            }
            
            if ($e) { 
                $sql .= " AND DATE(a.clock_in) <= :e"; 
                $p[':e'] = $e; 
            }
            
            if ($q) {
                $sql .= " AND (e.`name` LIKE :q OR CAST(e.`id` AS CHAR) LIKE :q OR CAST(a.user_id AS CHAR) LIKE :q)";
                $p[':q'] = '%' . $q . '%';
            }
            
            $sql .= " ORDER BY a.clock_in DESC, a.id DESC LIMIT 1000";
            
            $st = $pdo->prepare($sql);
            $st->execute($p);
            $results = $st->fetchAll(PDO::FETCH_ASSOC);
            
            logError("查詢結果", ['count' => count($results)]);
            ok($results ?: []);
        }
        
        // 【查詢員工列表】
        elseif ($action === 'employees') {
            $sql = "SELECT `id`, `name`, `position`, `role`
                    FROM `員工基本資料`
                    ORDER BY `name`";
            
            $stmt = $pdo->query($sql);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ok($employees);
        }
        
        // 【查詢人臉資料】
        elseif ($action === 'faces') {
            try {
                $sql = "SELECT 
                            ef.id,
                            ef.employee_id,
                            ef.face_image,
                            ef.created_at,
                            ef.is_active,
                            e.`name` AS emp_name,
                            e.`position` AS emp_position
                        FROM `employee_faces` ef
                        LEFT JOIN `員工基本資料` e ON ef.employee_id = e.`id`
                        WHERE ef.is_active = 1
                        ORDER BY ef.created_at DESC";
                
                $stmt = $pdo->query($sql);
                $faces = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                ok($faces);
                
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), "doesn't exist") !== false) {
                    ok([]);
                } else {
                    throw $e;
                }
            }
        }
        
        else {
            err('未知的 action', 400);
        }
    }

    // ==================== POST 請求 ====================
    elseif ($method === 'POST') {
        
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        logError("POST body", $body);
        
        // 【管理員編輯打卡記錄】
        if ($action === 'admin_save') {
            $att_id    = isset($body['id']) && $body['id']!=='' ? (int)$body['id'] : null;
            $date      = ymd_or_fail($body['date'] ?? '');
            $emp_code  = trim((string)($body['user_id'] ?? ''));
            $cin       = hhmm_or_null($body['clock_in'] ?? null);
            $cout      = hhmm_or_null($body['clock_out'] ?? null);
            $statusIn  = trim((string)($body['status'] ?? ''));
            $note      = trim((string)($body['note'] ?? ''));
            
            if($emp_code==='') err('員工編號不可為空',400);
            
            // 查詢員工
            $sqlEmp = "SELECT `id`, `name`, `position`, `role`
                       FROM `員工基本資料` 
                       WHERE `id` = :code
                       LIMIT 1";
            
            $st = $pdo->prepare($sqlEmp);
            $st->execute([':code' => $emp_code]);
            $emp = $st->fetch(PDO::FETCH_ASSOC);
            
            if(!$emp) err('找不到員工編號：'.$emp_code, 404);
            
            // 組合 datetime
            $cin_dt  = $cin  ? "$date $cin:00"  : null;
            $cout_dt = $cout ? "$date $cout:00" : null;
            
            // 計算工時 & 狀態
            $hours = null; 
            $status = $statusIn !== '' ? $statusIn : null;
            
            if($cin_dt && $cout_dt){
                $stH = $pdo->prepare("SELECT TIMESTAMPDIFF(MINUTE, :cin, :cout) AS m");
                $stH->execute([':cin' => $cin_dt, ':cout' => $cout_dt]);
                $m = (int)$stH->fetchColumn();
                
                if($m < 0) $m += 1440;
                $hours = round($m/60, 2);
                
                if($status === null) {
                    $status = ($m > 480) ? '加班' : '正常';
                }
            } else {
                if($status === null) $status = '缺卡';
            }
            
            $user_id = $emp['id'];
            
            if($att_id){ 
                // UPDATE
                $sql = "UPDATE `attendance`
                        SET user_id = :uid,
                            clock_in = :cin,
                            clock_out = :cout,
                            hours = :hrs,
                            status = :st,
                            note = :note
                        WHERE id = :id";
                
                $params = [
                    ':uid'  => $user_id,
                    ':cin'  => $cin_dt,
                    ':cout' => $cout_dt,
                    ':hrs'  => $hours,
                    ':st'   => $status,
                    ':note' => ($note!=='') ? $note : null,
                    ':id'   => $att_id
                ];
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute($params);
                
                if(!$result) err('更新失敗', 500, ['error' => $stmt->errorInfo()]);
                
            } else { 
                // INSERT
                $sql = "INSERT INTO `attendance` (user_id, clock_in, clock_out, hours, status, note)
                        VALUES (:uid, :cin, :cout, :hrs, :st, :note)";
                
                $params = [
                    ':uid'  => $user_id,
                    ':cin'  => $cin_dt,
                    ':cout' => $cout_dt,
                    ':hrs'  => $hours,
                    ':st'   => $status,
                    ':note' => ($note!=='') ? $note : null
                ];
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute($params);
                
                if(!$result) err('新增失敗', 500, ['error' => $stmt->errorInfo()]);
                
                $att_id = (int)$pdo->lastInsertId();
            }
            
            ok([
                'ok' => true,
                'id' => $att_id,
                'emp' => $emp,
                'status' => $status,
                'hours' => $hours,
                'message' => ($att_id && isset($result)) ? '更新成功' : '新增成功'
            ]);
        }
        
        // 【員工打卡】
        elseif ($action === 'punch') {
            $deviceToken = $_SERVER['HTTP_X_DEVICE_TOKEN'] ?? '';
            if (!$deviceToken) err('裝置 token 必填', 401);
            
            $code   = trim($body['emp_code'] ?? '');
            $action_type = strtolower(trim($body['action'] ?? ''));
            
            if ($code === '') err('emp_code required', 400);
            if (!in_array($action_type, ['in','out'], true)) err('action must be in|out', 400);
            
            // 查詢員工資料
            $sqlEmp = "SELECT `".EMP_PK_COL."` AS id, `".EMP_NAME_COL."` AS name, `".EMP_CODE_COL."` AS code
                       FROM `".EMP_TABLE."` WHERE `".EMP_CODE_COL."` = :code LIMIT 1";
            $st = $pdo->prepare($sqlEmp);
            $st->execute([':code' => $code]);
            $emp = $st->fetch();
            if (!$emp) err('找不到該員工編號', 404);
            
            // 驗證裝置與 B 級管理者綁定
            $sqlDevice = "SELECT id, name FROM `".EMP_TABLE."` 
                          WHERE role='B' AND device_token=:token LIMIT 1";
            $stDevice = $pdo->prepare($sqlDevice);
            $stDevice->execute([':token' => $deviceToken]);
            $manager = $stDevice->fetch();
            if (!$manager) err('此裝置未授權或非 B 級管理者使用', 403);
            
            // 查詢是否已有未下班紀錄
            $sqlOpen = "SELECT * FROM `".ATT_TABLE."`
                        WHERE user_id=:uid AND clock_out IS NULL
                        ORDER BY clock_in DESC LIMIT 1";
            $st2 = $pdo->prepare($sqlOpen);
            $st2->execute([':uid' => $emp['id']]);
            $open = $st2->fetch();
            
            if ($action_type === 'in') {
                if ($open) err('已有未下班紀錄，請先下班', 409);
                
                $sqlIns = "INSERT INTO `".ATT_TABLE."` (user_id, clock_in)
                           VALUES (:uid, NOW())";
                $pdo->prepare($sqlIns)->execute([':uid'=>$emp['id']]);
                
                ok(['ok'=>true, 'message'=>'上班打卡成功', 'emp'=>$emp]);
                
            } else {
                if (!$open) err('找不到未下班紀錄，請先上班', 409);
                
                $sqlUpd = "UPDATE `".ATT_TABLE."`
                           SET clock_out=NOW(),
                               hours = ROUND(TIMESTAMPDIFF(MINUTE, clock_in, NOW())/60,2),
                               status = CASE WHEN TIMESTAMPDIFF(MINUTE, clock_in, NOW()) > 480 THEN '加班' ELSE '正常' END
                           WHERE id=:id";
                $pdo->prepare($sqlUpd)->execute([':id'=>$open['id']]);
                
                ok(['ok'=>true, 'message'=>'下班打卡成功', 'emp'=>$emp]);
            }
        }
        
        else {
            err('未知的 action', 400);
        }
    }

    // ==================== DELETE 請求 ====================
    elseif ($method === 'DELETE') {
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        
        if (!$id) err('缺少必要參數：id', 400);
        
        logError("=== 開始刪除操作 ===", ['id' => $id]);
        
        // 檢查記錄是否存在
        $checkSql = "SELECT id, user_id, clock_in FROM `attendance` WHERE id = :id LIMIT 1";
        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([':id' => $id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) err('找不到此打卡記錄', 404);
        
        // 執行刪除
        $deleteSql = "DELETE FROM `attendance` WHERE id = :id";
        $deleteStmt = $pdo->prepare($deleteSql);
        $result = $deleteStmt->execute([':id' => $id]);
        
        if (!$result || $deleteStmt->rowCount() === 0) {
            err('刪除失敗', 500);
        }
        
        logError("=== 刪除成功 ===", ['id' => $id]);
        
        ok([
            'success' => true,
            'ok' => true,
            'message' => '打卡記錄已刪除',
            'deleted_id' => $id
        ]);
    }

    else {
        err('不支援的請求方法', 405);
    }

} catch(PDOException $ex) {
    logError("=== PDO Exception ===", [
        'message' => $ex->getMessage(),
        'code' => $ex->getCode()
    ]);
    err('資料庫操作失敗', 500, ['detail' => $ex->getMessage()]);
    
} catch(Throwable $ex) {
    logError("=== General Exception ===", [
        'message' => $ex->getMessage(),
        'file' => $ex->getFile(),
        'line' => $ex->getLine()
    ]);
    err('操作失敗', 500, ['detail' => $ex->getMessage()]);
}