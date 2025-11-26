<?php
// ===== 薪資管理_api.php =====

// 🔥 整合：加入權限檢查 (!! 依據您的範本修改 !!)
// auth_check.php 會自動處理 session_start() 和基本登入檢查
require_once __DIR__ . '/includes/auth_check.php';

// [!! 新增 !!] 定義一個 API 專用的權限檢查函數
// 這將用於需要 A 級(老闆) 或 B 級(管理員) 的操作
function check_api_admin_auth() {
    if (!check_user_level('A', false) && !check_user_level('B', false)) {
        http_response_code(403); // 403 Forbidden
        echo json_encode(['success' => false, 'message' => '權限不足，無法執行此操作']);
        exit;
    }
}

// ===== 資料庫連線 (原有程式碼) =====
$db_host = '127.0.0.1';
$db_name = 'lamian';
$db_user = 'root';
$db_pass = '';
$charset = 'utf8mb4';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$charset}";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗：' . $e->getMessage()]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// ===== 工具：檢查 salary_month 是否已鎖定（當前日期 >= salary_month 的下一個月 01） =====
function is_salary_month_locked(string $salary_month): bool {
    // salary_month 格式預期為 "YYYY-MM"
    $dt = DateTime::createFromFormat('Y-m', $salary_month);
    if (!$dt) return false;
    // 下一個月第一天
    $dt->modify('+1 month')->modify('first day of this month')->setTime(0,0,0);
    $now = new DateTime('now');
    // 若今天已到或超過下一個月第一天，視為鎖定
    return $now >= $dt;
}

// ... (normalize_record 函數，回傳欄位使用 hours) ...
function normalize_record($row)
{
    $base_salary = (float)($row['base_salary'] ?? 0);
    $hourly_rate = (float)($row['hourly_rate'] ?? 0);
    $hours = (float)($row['hours'] ?? $row['working_hours'] ?? 0); // 支援兩種來源
    $bonus = (float)($row['bonus'] ?? 0);
    $deductions = (float)($row['deductions'] ?? 0);

    $salary_type = $base_salary > 0 ? '月薪' : '時薪';

    $total_salary = ($base_salary > 0)
        ? $base_salary + $bonus - $deductions
        : ($hourly_rate * $hours + $bonus - $deductions);

    return [
        'id' => $row['id'],
        'name' => $row['name'],
        'salary_month' => $row['salary_month'] ?? '',
        'salary_type' => $salary_type,
        'base_salary' => $base_salary,
        'hourly_rate' => $hourly_rate,
        'hours' => $hours, // 回傳統一欄位 hours 給前端
        'bonus' => $bonus,
        'deductions' => $deductions,
        'total_salary' => $total_salary,
    ];
}

try {

    // ===============================
    // 1️⃣ 取得薪資列表 (管理員) - fetch
    // ===============================
    if ($action === 'fetch') {
        check_api_admin_auth();

        $month = $input['month'] ?? date('Y-m');
        $keyword = trim($input['keyword'] ?? '');

        // 抓出「有工時」的員工 (該月)
        $sqlEmp = "
            SELECT e.id, e.name, e.base_salary, e.hourly_rate
            FROM `員工基本資料` e
            JOIN (
                SELECT user_id, SUM(hours) AS total_hours
                FROM `attendance`
                WHERE DATE_FORMAT(clock_in, '%Y-%m') = :month
                GROUP BY user_id
                HAVING total_hours > 0
            ) a ON e.id = a.user_id
        ";

        $params = ['month' => $month];

        // 若有輸入關鍵字 → 加在 WHERE
        if ($keyword !== '') {
            $sqlEmp .= " WHERE e.name LIKE :kw OR CAST(e.id AS CHAR) LIKE :kw ";
            $params['kw'] = "%$keyword%";
        }

        $sqlEmp .= " ORDER BY e.id ASC";

        $stmt = $pdo->prepare($sqlEmp);
        $stmt->execute($params);
        $employees = $stmt->fetchAll();

        $records = [];
        $locked = is_salary_month_locked($month);

        foreach ($employees as $emp) {
            $userId = $emp['id'];

            // 取得 attendance 的加總（即時值）
            $sqlHours = "SELECT COALESCE(SUM(hours),0) AS att_hours
                 FROM `attendance`
                 WHERE user_id = :uid AND DATE_FORMAT(clock_in,'%Y-%m') = :month";
            $stmtHours = $pdo->prepare($sqlHours);
            $stmtHours->execute(['uid'=>$userId,'month'=>$month]);
            $att_hours = (float)$stmtHours->fetchColumn();

            // 先檢查該月薪資是否存在
            $sqlSalary = "SELECT * FROM `薪資管理` WHERE id=:uid AND salary_month=:month LIMIT 1";
            $stmtSalary = $pdo->prepare($sqlSalary);
            $stmtSalary->execute(['uid'=>$userId,'month'=>$month]);
            $salary = $stmtSalary->fetch();

            if (!$salary) {
                // 薪資不存在 -> 建立一筆（使用 attendance 的工時）
                $sqlIns = "INSERT INTO `薪資管理`
                   (id, name, salary_month, base_salary, hourly_rate, working_hours, att_last_sum, bonus, deductions)
                   VALUES (:id,:name,:month,:base_salary,:hourly_rate,:working_hours,:att_last_sum,0,0)";
                $pdo->prepare($sqlIns)->execute([
                    'id'=>$userId,
                    'name'=>$emp['name'],
                    'month'=>$month,
                    'base_salary'=>$emp['base_salary'],
                    'hourly_rate'=>$emp['hourly_rate'],
                    'working_hours'=>$att_hours,
                    'att_last_sum'=>$att_hours
                ]);
                $salary = [
                    'id'=>$userId,
                    'name'=>$emp['name'],
                    'base_salary'=>$emp['base_salary'],
                    'hourly_rate'=>$emp['hourly_rate'],
                    'working_hours'=>$att_hours,
                    'bonus'=>0,
                    'deductions'=>0
                ];
                $hours_to_return = $att_hours;
            } else {
                // 若已存在：
                if ($locked) {
                    // 已鎖定 -> 以薪資表的 working_hours 為準（不更新）
                    $hours_to_return = (float)($salary['working_hours'] ?? 0);
                } else {
                    // 未鎖定 -> 以 attendance 的加總為準，並更新薪資表的 working_hours（保持即時）
                    // 但若有 hours_manual（手動紀錄）需以手動紀錄為基礎再加上新增 attendance 差值（若有）
                    $db_att_last_sum = (float)($salary['att_last_sum'] ?? 0);
                    $new_att_sum = $att_hours;

                    // 若有 hours_manual（代表曾被手動改過），則以 hours_manual 為基底
                    $hours_manual = isset($salary['hours_manual']) && $salary['hours_manual'] !== null
                        ? (float)$salary['hours_manual'] : null;

                    if ($hours_manual !== null) {
                        // 計算 attendance 新增差值（基於 att_last_sum）
                        $delta = $new_att_sum - $db_att_last_sum;
                        if ($delta > 0) {
                            $updated_wh = $hours_manual + $delta;
                        } else {
                            $updated_wh = $hours_manual;
                        }
                        // 更新 working_hours 與 att_last_sum
                        $sqlUpd = "UPDATE `薪資管理` SET working_hours = :wh, att_last_sum = :att_last WHERE id = :id AND salary_month = :month";
                        $pdo->prepare($sqlUpd)->execute([
                            'wh' => $updated_wh,
                            'att_last' => $new_att_sum,
                            'id' => $userId,
                            'month' => $month
                        ]);
                        $salary['working_hours'] = $updated_wh;
                        $salary['att_last_sum'] = $new_att_sum;
                        $hours_to_return = $updated_wh;
                    } else {
                        // 若無手動修改紀錄，直接以 attendance 為準，並更新 att_last_sum
                        $sqlUpd = "UPDATE `薪資管理` SET working_hours = :wh, att_last_sum = :att_last WHERE id = :id AND salary_month = :month";
                        $pdo->prepare($sqlUpd)->execute([
                            'wh' => $new_att_sum,
                            'att_last' => $new_att_sum,
                            'id' => $userId,
                            'month' => $month
                        ]);
                        $salary['working_hours'] = $new_att_sum;
                        $salary['att_last_sum'] = $new_att_sum;
                        $hours_to_return = $new_att_sum;
                    }
                }
            }

            // 將資料 normalize 並確保回傳欄位為 hours
            $records[] = normalize_record(array_merge($salary, ['salary_month'=>$month, 'hours'=>$hours_to_return]));
        }

        echo json_encode(['success'=>true,'records'=>$records]);
        exit;
    }

    // ===============================
    // 2️⃣ 取得單一員工薪資詳細資料 (管理員)
    // ===============================
    if ($action === 'detail') {
        check_api_admin_auth();

        $id = $input['id'] ?? null;
        $month = $input['month'] ?? date('Y-m');

        if (!$id) {
            echo json_encode(['success' => false, 'message' => '缺少 id']);
            exit;
        }

        // 先抓 attendance 的加總（即時）
        $sqlAtt = "SELECT COALESCE(SUM(hours),0) AS att_hours
                   FROM `attendance`
                   WHERE user_id = :uid AND DATE_FORMAT(clock_in,'%Y-%m') = :month";
        $stmtAtt = $pdo->prepare($sqlAtt);
        $stmtAtt->execute(['uid'=>$id, 'month'=>$month]);
        $att_hours = (float)$stmtAtt->fetchColumn();

        // 再抓薪資表（如果有）
        $sqlSalary = "SELECT * FROM `薪資管理` WHERE id=:uid AND salary_month=:month LIMIT 1";
        $stmtSalary = $pdo->prepare($sqlSalary);
        $stmtSalary->execute(['uid'=>$id, 'month'=>$month]);
        $salary = $stmtSalary->fetch();

        $locked = is_salary_month_locked($month);
        if (!$salary) {
            // 若無薪資紀錄 -> 以 attendance 為準並新增一筆
            // 先抓員工基本資料
            $sqlEmp = "SELECT name, base_salary, hourly_rate FROM `員工基本資料` WHERE id = :id LIMIT 1";
            $stmtEmp = $pdo->prepare($sqlEmp);
            $stmtEmp->execute(['id'=>$id]);
            $emp = $stmtEmp->fetch();

            if (!$emp) {
                echo json_encode(['success'=>false,'message'=>'找不到員工資料']);
                exit;
            }

            $sqlIns = "INSERT INTO `薪資管理`
                       (id, name, salary_month, base_salary, hourly_rate, working_hours, att_last_sum, bonus, deductions)
                       VALUES (:id, :name, :month, :base_salary, :hourly_rate, :working_hours, :att_last_sum, 0, 0)";
            $pdo->prepare($sqlIns)->execute([
                'id' => $id,
                'name' => $emp['name'],
                'month' => $month,
                'base_salary' => $emp['base_salary'],
                'hourly_rate' => $emp['hourly_rate'],
                'working_hours' => $att_hours,
                'att_last_sum' => $att_hours
            ]);

            $salary = [
                'id' => $id,
                'name' => $emp['name'],
                'base_salary' => $emp['base_salary'],
                'hourly_rate' => $emp['hourly_rate'],
                'working_hours' => $att_hours,
                'bonus' => 0,
                'deductions' => 0
            ];

            $hours_to_return = $att_hours;
        } else {
            // 若有薪資紀錄
            if ($locked) {
                // 鎖定 -> 以薪資表為主（不使用 attendance）
                $hours_to_return = (float)($salary['working_hours'] ?? 0);
            } else {
                // 未鎖定 -> 以 attendance 為準，並更新薪資表
                $hours_to_return = $att_hours;
                $sqlUpd = "UPDATE `薪資管理` SET working_hours = :wh, att_last_sum = :att_last WHERE id = :id AND salary_month = :month";
                $pdo->prepare($sqlUpd)->execute([
                    'wh' => $att_hours,
                    'att_last' => $att_hours,
                    'id' => $id,
                    'month' => $month
                ]);
                $salary['working_hours'] = $att_hours;
            }
        }

        echo json_encode(['success'=>true,'record'=> normalize_record(array_merge($salary, ['salary_month'=>$month, 'hours'=>$hours_to_return]))]);
        exit;
    }

  // ===============================
  // 3️⃣ 更新或新增薪資 (管理員) - update（已整合 hours_manual、att_last_sum 邏輯）
  // ===============================
if ($action === 'update') {
    check_api_admin_auth();

    $id = $input['user_id'] ?? null;
    $month = $input['salary_month'] ?? $input['month'] ?? date('Y-m');

    // 支援前端送來的 manual 時數（我們接受 working_hours 當作手動時數輸入）
    $provided_manual = null;
    if (isset($input['working_hours'])) $provided_manual = (float)$input['working_hours'];
    if (isset($input['hours']) && $provided_manual === null) $provided_manual = (float)$input['hours'];

    $bonus = isset($input['bonus']) ? (float)$input['bonus'] : 0;
    $deductions = isset($input['deductions']) ? (float)$input['deductions'] : 0;

    if (!$id) {
        echo json_encode(['success'=>false,'message'=>'缺少 user_id']);
        exit;
    }

    try {
        // -----------------------------
        // 3.1 抓現有薪資資料與該月 attendance 總時數
        // -----------------------------
        $sqlCheck = "SELECT * FROM `薪資管理` WHERE id = :id AND salary_month = :month LIMIT 1";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute(['id'=>$id,'month'=>$month]);
        $row = $stmtCheck->fetch();
        $exists = (bool)$row;

        // 取得 attendance 當前累積（即時）
        $sqlAtt = "SELECT COALESCE(SUM(hours),0) AS att_hours FROM `attendance` WHERE user_id = :uid AND DATE_FORMAT(clock_in,'%Y-%m') = :month";
        $stmtAtt = $pdo->prepare($sqlAtt);
        $stmtAtt->execute(['uid'=>$id,'month'=>$month]);
        $att_sum = (float)$stmtAtt->fetchColumn();

        $locked = is_salary_month_locked($month);

        if ($exists) {
            // -----------------------------
            // 3.2 更新已存在的薪資紀錄（有可能手動修改或只更新獎金/扣款）
            // -----------------------------

            // 初始化更新欄位
            $fields = [];
            $params = [];

            // 取得 DB 現有值
            $db_working_hours = (float)($row['working_hours'] ?? 0);
            $db_att_last_sum = isset($row['att_last_sum']) ? (float)$row['att_last_sum'] : 0;
            $db_hours_manual = isset($row['hours_manual']) && $row['hours_manual'] !== null ? (float)$row['hours_manual'] : null;
            $db_base_salary = (float)($row['base_salary'] ?? 0);
            $db_hourly_rate = (float)($row['hourly_rate'] ?? 0);

            // ---------- 處理管理員手動輸入的時數（provided_manual） ----------
            if ($provided_manual !== null) {
                // 視為手動修改 -> 寫入 hours_manual，並把 working_hours 設為手動值
                // 為了讓之後的新增 attendance 可以從當前 att_sum 開始累加，將 att_last_sum 設為當前 att_sum（snapshot）
                $fields[] = "hours_manual = :hours_manual";
                $fields[] = "working_hours = :working_hours";
                $fields[] = "att_last_sum = :att_last_sum";
                $params['hours_manual'] = $provided_manual;
                $params['working_hours'] = $provided_manual;
                $params['att_last_sum'] = $att_sum; // snapshot of attendance at time of manual edit
                // 當月已鎖定時，既然要以手動數為主，也把 att_last_sum 同步設為 att_sum（不再累加）
            } else {
                // 無手動輸入（provided_manual == null）
                if (!$locked) {
                    // 未鎖定：以 attendance 差值累加到 working_hours（避免重複累加，使用 att_last_sum 作為快照）
                    $delta = $att_sum - $db_att_last_sum;
                    if ($delta > 0) {
                        // 累加新增的 attendance 差值
                        $fields[] = "working_hours = :working_hours";
                        $fields[] = "att_last_sum = :att_last_sum";
                        $params['working_hours'] = $db_working_hours + $delta;
                        $params['att_last_sum'] = $att_sum;
                    } else {
                        // 沒有新增 attendance，不更新 working_hours 也不更新 att_last_sum
                    }
                } else {
                    // 已鎖定：不更新 working_hours，也不更新 att_last_sum（資料固定）
                    // 不做任何 working_hours/att_last_sum 更新
                }
            }

            // ---------- 獎金/扣款 必定入參更新（即使是 0） ----------
            $fields[] = "bonus = :bonus";
            $fields[] = "deductions = :deductions";
            $params['bonus'] = $bonus;
            $params['deductions'] = $deductions;

            // ---------- 計算 total_salary（需用最新可能的 working_hours） ----------
            // 若剛剛在 $params 裡指定了 working_hours，使用它；否則用 DB 現有值
            $calc_working_hours = array_key_exists('working_hours', $params) ? (float)$params['working_hours'] : $db_working_hours;
            $total_salary = ($db_base_salary > 0)
                ? $db_base_salary + $params['bonus'] - $params['deductions']
                : ($db_hourly_rate * $calc_working_hours + $params['bonus'] - $params['deductions']);

            $fields[] = "total_salary = :total_salary";
            $params['total_salary'] = $total_salary;

            // ---------- 如果沒有任何要更新的欄位（fields 為空），直接回應成功（避免 SQL 語法錯誤） ----------
            if (count($fields) > 0) {
                $params['id'] = $id;
                $params['month'] = $month;
                $sqlUpd = "UPDATE `薪資管理` SET ".implode(', ',$fields)." WHERE id = :id AND salary_month = :month";
                $pdo->prepare($sqlUpd)->execute($params);
            }

        } else {
            // -----------------------------
            // 3.3 如果該月份薪資紀錄不存在 -> 新增一筆（支援 provided_manual 或用 attendance）
            // -----------------------------
            // 若沒有 provided_manual，則以當前 attendance sum 作為 working_hours（att_last_sum 也設為 att_sum）
            $new_working_hours = $provided_manual !== null ? $provided_manual : $att_sum;
            $new_hours_manual = $provided_manual !== null ? $provided_manual : null;
            $new_att_last_sum = $att_sum;

            // 取得員工基本資料以填 base_salary / hourly_rate / name
            $sqlEmp = "SELECT base_salary, hourly_rate, name FROM `員工基本資料` WHERE id = :id LIMIT 1";
            $stmtEmp = $pdo->prepare($sqlEmp);
            $stmtEmp->execute(['id'=>$id]);
            $emp = $stmtEmp->fetch();

            if (!$emp) {
                echo json_encode(['success'=>false,'message'=>'找不到員工基本資料']);
                exit;
            }

            $base_salary = (float)$emp['base_salary'];
            $hourly_rate = (float)$emp['hourly_rate'];

            $total_salary = ($base_salary > 0)
                ? $base_salary + $bonus - $deductions
                : ($hourly_rate * $new_working_hours + $bonus - $deductions);

            $sqlIns = "
                INSERT INTO `薪資管理`(id,name,salary_month,base_salary,hourly_rate,
                    working_hours,hours_manual,att_last_sum,bonus,deductions,total_salary,created_at)
                VALUES (:id,:name,:month,:base_salary,:hourly_rate,
                    :working_hours,:hours_manual,:att_last_sum,:bonus,:deductions,:total_salary,NOW())
            ";
            $pdo->prepare($sqlIns)->execute([
                'id'=>$id,
                'name'=>$emp['name'],
                'month'=>$month,
                'base_salary'=>$base_salary,
                'hourly_rate'=>$hourly_rate,
                'working_hours'=>$new_working_hours,
                'hours_manual'=>$new_hours_manual,
                'att_last_sum'=>$new_att_last_sum,
                'bonus'=>$bonus,
                'deductions'=>$deductions,
                'total_salary'=>$total_salary
            ]);
        }

        echo json_encode(['success'=>true,'message'=>'薪資資料已更新']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success'=>false,'message'=>'伺服器錯誤：'.$e->getMessage()]);
        exit;
    }
}

    // ===============================
    // 4️⃣ 恢復薪資資料 (管理員)
    // ===============================
    if ($action === 'restore') {
        check_api_admin_auth();

        $id = $input['user_id'] ?? null;
        $month = $input['month'] ?? date('Y-m');

        if (!$id) {
            echo json_encode(['success' => false, 'message' => '缺少 user_id']);
            exit;
        }

        $sql = "DELETE FROM `薪資管理` WHERE id = :id AND salary_month = :month";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'month' => $month]);

        echo json_encode(['success' => true, 'message' => '薪資資料已恢復']);
        exit;
    }

    // ===============================
    // 5️⃣ 員工查詢自己的薪資記錄列表 - fetch_my_records
    // ===============================
    if ($action === 'fetch_my_records') {
        $user = get_user_info();
        $userId = $user['uid'];
        $year = $input['year'] ?? date('Y');

        $sqlMonths = "SELECT DISTINCT DATE_FORMAT(clock_in,'%Y-%m') AS month 
                      FROM `attendance` 
                      WHERE user_id=:uid AND YEAR(clock_in)=:year 
                      ORDER BY month DESC";
        $stmt = $pdo->prepare($sqlMonths);
        $stmt->execute(['uid'=>$userId,'year'=>$year]);
        $months = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $records = [];
        foreach ($months as $month) {
            $sqlHours = "SELECT COALESCE(SUM(hours),0) AS att_hours 
                         FROM `attendance` 
                         WHERE user_id=:uid AND DATE_FORMAT(clock_in,'%Y-%m')=:month";
            $stmtHours = $pdo->prepare($sqlHours);
            $stmtHours->execute(['uid'=>$userId,'month'=>$month]);
            $att_hours = (float)$stmtHours->fetchColumn();

            $sqlSalary = "SELECT * FROM `薪資管理` WHERE id=:uid AND salary_month=:month LIMIT 1";
            $stmtSalary = $pdo->prepare($sqlSalary);
            $stmtSalary->execute(['uid'=>$userId,'month'=>$month]);
            $salary = $stmtSalary->fetch();

            $locked = is_salary_month_locked($month);

            if (!$salary) {
                // 新增一筆
                $sqlEmp = "SELECT name, base_salary, hourly_rate FROM `員工基本資料` WHERE id=:id LIMIT 1";
                $stmtEmp = $pdo->prepare($sqlEmp);
                $stmtEmp->execute(['id'=>$userId]);
                $emp = $stmtEmp->fetch();

                $pdo->prepare("INSERT INTO `薪資管理` (id,name,salary_month,base_salary,hourly_rate,working_hours,att_last_sum,bonus,deductions)
                               VALUES (:id,:name,:month,:base_salary,:hourly_rate,:working_hours,:att_last_sum,0,0)")
                    ->execute([
                        'id'=>$userId,
                        'name'=>$emp['name'],
                        'month'=>$month,
                        'base_salary'=>$emp['base_salary'],
                        'hourly_rate'=>$emp['hourly_rate'],
                        'working_hours'=>$att_hours,
                        'att_last_sum'=>$att_hours
                    ]);

                $salary = [
                    'id'=>$userId,
                    'name'=>$emp['name'],
                    'base_salary'=>$emp['base_salary'],
                    'hourly_rate'=>$emp['hourly_rate'],
                    'working_hours'=>$att_hours,
                    'bonus'=>0,
                    'deductions'=>0
                ];
                $hours_to_return = $att_hours;
            } else {
                if ($locked) {
                    $hours_to_return = (float)($salary['working_hours'] ?? 0);
                } else {
                    // 未鎖定 -> 更新為 attendance 的加總（若曾手動修改則以 hours_manual 為基準加上新增差值）
                    $db_att_last_sum = (float)($salary['att_last_sum'] ?? 0);
                    $db_hours_manual = isset($salary['hours_manual']) && $salary['hours_manual'] !== null ? (float)$salary['hours_manual'] : null;

                    if ($db_hours_manual !== null) {
                        $delta = $att_hours - $db_att_last_sum;
                        $hours_to_return = $db_hours_manual + ($delta > 0 ? $delta : 0);
                        // 更新 DB
                        $pdo->prepare("UPDATE `薪資管理` SET working_hours = :wh, att_last_sum = :att_last WHERE id = :id AND salary_month = :month")
                            ->execute(['wh'=>$hours_to_return, 'att_last'=>$att_hours, 'id'=>$userId, 'month'=>$month]);
                    } else {
                        $hours_to_return = $att_hours;
                        $pdo->prepare("UPDATE `薪資管理` SET working_hours = :wh, att_last_sum = :att_last WHERE id = :id AND salary_month = :month")
                            ->execute(['wh'=>$att_hours, 'att_last'=>$att_hours, 'id'=>$userId, 'month'=>$month]);
                    }
                }
            }

            $records[] = normalize_record(array_merge($salary, ['salary_month'=>$month, 'hours'=>$hours_to_return]));
        }

        echo json_encode(['success'=>true,'records'=>$records]);
        exit;
    }

    // ===============================
    // 6️⃣ 員工查詢自己的薪資詳細資料 - fetch_my_detail
    // ===============================
    if ($action === 'fetch_my_detail') {
        $user = get_user_info();
        $userId = $user['uid'];
        $month = $input['month'] ?? '';

        if (!$month) {
            echo json_encode(['success'=>false,'message'=>'缺少月份']);
            exit;
        }

        $sqlHours = "SELECT COALESCE(SUM(hours),0) AS att_hours 
                     FROM `attendance` 
                     WHERE user_id = :uid AND DATE_FORMAT(clock_in,'%Y-%m') = :month";
        $stmt = $pdo->prepare($sqlHours);
        $stmt->execute(['uid'=>$userId, 'month'=>$month]);
        $att_hours = (float)$stmt->fetchColumn();

        $sqlSalary = "SELECT * FROM `薪資管理` WHERE id = :uid AND salary_month = :month LIMIT 1";
        $stmt = $pdo->prepare($sqlSalary);
        $stmt->execute(['uid'=>$userId, 'month'=>$month]);
        $salary = $stmt->fetch();

        $locked = is_salary_month_locked($month);

        if (!$salary) {
            $sqlEmp = "SELECT name, base_salary, hourly_rate FROM `員工基本資料` WHERE id = :id LIMIT 1";
            $stmtEmp = $pdo->prepare($sqlEmp);
            $stmtEmp->execute(['id'=>$userId]);
            $emp = $stmtEmp->fetch();

            $pdo->prepare("INSERT INTO `薪資管理` (id, name, salary_month, base_salary, hourly_rate, working_hours, att_last_sum, bonus, deductions)
                           VALUES (:id, :name, :month, :base_salary, :hourly_rate, :working_hours, :att_last_sum, 0, 0)")
                ->execute([
                    'id' => $userId,
                    'name' => $emp['name'],
                    'month' => $month,
                    'base_salary' => $emp['base_salary'],
                    'hourly_rate' => $emp['hourly_rate'],
                    'working_hours' => $att_hours,
                    'att_last_sum' => $att_hours
                ]);

            $salary = [
                'id' => $userId,
                'name' => $emp['name'],
                'base_salary' => $emp['base_salary'],
                'hourly_rate' => $emp['hourly_rate'],
                'working_hours' => $att_hours,
                'bonus' => 0,
                'deductions' => 0
            ];

            $hours_to_return = $att_hours;
        } else {
            if ($locked) {
                $hours_to_return = (float)($salary['working_hours'] ?? 0);
            } else {
                $hours_to_return = $att_hours;
                $pdo->prepare("UPDATE `薪資管理` SET working_hours = :wh, att_last_sum = :att_last WHERE id = :id AND salary_month = :month")
                    ->execute(['wh'=>$att_hours, 'att_last'=>$att_hours, 'id'=>$userId, 'month'=>$month]);
                $salary['working_hours'] = $att_hours;
            }
        }

        echo json_encode(['success' => true, 'record' => normalize_record(array_merge($salary, ['salary_month' => $month, 'hours' => $hours_to_return]))]);
        exit;
    }

    // ===============================
    // 7️⃣ 未知 action
    // ===============================
    echo json_encode(['success' => false, 'message' => '未知的 action']);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '伺服器錯誤：' . $e->getMessage()]);
    exit;
}
