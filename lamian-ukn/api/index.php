<?php
// /lamian-ukn/api/index.php
// 統一 JSON 預設（匯出 CSV 的地方會覆蓋 header）
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';
$pdo = pdo(); // <--- 重要：先建立 PDO 連線

/* ---------------------- 基礎工具 ---------------------- */
function json($data, int $code=200){
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
function bad($msg, int $code=400){ json(['error'=>$msg], $code); }

/** 將 2025-10 / 202510 / 2025/10 轉為整數 202510 */
function month_to_int($m){
  if ($m === null || $m === '') return (int)date('Ym');
  $s = preg_replace('/[^\d]/', '', (string)$m);
  return (int)substr($s, 0, 6);
}

/* ---------------------- 路徑解析 ---------------------- */
$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pos  = strpos($uri, '/api');
$path = $pos !== false ? substr($uri, $pos + 4) : '/';
$method = $_SERVER['REQUEST_METHOD'];

/* ---------------------- 根路由 ---------------------- */
if ($path === '/' || $path === '') {
  json(['ok'=>true, 'service'=>'lamian-ukn api']);
}

/* ======================= 薪資管理 ======================= */
/* GET /salaries            清單（支援 month, q, page, limit）
   GET /salaries/export     匯出 CSV                         */
if (preg_match('#^/salaries(?:/export)?$#', $path)) {
  $isExport = str_ends_with($path, '/export');
  $month = isset($_GET['month']) ? month_to_int($_GET['month']) : (int)date('Ym');
  $q     = trim($_GET['q'] ?? '');
  $page  = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(100, max(1, (int)($_GET['limit'] ?? 10)));
  $offset= ($page-1)*$limit;

  $where = ['salary_month = :m'];
  $bind  = [':m' => $month];
  if ($q !== '') {
    $where[] = '(id LIKE :kw OR name LIKE :kw)';
    $bind[':kw'] = "%{$q}%";
  }
  $whereSql = 'WHERE '.implode(' AND ', $where);

  if ($isExport) {
    header('Content-Type: text/csv; charset=utf-8');
    $fn = "薪資管理_{$month}.csv";
    header('Content-Disposition: attachment; filename="'.$fn.'"');
    echo "\xEF\xBB\xBF"; // BOM for Excel

    $sql = "
      SELECT id AS user_id, name, salary_month, base_salary, hourly_rate, working_hours,
             bonus, deductions, total_salary
        FROM `薪資`
       $whereSql
       ORDER BY name ASC, id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($bind);

    $out = fopen('php://output', 'w');
    fputcsv($out, ['員工ID','姓名','發薪月份','底薪','時薪','本月工時','獎金','扣款','實領']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      fputcsv($out, [
        $row['user_id'], $row['name'], $row['salary_month'],
        $row['base_salary'], $row['hourly_rate'], $row['working_hours'],
        $row['bonus'], $row['deductions'], $row['total_salary']
      ]);
    }
    fclose($out);
    exit;
  } else {
    $csql = "SELECT COUNT(*) FROM `薪資` $whereSql";
    $cstm = $pdo->prepare($csql);
    $cstm->execute($bind);
    $total = (int)$cstm->fetchColumn();

    $sql = "
      SELECT id AS user_id, name, salary_month, base_salary, hourly_rate, working_hours,
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
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json(['data'=>$data, 'total'=>$total]);
  }
}

/* POST /salaries/recalculate  （目前 stub） */
if ($path === '/salaries/recalculate' && $method === 'POST') {
  json(['ok'=>true, 'message'=>'recalculate queued (stub)']);
}

/* /salaries/{id}  GET 取單筆、PUT 更新 */
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
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) bad('record not found', 404);
    json(['salary'=>$row]);
  }

  if ($method === 'PUT') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $month = month_to_int($body['salary_month'] ?? null);
    $base  = isset($body['base_salary']) ? (int)$body['base_salary'] : 0;
    $rate  = array_key_exists('hourly_rate', $body)
              ? ($body['hourly_rate'] === null ? null : (int)$body['hourly_rate'])
              : null;
    $bonus = (int)($body['bonus'] ?? 0);
    $ded   = (int)($body['deductions'] ?? 0);

    $s = $pdo->prepare("SELECT name, working_hours FROM `薪資` WHERE id=:id AND salary_month=:m LIMIT 1");
    $s->execute([':id'=>$id, ':m'=>$month]);
    $exist = $s->fetch(PDO::FETCH_ASSOC);

    $working_hours = (float)($exist['working_hours'] ?? 0);
    $calcBase = ($rate !== null) ? (int)round($rate * $working_hours) : $base;
    $total = $calcBase + $bonus - $ded;

    if ($exist) {
      $u = $pdo->prepare("
        UPDATE `薪資`
           SET base_salary=:base, hourly_rate=:rate,
               bonus=:bonus, deductions=:ded, total_salary=:total
         WHERE id=:id AND salary_month=:m
      ");
      $u->execute([
        ':base'=>$base, ':rate'=>$rate, ':bonus'=>$bonus, ':ded'=>$ded,
        ':total'=>$total, ':id'=>$id, ':m'=>$month
      ]);
    } else {
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

    $stmt = $pdo->prepare("
      SELECT id AS user_id, name, salary_month, base_salary, hourly_rate, working_hours,
             bonus, deductions, total_salary
        FROM `薪資`
       WHERE id=:id AND salary_month=:m
    ");
    $stmt->execute([':id'=>$id, ':m'=>$month]);
    json(['salary'=>$stmt->fetch(PDO::FETCH_ASSOC)]);
  }

  bad('method not allowed', 405);
}

/* ======================= 庫存管理 ======================= */
/* 重要：這裡用你目前實際表名 */
const TABLE_STOCK = '`庫存管理`';  // <--- 若表名不同，改這一行即可

/** 把 last_update 轉成好看的字串（支援 UNIX秒 或 DATETIME字串） */
function _human_time($v){
  if ($v === null || $v === '') return null;
  return ctype_digit((string)$v) ? date('Y-m-d H:i:s', (int)$v) : (string)$v;
}

/* GET /inventory             查詢清單（q 可搜 id / item_id；分頁）
   GET /inventory/export      匯出 CSV                                   */
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
    echo "\xEF\xBB\xBF"; // Excel BOM

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
    $csql = "SELECT COUNT(*) FROM ".TABLE_STOCK." $whereSql";
    $cst  = $pdo->prepare($csql);
    $cst->execute($bind);
    $total = (int)$cst->fetchColumn();

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

/* GET /inventory/{id}   取單筆
   PUT /inventory/{id}   覆寫 quantity / updated_by，並更新 last_update       */
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

    $now = time(); // 若欄位是 DATETIME，將下面 SQL 改為 last_update = NOW()
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

/* POST /inventory/adjust   數量增減（delta 可正可負）
   body: { item_id: 123, delta: -3, updated_by: 9 }                         */
if ($path === '/inventory/adjust' && $method === 'POST') {
  $b = json_decode(file_get_contents('php://input'), true) ?? [];
  $itemId = $b['item_id']   ?? null;
  $delta  = (int)($b['delta'] ?? 0);
  $updBy  = isset($b['updated_by']) ? (int)$b['updated_by'] : null;
  if ($itemId === null) bad('item_id required', 422);
  if ($delta === 0)     bad('delta must be non-zero', 422);

  $pdo->beginTransaction();
  try {
    $st = $pdo->prepare("SELECT id,quantity,last_update,updated_by FROM ".TABLE_STOCK." WHERE item_id=:iid FOR UPDATE");
    $st->execute([':iid'=>$itemId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    $now = time(); // 若欄位是 DATETIME，改成 SQL: last_update = NOW()

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

/* ---------------------- 最終 404（一定要在所有路由之後） ---------------------- */
bad('not found', 404);
