<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';

function table_exists(PDO $pdo, string $t): bool {
  $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t LIMIT 1');
  $stmt->execute([':t'=>$t]); return (bool)$stmt->fetchColumn();
}
function column_exists(PDO $pdo, string $t, string $c): bool {
  $stmt = $pdo->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c LIMIT 1');
  $stmt->execute([':t'=>$t, ':c'=>$c]); return (bool)$stmt->fetchColumn();
}

$limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 20;

$join_sql = "LEFT JOIN `庫存商品` p ON p.id = i.item_id";
$select_category = "p.category AS category";
if (table_exists($pdo,'庫存商品') && column_exists($pdo,'庫存商品','category_id') && table_exists($pdo,'商品分類')) {
  $join_sql = "LEFT JOIN `庫存商品` p ON p.id = i.item_id LEFT JOIN `商品分類` c ON c.id = p.category_id";
  $select_category = "c.name AS category";
}

$sql = "SELECT i.id, i.item_id, i.quantity, i.updated_by, i.last_update,
               p.name, p.unit, $select_category
        FROM `庫存管理` i
        $join_sql
        ORDER BY i.id DESC
        LIMIT :lim";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

foreach ($rows as &$r) {
  if (isset($r['last_update'])) {
    $v = $r['last_update'];
    if (ctype_digit((string)$v)) { $ts=(int)$v; $r['last_update_iso'] = $ts?date('Y-m-d H:i:s',$ts):null; }
    else { $t=strtotime($v); $r['last_update_iso'] = $t?date('Y-m-d H:i:s',$t):$v; }
  }
}
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
