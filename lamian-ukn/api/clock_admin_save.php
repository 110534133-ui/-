<?php
// /lamian-ukn/api/clock_admin_save.php (完整修正版)
require __DIR__.'/config.php';

// 錯誤日誌函數
function logError($msg, $data = null) {
    error_log("[clock_admin_save] " . $msg . ($data ? " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) : ""));
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

try{
  $pdo  = pdo();
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  
  logError("=== 開始處理請求 ===");
  logError("Received data", $body);

  // 取得參數
  $att_id    = isset($body['id']) && $body['id']!=='' ? (int)$body['id'] : null;
  $date      = ymd_or_fail($body['date'] ?? '');
  $emp_code  = trim((string)($body['user_id'] ?? ''));
  $cin       = hhmm_or_null($body['clock_in'] ?? null);
  $cout      = hhmm_or_null($body['clock_out'] ?? null);
  $statusIn  = trim((string)($body['status'] ?? ''));
  $note      = trim((string)($body['note'] ?? ''));

  logError("Parsed params", [
    'att_id' => $att_id,
    'date' => $date,
    'emp_code' => $emp_code,
    'clock_in' => $cin,
    'clock_out' => $cout,
    'status' => $statusIn,
    'note' => $note
  ]);

  if($emp_code==='') {
    logError("ERROR: emp_code is empty");
    err('員工編號不可為空',400);
  }

  // === 查詢員工（使用中文表名）===
  $sqlEmp = "SELECT `id`, `name`, `position`, `role`
             FROM `員工基本資料` 
             WHERE `id` = :code
             LIMIT 1";
  
  logError("Executing employee query", ['sql' => $sqlEmp, 'code' => $emp_code]);
  
  $st = $pdo->prepare($sqlEmp);
  $st->execute([':code' => $emp_code]);
  $emp = $st->fetch(PDO::FETCH_ASSOC);
  
  if(!$emp){ 
    logError("ERROR: Employee not found", ['emp_code' => $emp_code]);
    
    // 額外檢查：列出資料表中的所有 id
    try {
      $allIds = $pdo->query("SELECT `id` FROM `員工基本資料` LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
      logError("Available employee IDs (first 10)", $allIds);
    } catch (Exception $e) {
      logError("Failed to fetch employee IDs", ['error' => $e->getMessage()]);
    }
    
    err('找不到員工編號：'.$emp_code . '，請確認該員工是否存在於「員工基本資料」表中', 404); 
  }
  
  logError("Found employee", $emp);

  // 組合 datetime
  $cin_dt  = $cin  ? "$date $cin:00"  : null;
  $cout_dt = $cout ? "$date $cout:00" : null;

  logError("Datetime values", ['clock_in_dt' => $cin_dt, 'clock_out_dt' => $cout_dt]);

  // 計算工時 & 狀態
  $hours = null; 
  $status = $statusIn !== '' ? $statusIn : null;
  
  if($cin_dt && $cout_dt){
    $stH = $pdo->prepare("SELECT TIMESTAMPDIFF(MINUTE, :cin, :cout) AS m");
    $stH->execute([':cin' => $cin_dt, ':cout' => $cout_dt]);
    $m = (int)$stH->fetchColumn();
    
    if($m < 0) $m += 1440; // 跨日處理
    $hours = round($m/60, 2);
    
    // 如果沒有手動指定狀態,自動判斷
    if($status === null) {
      $status = ($m > 480) ? '加班' : '正常';
    }
  } else {
    // 有任一邊缺卡
    if($status === null) $status = '缺卡';
  }

  logError("Calculated work data", ['hours' => $hours, 'status' => $status, 'minutes' => $m ?? null]);

  // === 使用員工的 id（varchar 類型）作為 user_id ===
  $user_id = $emp['id'];

  if($att_id){ 
    // === UPDATE ===
    logError("=== UPDATE Mode ===", ['attendance_id' => $att_id]);
    
    $sql = "UPDATE `attendance`
            SET user_id = :uid,
                clock_in = :cin,
                clock_out = :cout,
                hours = :hrs,
                status = :st,
                note = :note
            WHERE id = :id";
    
    $params = [
      ':uid'  => $user_id,  // 使用 varchar 的 id
      ':cin'  => $cin_dt,
      ':cout' => $cout_dt,
      ':hrs'  => $hours,
      ':st'   => $status,
      ':note' => ($note!=='') ? $note : null,
      ':id'   => $att_id
    ];
    
    logError("Update SQL params", $params);
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if(!$result) {
      $errorInfo = $stmt->errorInfo();
      logError("ERROR: Update failed", $errorInfo);
      err('更新失敗', 500, ['error' => $errorInfo]);
    }
    
    $affectedRows = $stmt->rowCount();
    logError("Update success", ['affected_rows' => $affectedRows]);
    
    if($affectedRows === 0) {
      logError("WARNING: No rows updated (record may not exist)", ['att_id' => $att_id]);
    }
    
  } else { 
    // === INSERT ===
    logError("=== INSERT Mode ===");
    
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
    
    logError("Insert SQL params", $params);
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if(!$result) {
      $errorInfo = $stmt->errorInfo();
      logError("ERROR: Insert failed", $errorInfo);
      err('新增失敗', 500, ['error' => $errorInfo]);
    }
    
    $att_id = (int)$pdo->lastInsertId();
    logError("Insert success", ['new_attendance_id' => $att_id]);
  }

  logError("=== 處理成功 ===");
  
  ok([
    'ok' => true,
    'id' => $att_id,
    'emp' => $emp,
    'status' => $status,
    'hours' => $hours,
    'message' => ($att_id && isset($result)) ? '更新成功' : '新增成功'
  ]);

} catch(PDOException $ex){
  logError("=== PDO Exception ===", [
    'message' => $ex->getMessage(),
    'code' => $ex->getCode(),
    'file' => $ex->getFile(),
    'line' => $ex->getLine()
  ]);
  err('資料庫操作失敗', 500, ['detail' => $ex->getMessage()]);
  
} catch(Throwable $ex){
  logError("=== General Exception ===", [
    'message' => $ex->getMessage(),
    'file' => $ex->getFile(),
    'line' => $ex->getLine(),
    'trace' => $ex->getTraceAsString()
  ]);
  err('操作失敗', 500, ['detail' => $ex->getMessage()]);
}