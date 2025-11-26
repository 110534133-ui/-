<?php
// /lamian-ukn/api_get_employees.php
// 目的：提供一個 JSON 格式的員工清單 (ID 和 Name)
// 給「班表管理」頁面的下拉選單使用

header('Content-Type: application/json; charset=utf-8');

// 參照你其他檔案的連線設定
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=lamian;charset=utf8mb4',
        'root', '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // 抓取所有員工的 ID 和 Name，按姓名排序
    $stmt = $pdo->query("
        SELECT id, name 
        FROM 員工基本資料
        ORDER BY name
    ");
    
    $employees = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $employees]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '無法載入員工清單：' . $e->getMessage()]);
}
?>