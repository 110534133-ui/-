<?php
/* ==== 基本設定：請改這四個 ==== */
$DB_HOST = '127.0.0.1';
$DB_NAME = 'lamian';
$DB_USER = 'root';
$DB_PASS = '';

/* ==== 連線 ==== */
$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  echo "<p>✅ DB connected.</p>";
} catch (PDOException $e) {
  http_response_code(500);
  exit("<p>❌ DB connect failed: ".h($e->getMessage())."</p>");
}

/* ==== 當前 DB 與表 ==== */
echo "<p>當前資料庫：".h($pdo->query("SELECT DATABASE()")->fetchColumn())."</p>";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "<p>當前DB的資料表：".h(implode(", ", $tables))."</p>";
if (!in_array('employees', $tables) || !in_array('attendance', $tables)) {
  echo "<p>⚠️ 找不到 employees 或 attendance 表（注意大小寫）。</p>";
}

/* ==== 總筆數 ==== */
$cntA = (int)$pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
$cntE = (int)$pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
echo "<p>attendance 筆數：{$cntA}，employees 筆數：{$cntE}</p>";

/* ==== attendance 樣本 ==== */
echo "<h3>attendance 前5筆</h3>";
$stmt = $pdo->query("SELECT id,user_id,clock_in,clock_out,hours,status FROM attendance ORDER BY id DESC LIMIT 5");
echo "<pre>".h(json_encode($stmt->fetchAll(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))."</pre>";

/* ==== 用 employee_id 做 JOIN（你目前採用的對應） ==== */
echo "<h3>JOIN 測試：attendance.user_id ↔ employees.employee_id</h3>";
$sql1 = "
SELECT
  a.id, a.user_id,
  e.employee_id, e.emp_no, e.name AS employee_name,
  a.clock_in, a.clock_out,
  ROUND(CASE WHEN a.clock_out IS NOT NULL
       THEN TIMESTAMPDIFF(MINUTE,a.clock_in,a.clock_out)/60 END,2) AS hours_calc,
  CASE
    WHEN a.clock_out IS NULL THEN '缺卡'
    WHEN TIMESTAMPDIFF(MINUTE,a.clock_in,a.clock_out) > 8*60 THEN '加班'
    ELSE '正常'
  END AS status_calc
FROM attendance a
JOIN employees e ON a.user_id = e.employee_id
ORDER BY a.clock_in DESC
LIMIT 10";
echo "<pre>".h(json_encode($pdo->query($sql1)->fetchAll(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))."</pre>";

/* ==== 若你懷疑 user_id 其實存的是 emp_no（字串工號），也測一下 ==== */
echo "<h3>JOIN 測試（備用）：attendance.user_id ↔ employees.emp_no</h3>";
$sql2 = "
SELECT a.id, a.user_id, e.emp_no, e.employee_id, e.name AS employee_name
FROM attendance a
JOIN employees e ON e.emp_no = CAST(a.user_id AS CHAR)
ORDER BY a.clock_in DESC
LIMIT 10";
echo "<pre>".h(json_encode($pdo->query($sql2)->fetchAll(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))."</pre>";

/* ==== 孤兒資料（對不到員工的 user_id） ==== */
echo "<h3>對不到員工（以 employee_id 為準）</h3>";
$orph1 = $pdo->query("
SELECT DISTINCT a.user_id
FROM attendance a
LEFT JOIN employees e ON e.employee_id = a.user_id
WHERE e.id IS NULL
LIMIT 50
")->fetchAll(PDO::FETCH_COLUMN);
echo $orph1 ? "<pre>".h(json_encode($orph1, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))."</pre>" : "<p>✅ 無</p>";

echo "<h3>對不到員工（以 emp_no 為準）</h3>";
$orph2 = $pdo->query("
SELECT DISTINCT a.user_id
FROM attendance a
LEFT JOIN employees e ON e.emp_no = CAST(a.user_id AS CHAR)
WHERE e.id IS NULL
LIMIT 50
")->fetchAll(PDO::FETCH_COLUMN);
echo $orph2 ? "<pre>".h(json_encode($orph2, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))."</pre>" : "<p>✅ 無</p>";
