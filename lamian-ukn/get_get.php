<?php
// 1. 引入唯一的設定檔和權限檢查
// 【路徑已修改】

// 從 /api 往上層到 / 根目錄，再進入 /includes
require_once __DIR__ . '/../includes/auth_check.php'; 

// config.php 就在 /api 同一層，所以直接引用
require_once __DIR__ . '/config.php'; 

// (舊的 header, ini_set, error_reporting, new PDO... 皆已刪除)

try {
    // 2. 🚨【安全修補】
    // (安全檢查邏輯不變)
    if (!check_user_level('A', false)) {
        err('權限不足 (僅限 A 級)', 403);
    }
    
    // 3. 透過 config.php 的 pdo() 函數取得連線
    $pdo = pdo();

    // 4. (您原有的篩選、分頁、排序邏輯，全部保留)
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM daily_report WHERE id = :id");
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            ok(['success'=>true, 'data'=>$row]); // 使用 ok()
        } else {
            err('找不到資料', 404); // 使用 err()
        }
    }

    $start_date = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? trim($_GET['start_date']) : null;
    $end_date = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? trim($_GET['end_date']) : null;
    $filled_by = isset($_GET['filled_by']) && $_GET['filled_by'] !== '' ? trim($_GET['filled_by']) : null;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 15;
    $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'report_date';
    $sort_dir = isset($_GET['sort_dir']) && strtolower($_GET['sort_dir']) === 'asc' ? 'ASC' : 'DESC';

    $allowedSort = ['report_date', 'total_income', 'total_expense', 'filled_by', 'id'];
    if (!in_array($sort_by, $allowedSort)) {
        $sort_by = 'report_date';
    }

    $wheres = [];
    $params = [];
    if ($start_date) {
        $wheres[] = "report_date >= :start_date";
        $params['start_date'] = $start_date;
    }
    if ($end_date) {
        $wheres[] = "report_date <= :end_date";
        $params['end_date'] = $end_date . " 23:59:59";
    }
    if ($filled_by) {
        $wheres[] = "TRIM(filled_by) = :filled_by";
        $params['filled_by'] = trim($filled_by);
    }
    $whereSql = count($wheres) ? 'WHERE ' . implode(' AND ', $wheres) : '';

    // (您原有的 SQL 邏輯，全部保留)
    $countSql = "SELECT COUNT(*) AS cnt FROM daily_report {$whereSql}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total_records = (int)$countStmt->fetchColumn();

    if ($per_page <= 0) {
        $total_pages = $total_records > 0 ? 1 : 0;
        $offset = 0;
    } else {
        $total_pages = $total_records > 0 ? ceil($total_records / $per_page) : 1;
        $offset = ($page - 1) * $per_page;
    }

    $dataSql = "SELECT * FROM daily_report {$whereSql} ORDER BY {$sort_by} {$sort_dir}";
    if ($per_page > 0) {
        $dataSql .= " LIMIT :limit OFFSET :offset";
    }
    $stmt = $pdo->prepare($dataSql);
    foreach ($params as $k => $v) {
        $stmt->bindValue(":{$k}", $v);
    }
    if ($per_page > 0) {
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $totals = [
        'total_income_sum' => 0,
        'total_expense_sum' => 0,
        'net_income' => 0,
        'total_records' => $total_records
    ];
    
    $allSql = "SELECT * FROM daily_report {$whereSql} ORDER BY report_date DESC";
    $allStmt = $pdo->prepare($allSql);
    foreach ($params as $k => $v) {
        $allStmt->bindValue(":{$k}", $v);
    }
    $allStmt->execute();
    $allRows = $allStmt->fetchAll();

    foreach ($allRows as $r) {
        $totals['total_income_sum'] += isset($r['total_income']) ? floatval($r['total_income']) : 0.0;
        $totals['total_expense_sum'] += isset($r['total_expense']) ? floatval($r['total_expense']) : 0.0;
    }
    $totals['net_income'] = $totals['total_income_sum'] - $totals['total_expense_sum'];
    
    $filled_by_options = [];
    $fbSql = "SELECT DISTINCT filled_by FROM daily_report {$whereSql} ORDER BY filled_by ASC";
    $fbStmt = $pdo->prepare($fbSql);
    foreach ($params as $k => $v) {
        $fbStmt->bindValue(":{$k}", $v);
    }
    $fbStmt->execute();
    $fbRows = $fbStmt->fetchAll();
    foreach ($fbRows as $r) {
        if (isset($r['filled_by'])) $filled_by_options[] = $r['filled_by'];
    }

    // 5. 使用 config.php 的 ok() 函數回傳
    ok([
        'success' => true,
        'data' => $rows,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => $total_pages,
        'total_records' => $total_records,
        'totals' => $totals,
        'filled_by_options' => $filled_by_options
    ]);

} catch (Exception $e) {
    // 6. 使用 config.php 的 err() 函數回傳
    err('查詢失敗: ' . $e->getMessage(), 500);
}
?>