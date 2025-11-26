<?php
// /lamian-ukn/api/clock_employees.php
require __DIR__.'/config.php';

try {
    $pdo = pdo();
    
    $sql = "SELECT `id`, `name`, `position`, `role`
            FROM `員工基本資料`
            ORDER BY `name`";
    
    $stmt = $pdo->query($sql);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ok($employees);
    
} catch(Throwable $ex) {
    err('查詢員工失敗', 500, ['detail' => $ex->getMessage()]);
}