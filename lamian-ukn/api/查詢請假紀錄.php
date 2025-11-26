<?php
// 🔥 修正版 v2：api/查詢請假紀錄.php
header('Content-Type: application/json; charset=utf-8');

// 引入標準設定檔和權限檢查
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/config.php'; // 包含 pdo(), ok(), err()

try {
    // 檢查權限 (A, B, C 級都可查詢自己的)
    if (!check_user_level(['A', 'B', 'C'], false)) {
        err('您尚未登入，無法查詢紀錄', 401);
    }

    // 取得當前登入者的資訊
    $user = get_user_info();
    $employeeName = $user['name'];

    if (empty($employeeName) || $employeeName === '訪客') {
         err('無法識別您的身分，請重新登入', 401);
    }

    // 使用標準 pdo() 連線
    $pdo = pdo();

    // SQL 查詢，JOIN 假別資料表，並使用登入者姓名
    $sql = "
        SELECT 
            t.name AS leave_type_name,
            l.start_date,
            l.end_date,
            l.reason,
            l.status
        FROM leave_system AS l
        JOIN 假別 AS t ON l.leave_type_id = t.id
        WHERE l.name = ?
        ORDER BY l.start_date DESC
    "; // 🔥 修正：移除了 'l.id DESC'
    
    // 使用 PDO 預備語法
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$employeeName]); // 綁定真實的員工姓名
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = [];

    // 格式化資料 (同您原檔)
    foreach ($rows as $row) {
        $data[] = [
            "type" => $row["leave_type_name"],
            "start" => $row["start_date"],
            "end" => $row["end_date"],
            "reason" => $row["reason"],
            "status" => $row["status"]
        ];
    }
    
    // 使用 ok() 回應
    ok($data);

} catch (Throwable $e) {
    // 使用標準 err() 函數
    error_log("查詢請假紀錄.php Error: " . $e->getMessage());
    err('API 內部錯誤', 500, ['detail' => $e->getMessage()]);
}
?>