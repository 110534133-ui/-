<?php
// 1. 引入唯一的設定檔和權限檢查
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../api/config.php';

// (舊的 header, ini_set, error_reporting, new PDO... 皆已刪除)

try {
    // 2. 🚨【安全修補】
    // 這是給 A/B 級儀表板看的營收資料，必須限制 A/B 級才能訪問
    if (!check_user_level(['A', 'B'], false)) {
        err('權限不足 (僅限 A/B 級)', 403);
    }

    // 3. 透過 config.php 的 pdo() 函數取得連線
    $pdo = pdo();

    $dayNames = ['日','一','二','三','四','五','六'];
    
    // ==========================================================
    // 🔥【新增邏輯】: 優先處理 charts.php 傳來的 start_date 和 end_date
    // ==========================================================
    if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
        $start = $_GET['start_date'];
        $end   = $_GET['end_date'];

        // 簡易驗證
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            err('日期格式錯誤，請用 YYYY-MM-DD', 400);
        }
        if (strtotime($start) > strtotime($end)) {
            err('開始日期不能晚於結束日期', 400);
        }

        // SQL 查詢 (與下面邏輯相同)
        $sql = "
            SELECT DATE(report_date) AS d,
                   SUM(total_income)  AS total_income,
                   SUM(total_expense) AS total_expense
            FROM daily_report
            WHERE DATE(report_date) BETWEEN :s AND :e
            GROUP BY DATE(report_date)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':s'=>$start, ':e'=>$end]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 將 $rows 轉為 map 方便查找
        $map = [];
        foreach ($rows as $r) { $map[$r['d']] = $r; }

        // 逐日補 0 (確保圖表連續)
        $result = [];
        $currentDate = new DateTime($start);
        $endDate = new DateTime($end);

        while ($currentDate <= $endDate) {
            $d = $currentDate->format('Y-m-d');
            $found = $map[$d] ?? null; // 從 map 查找

            $result[] = [
                'report_date'   => $d,
                'weekday'       => '星期'.$dayNames[(int)$currentDate->format('w')],
                'total_income'  => (int)($found['total_income']  ?? 0),
                'total_expense' => (int)($found['total_expense'] ?? 0),
            ];
            $currentDate->modify('+1 day'); // 增加一天
        }
        
        ok(['success'=>true,'data'=>$result]);
        exit; // 完成請求，退出
    }
    // ==========================================================
    // 🔥【新增邏輯結束】
    // ==========================================================


    // ---------- A) 指定月份 (邏輯不變) ----------
    if (isset($_GET['month'])) {
        $month = $_GET['month']; // 2025-08
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            // 5. 使用 err() 回傳錯誤
            err('月份格式錯誤，請用 YYYY-MM', 400);
        }

        $start = $month.'-01';
        $end   = date('Y-m-t', strtotime($start));

        // SQL 邏輯不變
        $sql = "
            SELECT DATE(report_date) AS d,
                   SUM(total_income)  AS total_income,
                   SUM(total_expense) AS total_expense
            FROM daily_report
            WHERE DATE(report_date) BETWEEN :s AND :e
            GROUP BY DATE(report_date)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':s'=>$start, ':e'=>$end]);
        $rows = $stmt->fetchAll();

        // 逐日補 0 (邏輯不變)
        $result = [];
        $daysInMonth = (int)date('t', strtotime($start));
        for ($i=1; $i <= $daysInMonth; $i++) {
            $d = sprintf('%s-%02d', $month, $i);
            $found = null;
            if ($rows) {
                foreach ($rows as $r) { if ($r['d'] === $d) { $found = $r; break; } }
            }
            $result[] = [
                'report_date'   => $d,
                'weekday'       => '星期'.$dayNames[(int)date('w', strtotime($d))],
                'total_income'  => (int)($found['total_income']  ?? 0),
                'total_expense' => (int)($found['total_expense'] ?? 0),
            ];
        }
        
        // 6. 使用 ok() 回傳成功
        ok(['success'=>true,'data'=>$result]);
    }

    // ---------- B) 預設：過去七日 (邏輯不變, index.php 會用到) ----------
    $dates = [];
    for ($i=6; $i>=0; $i--) { $dates[] = date('Y-m-d', strtotime("-{$i} days")); }
    $start = $dates[0]; $end = $dates[6];

    $sql = "
        SELECT DATE(report_date) AS d,
               SUM(total_income)  AS total_income,
               SUM(total_expense) AS total_expense
        FROM daily_report
        WHERE DATE(report_date) BETWEEN :s AND :e
        GROUP BY DATE(report_date)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':s'=>$start, ':e'=>$end]);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $r) { $map[$r['d']] = $r; }

    $out = [];
    foreach ($dates as $d) {
        $out[] = [
            'report_date'   => $d,
            'weekday'       => '星期'.$dayNames[(int)date('w', strtotime($d))],
            'total_income'  => (int)($map[$d]['total_income']  ?? 0),
            'total_expense' => (int)($map[$d]['total_expense'] ?? 0),
        ];
    }
    
    // 6. 使用 ok() 回傳成功
    ok(['success'=>true,'data'=>$out]);

} catch(PDOException $e) {
    // 7. 使用 err() 回傳錯誤
    err($e->getMessage(), 500);
}
?>