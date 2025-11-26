<?php
// 🔥 修正版：api_report_check.php
header('Content-Type: application/json; charset=utf-8');

// 🔥 修正：引入標準設定檔和權限檢查
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/config.php'; // 裡面有 pdo(), ok(), err()

// 關閉 PHP 自身的錯誤顯示，統一由 try-catch 處理
ini_set('display_errors', '0');
error_reporting(E_ALL);

try {
    // 🔥 修正：檢查權限 (A 或 B 級)，且 $redirect = false (API模式)
    if (!check_user_level(['A', 'B'], false)) {
        err('權限不足 (僅限 A/B 級)', 403);
    }
    
    // 🔥 修正：使用標準 pdo() 連線
    $pdo = pdo();

    // ===== 取得前端 JSON =====
    $raw = file_get_contents("php://input");
    if ($raw === false || trim($raw) === '') {
        err('未接收到任何資料');
    }
    $data = json_decode($raw, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        err('無效的 JSON：' . json_last_error_msg());
    }

    /* -------------------------
       ✅ 日報表檢查（report_date）
       ------------------------- */
    if (isset($data['report_date'])) {
        // ... (檢查邏輯不變) ...
        $required = ['report_date', 'weekday', 'filled_by'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                err("欄位 {$field} 為必填");
            }
        }
        $d = DateTime::createFromFormat('Y-m-d', $data['report_date']);
        $validDate = $d && $d->format('Y-m-d') === $data['report_date'];
        if (!$validDate) {
            err('日期格式錯誤（需為 YYYY-MM-DD）');
        }
        $sql = "SELECT COUNT(*) AS cnt FROM daily_report WHERE report_date = :report_date";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":report_date", $data['report_date']);
        $stmt->execute();
        $result = $stmt->fetch();
        if ($result && $result['cnt'] > 0) {
            err('此日期已填寫過日報表');
        }
        ok(['success' => true]); // 通過
    }

    /* -------------------------
       ✅ 租金日期檢查（rent_start, rent_end）
       ------------------------- */
    if (isset($data['rent_start']) && isset($data['rent_end'])) {
        // ... (檢查邏輯不變) ...
        $rent_start = trim($data['rent_start']);
        $rent_end = trim($data['rent_end']);
        $ds = DateTime::createFromFormat('Y-m-d', $rent_start);
        $de = DateTime::createFromFormat('Y-m-d', $rent_end);
        $okDs = $ds && $ds->format('Y-m-d') === $rent_start;
        $okDe = $de && $de->format('Y-m-d') === $rent_end;
        if (!$okDs || !$okDe) {
            err('租金日期格式錯誤（YYYY-MM-DD）');
        }
        if ($ds > $de) {
            err('租金起始日不能晚於結束日');
        }
        $sql = "SELECT COUNT(*) AS cnt 
                FROM rent_setting 
                WHERE NOT (:rent_end < rent_start OR :rent_start > rent_end)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':rent_start', $rent_start);
        $stmt->bindValue(':rent_end', $rent_end);
        $stmt->execute();
        $rentOverlap = $stmt->fetch();
        if ($rentOverlap && $rentOverlap['cnt'] > 0) {
            err('租金日期重複');
        }
        ok(['success' => true]); // 通過
    }

    /* -------------------------
       ✅ 水電瓦斯月份檢查（utilities_month）
       ------------------------- */
    if (isset($data['utilities_month'])) {
        // ... (檢查邏輯不變) ...
        $utilities_month = trim($data['utilities_month']);
        if ($utilities_month === '') {
            err('水電瓦斯月份不得為空');
        }
        $currentYear = date('Y');
        $sql = "SELECT COUNT(*) AS cnt 
                FROM daily_report 
                WHERE YEAR(report_date) = :year 
                  AND utilities_month = :utilities_month";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':year', $currentYear);
        $stmt->bindValue(':utilities_month', $utilities_month);
        $stmt->execute();
        $result = $stmt->fetch();
        if ($result && $result['cnt'] > 0) {
            err("今年已存在 {$utilities_month} 的水電瓦斯資料");
        }
        ok(['success' => true]); // 通過
    }

    // 若無符合檢查類別
    err('請提供有效的檢查項目');
    
} catch (Throwable $e) {
    // 🔥 修正：使用標準 err() 函數
    error_log("api_report_check.php Error: " . $e->getMessage());
    err('API 內部錯誤', 500, ['detail' => $e->getMessage()]);
}
?>