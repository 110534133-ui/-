<?php
// /lamian-ukn/api/clock_list.php (修正版)
require __DIR__.'/config.php';

function logError($msg, $data = null) {
    error_log("[clock_list] " . $msg . ($data ? " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) : ""));
}

try {
  $pdo = pdo();
  $s = g('start_date');  // YYYY-MM-DD
  $e = g('end_date');    // YYYY-MM-DD
  $q = g('q');           // 關鍵字（姓名/編號）

  logError("查詢參數", ['start_date' => $s, 'end_date' => $e, 'q' => $q]);

  // === 修正：處理 varchar 與 int 的類型轉換 ===
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
  
  logError("執行 SQL", ['sql' => $sql, 'params' => $p]);
  
  $st = $pdo->prepare($sql);
  $st->execute($p);
  $results = $st->fetchAll(PDO::FETCH_ASSOC);
  
  logError("查詢結果", ['count' => count($results)]);
  
  // 確保返回空陣列而不是 null
  if (empty($results)) {
    logError("⚠️ 查詢結果為空");
    ok([]);
  } else {
    logError("✅ 成功取得資料", ['first_row' => $results[0]]);
    ok($results);
  }

} catch(PDOException $ex) {
  logError("❌ 資料庫錯誤", [
    'message' => $ex->getMessage(),
    'code' => $ex->getCode()
  ]);
  err('資料庫查詢失敗', 500, ['detail' => $ex->getMessage()]);
  
} catch(Throwable $ex) {
  logError("❌ 系統錯誤", [
    'message' => $ex->getMessage(),
    'file' => $ex->getFile(),
    'line' => $ex->getLine()
  ]);
  err('查詢失敗', 500, ['detail' => $ex->getMessage()]);
}