<?php
// 產出「每品項」彙總庫存，提供庫存查詢頁
// 回傳：id(品項ID) / name / category / unit / quantity(加總) / last_update_iso(最近一筆) / updated_by(最近一筆)
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';

try {
  $pdo = pdo();

  // 以品項為單位，先把「加總數量」以及「最近一筆 id」求出來
  $sumSql = "
    SELECT
      i.item_id,
      SUM(i.quantity) AS qty,
      MAX(i.id)       AS last_id
    FROM `庫存管理` AS i
    GROUP BY i.item_id
  ";
  $sum = $pdo->query($sumSql)->fetchAll(PDO::FETCH_ASSOC);

  if (!$sum) { echo json_encode([], JSON_UNESCAPED_UNICODE); exit; }

  // 把 item_id 收集起來
  $ids = array_map(fn($r) => (int)$r['item_id'], $sum);
  $in  = implode(',', array_fill(0, count($ids), '?'));

  // 取各品項資料
  $prodSql = "
    SELECT p.id, p.name, p.unit, p.category_id, c.name AS category
    FROM `庫存商品` AS p
    LEFT JOIN `商品分類` AS c ON c.id = p.category_id
    WHERE p.id IN ($in)
  ";
  $ps = $pdo->prepare($prodSql);
  $ps->execute($ids);
  $prod = [];
  while ($r = $ps->fetch(PDO::FETCH_ASSOC)) { $prod[(int)$r['id']] = $r; }

  // 取每個品項「最後一筆異動」（用 MAX(id) 當最近）
  $lastIds = array_map(fn($r) => (int)$r['last_id'], $sum);
  $inLast  = implode(',', array_fill(0, count($lastIds), '?'));
  $lastSql = "
    SELECT id, item_id, last_update, updated_by
    FROM `庫存管理`
    WHERE id IN ($inLast)
  ";
  $ls = $pdo->prepare($lastSql);
  $ls->execute($lastIds);
  $last = [];
  while ($r = $ls->fetch(PDO::FETCH_ASSOC)) { $last[(int)$r['item_id']] = $r; }

  // 組結果
  $out = [];
  foreach ($sum as $row) {
    $iid = (int)$row['item_id'];
    $p   = $prod[$iid] ?? ['id'=>$iid,'name'=>null,'unit'=>null,'category'=>null];
    $lv  = $last[$iid] ?? null;

    $iso = null; $by = null;
    if ($lv) {
      $iso = $lv['last_update'] ? date('Y-m-d H:i:s', strtotime($lv['last_update'])) : null;
      $by  = $lv['updated_by'] ?? null;
    }

    $out[] = [
      'id'             => $p['id'],
      'name'           => $p['name'],
      'category'       => $p['category'],
      'unit'           => $p['unit'],
      'quantity'       => (int)$row['qty'],
      'last_update_iso'=> $iso,
      'updated_by'     => $by,
    ];
  }

  // 依品項 id 排序（你也可改 name ASC）
  usort($out, fn($a,$b)=> ($a['id'] <=> $b['id']));
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'product_stock_list failed','detail'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
