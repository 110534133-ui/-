<?php
// C:\xampp\htdocs\lamian-ukn\api\index.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';

// 取 /api 後面的路徑
$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pos  = strpos($uri, '/api');
$path = $pos !== false ? substr($uri, $pos + 4) : '/';
$method = $_SERVER['REQUEST_METHOD'];

// 小工具：通用回傳
function json($data, int $code=200){
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
function bad($msg, int $code=400){ json(['error'=>$msg], $code); }

// 主要路由
if ($path === '/' || $path === '') {
  json(['ok'=>true, 'service'=>'lamian-ukn payroll api']);
}

// GET /salaries       取清單（支援 month, q, page, limit）
// GET /salaries/export 匯出 CSV
if (preg_match('#^/salaries(?:/export)?$#', $path)) {
  $isExport = str_ends_with($path, '/export');
  $month = isset($_GET['month']) ? month_to_int($_GET['month']) : (int)date('Ym');
  $q     = trim($_GET['q'] ?? '');
  $page  = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(100, max(1, (int)($_GET['limit'] ?? 10)));
  $offset= ($page-1)*$limit;

  // 基本 where
  $where = ['salary_month = :m'];
  $bind  = [':m' => $month];
  if ($q !== '') {
    $where[] = '(id LIKE :kw OR name LIKE :kw)';
    $bind[':kw'] = "%{$q}%";
  }
  $whereSql = 'WHERE '.implode(' AND ', $where);

  // 匯出不分頁；一般清單分頁
  if ($isExport) {
    header('Content-Type: text/csv; charset=utf-8');
    $fn = "薪資管理_{$month}.csv";
    header('Content-Disposition: attachment; filename="'.$fn.'"');
    // Excel 友善：加 BOM
    echo "\xEF\xBB\xBF";

    $sql = "
      SELECT 
        id AS user_id, name, salary_month, base_salary, hourly_rate, working_hours, 
        bonus, deductions, total_salary
      FROM `薪資`
      $whereSql
      ORDER BY name ASC, id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($bind);

    $out = fopen('php://output', 'w');
    fputcsv($out, ['員工ID','姓名','發薪月份','底薪','時薪','本月工時','獎金','扣款','實領']);
    while ($row = $stmt->fetch()) {
      fputcsv($out, [
        $row['user_id'], $row['name'], $row['salary_month'],
        $row['base_salary'], $row['hourly_rate'], $row['working_hours'],
        $row['bonus'], $row['deductions'], $row['total_salary']
      ]);
    }
    fclose($out);
    exit;
  } else {
    // 取總數
    $csql = "SELECT COUNT(*) FROM `薪資` $whereSql";
    $cstm = $pdo->prepare($csql);
    $cstm->execute($bind);
    $total = (int)$cstm->fetchColumn();

    // 取資料
    $sql = "
      SELECT 
        id AS user_id, name, salary_month, base_salary, hourly_rate, working_hours,
        bonus, deductions, total_salary
      FROM `薪資`
      $whereSql
      ORDER BY name ASC, id ASC
      LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($bind as $k=>$v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll();

    json(['data'=>$data, 'total'=>$total]);
  }
}

// POST /salaries/recalculate  （此處先提供 stub）
if ($path === '/salaries/recalculate' && $method === 'POST') {
  // 真正計算：去彙整「打卡紀錄」「日報表」…（目前先回成功）
  json(['ok'=>true, 'message'=>'recalculate queued (stub)']);
}

// /salaries/{id}  GET 取單筆、PUT 更新
if (preg_match('#^/salaries/([^/]+)$#', $path, $m)) {
  $id = $m[1];

  if ($method === 'GET') {
    $month = isset($_GET['month']) ? month_to_int($_GET['month']) : (int)date('Ym');
    $sql = "
      SELECT id AS user_id, name, salary_month, base_salary, hourly_rate, working_hours,
             bonus, deductions, total_salary
      FROM `薪資`
      WHERE id = :id AND salary_month = :m
      LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id'=>$id, ':m'=>$month]);
    $row = $stmt->fetch();
    if (!$row) bad('record not found', 404);
    json(['salary'=>$row]);
  }

  if ($method === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $month = month_to_int($body['salary_month'] ?? null);
    $base  = isset($body['base_salary']) ? (int)$body['base_salary'] : 0;
    $rate  = array_key_exists('hourly_rate', $body) ? ($body['hourly_rate']===null ? null : (int)$body['hourly_rate']) : null;
    $bonus = (int)($body['bonus'] ?? 0);
    $ded   = (int)($body['deductions'] ?? 0);

    // 先抓一筆（看是否存在 & 取 working_hours 用來算總額）
    $s = $pdo->prepare("SELECT name, working_hours FROM `薪資` WHERE id=:id AND salary_month=:m LIMIT 1");
    $s->execute([':id'=>$id, ':m'=>$month]);
    $exist = $s->fetch();

    $working_hours = (float)($exist['working_hours'] ?? 0);
    $calcBase = ($rate !== null) ? (int)round($rate * $working_hours) : $base;
    $total = $calcBase + $bonus - $ded;

    if ($exist) {
      $u = $pdo->prepare("
        UPDATE `薪資`
           SET base_salary = :base,
               hourly_rate = :rate,
               bonus = :bonus,
               deductions = :ded,
               total_salary = :total
         WHERE id = :id AND salary_month = :m
      ");
      $u->execute([
        ':base'=>$base, ':rate'=>$rate, ':bonus'=>$bonus, ':ded'=>$ded,
        ':total'=>$total, ':id'=>$id, ':m'=>$month
      ]);
    } else {
      // 如果沒有就插入一筆（name 先用 id 當佔位或你可以改成去 `員工基本資料` 撈）
      $ins = $pdo->prepare("
        INSERT INTO `薪資`
          (id, name, salary_month, base_salary, hourly_rate, working_hours, bonus, deductions, total_salary)
        VALUES
          (:id, :name, :m, :base, :rate, :hrs, :bonus, :ded, :total)
      ");
      $ins->execute([
        ':id'=>$id, ':name'=>$id, ':m'=>$month,
        ':base'=>$base, ':rate'=>$rate, ':hrs'=>$working_hours,
        ':bonus'=>$bonus, ':ded'=>$ded, ':total'=>$total
      ]);
    }

    // 回傳最新資料
    $stmt = $pdo->prepare("
      SELECT id AS user_id, name, salary_month, base_salary, hourly_rate, working_hours,
             bonus, deductions, total_salary
      FROM `薪資`
      WHERE id=:id AND salary_month=:m
    ");
    $stmt->execute([':id'=>$id, ':m'=>$month]);
    json(['salary'=>$stmt->fetch()]);
  }

  bad('method not allowed', 405);
}

bad('not found', 404);

/* ==================== 庫存管理 API（對應欄位：id, item_id, quantity, last_update, updated_by） ==================== */

const TABLE_STOCK = '`庫存`'; // ← 如果實際表名不同，改這裡

// 工具：把 last_update 變成人看的字串（支援 UNIX秒 或 DATETIME字串）
function _human_time($v){
  if ($v === null || $v === '') return null;
  return ctype_digit((string)$v) ? date('Y-m-d H:i:s', (int)$v) : (string)$v;
}

// GET /inventory   查詢清單（q 可搜 id 或 item_id；分頁）
// GET /inventory/export  匯出 CSV
if (preg_match('#^/inventory(?:/export)?$#', $path)) {
  $isExport = str_ends_with($path, '/export');

  $q      = trim($_GET['q'] ?? '');
  $page   = max(1, (int)($_GET['page'] ?? 1));
  $limit  = min(200, max(1, (int)($_GET['limit'] ?? 20)));
  $offset = ($page-1)*$limit;

  $where = ['1=1'];
  $bind  = [];
  if ($q !== '') {
    $where[]   = '(id LIKE :kw OR item_id LIKE :kw)';
    $bind[':kw'] = "%{$q}%";
  }
  $whereSql = 'WHERE '.implode(' AND ', $where);

  if ($isExport) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventory.csv"');
    echo "\xEF\xBB\xBF"; // Excel用BOM

    $sql = "SELECT id,item_id,quantity,last_update,updated_by
              FROM ".TABLE_STOCK." $whereSql
             ORDER BY item_id ASC, id ASC";
    $st = $pdo->prepare($sql);
    $st->execute($bind);

    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','item_id','quantity','last_update','last_update_at','updated_by']);
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
      fputcsv($out, [
        $r['id'], $r['item_id'], $r['quantity'], $r['last_update'],
        _human_time($r['last_update']), $r['updated_by']
      ]);
    }
    fclose($out);
    exit;
  } else {
    // total
    $csql = "SELECT COUNT(*) FROM ".TABLE_STOCK." $whereSql";
    $cst  = $pdo->prepare($csql);
    $cst->execute($bind);
    $total = (int)$cst->fetchColumn();

    // rows
    $sql = "SELECT id,item_id,quantity,last_update,updated_by
              FROM ".TABLE_STOCK." $whereSql
             ORDER BY item_id ASC, id ASC
             LIMIT :limit OFFSET :offset";
    $st = $pdo->prepare($sql);
    foreach ($bind as $k=>$v) $st->bindValue($k, $v);
    $st->bindValue(':limit', $limit, PDO::PARAM_INT);
    $st->bindValue(':offset', $offset, PDO::PARAM_INT);
    $st->execute();

    $rows = [];
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
      $r['last_update_at'] = _human_time($r['last_update']);
      $rows[] = $r;
    }
    json(['data'=>$rows, 'total'=>$total]);
  }
}

// GET /inventory/{id}  取單筆
// PUT /inventory/{id}  直接覆寫（quantity / updated_by），同時更新 last_update
if (preg_match('#^/inventory/(\d+)$#', $path, $m)) {
  $pk = (int)$m[1];

  if ($method === 'GET') {
    $st = $pdo->prepare("SELECT id,item_id,quantity,last_update,updated_by FROM ".TABLE_STOCK." WHERE id=:id");
    $st->execute([':id'=>$pk]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if (!$r) bad('item not found', 404);
    $r['last_update_at'] = _human_time($r['last_update']);
    json(['item'=>$r]);
  }

  if ($method === 'PUT') {
    $b = json_decode(file_get_contents('php://input'), true) ?? [];
    $qty = isset($b['quantity']) ? (int)$b['quantity'] : null;
    $upd = isset($b['updated_by']) ? (int)$b['updated_by'] : null;

    if ($qty === null) bad('quantity required', 422);
    // 若 last_update 是 DATETIME，改成：..., last_update = NOW()
    $now = time();
    $u = $pdo->prepare("
      UPDATE ".TABLE_STOCK."
         SET quantity=:q, updated_by=:u, last_update=:lu
       WHERE id=:id
    ");
    $u->execute([':q'=>$qty, ':u'=>$upd, ':lu'=>$now, ':id'=>$pk]);

    $st = $pdo->prepare("SELECT id,item_id,quantity,last_update,updated_by FROM ".TABLE_STOCK." WHERE id=:id");
    $st->execute([':id'=>$pk]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    $r['last_update_at'] = _human_time($r['last_update']);
    json(['item'=>$r]);
  }

  bad('method not allowed', 405);
}

// POST /inventory/adjust   數量增減（delta 可正可負）
// body: { item_id: 123, delta: -3, updated_by: 9 }
if ($path === '/inventory/adjust' && $method === 'POST') {
  $b = json_decode(file_get_contents('php://input'), true) ?? [];
  $itemId = $b['item_id']   ?? null;
  $delta  = (int)($b['delta'] ?? 0);
  $updBy  = isset($b['updated_by']) ? (int)$b['updated_by'] : null;
  if ($itemId === null) bad('item_id required', 422);
  if ($delta === 0)     bad('delta must be non-zero', 422);

  $pdo->beginTransaction();
  try {
    // 先查現況
    $st = $pdo->prepare("SELECT id,quantity,last_update,updated_by FROM ".TABLE_STOCK." WHERE item_id=:iid FOR UPDATE");
    $st->execute([':iid'=>$itemId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    $now = time(); // 若改為 DATETIME 欄位，改用 SQL: last_update = NOW()

    if ($row) {
      $newQty = max(0, (int)$row['quantity'] + $delta);
      $u = $pdo->prepare("
        UPDATE ".TABLE_STOCK."
           SET quantity=:q, updated_by=:u, last_update=:lu
         WHERE id=:id
      ");
      $u->execute([':q'=>$newQty, ':u'=>$updBy, ':lu'=>$now, ':id'=>$row['id']]);
      $id = (int)$row['id'];
    } else {
      $newQty = max(0, $delta);
      $i = $pdo->prepare("
        INSERT INTO ".TABLE_STOCK." (item_id,quantity,last_update,updated_by)
        VALUES (:iid,:q,:lu,:u)
      ");
      $i->execute([':iid'=>$itemId, ':q'=>$newQty, ':lu'=>$now, ':u'=>$updBy]);
      $id = (int)$pdo->lastInsertId();
    }

    $g = $pdo->prepare("SELECT id,item_id,quantity,last_update,updated_by FROM ".TABLE_STOCK." WHERE id=:id");
    $g->execute([':id'=>$id]);
    $r = $g->fetch(PDO::FETCH_ASSOC);
    $pdo->commit();

    $r['last_update_at'] = _human_time($r['last_update']);
    json(['item'=>$r]);
  } catch (Throwable $e) {
    $pdo->rollBack();
    bad('adjust failed: '.$e->getMessage(), 500);
  }
}

