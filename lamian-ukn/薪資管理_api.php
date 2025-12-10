<?php
// ===== 薪資管理_api.php (修正版) =====

require_once __DIR__ . '/includes/auth_check.php';

function check_api_admin_auth() {
    if (!check_user_level('A', false) && !check_user_level('B', false)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '權限不足,無法執行此操作']);
        exit;
    }
}

// ===== 資料庫連線 =====
$db_host = '127.0.0.1';
$db_name = 'lamian';
$db_user = 'root';
$db_pass = '';
$charset = 'utf8mb4';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$charset}";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗:' . $e->getMessage()]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// 檢查薪資月份是否已鎖定
function is_salary_month_locked(string $salary_month): bool {
    $dt = DateTime::createFromFormat('Y-m', $salary_month);
    if (!$dt) return false;
    $dt->modify('+1 month')->modify('first day of this month')->setTime(0,0,0);
    $now = new DateTime('now');
    return $now >= $dt;
}

// 標準化記錄格式
function normalize_record($row) {
    $base_salary = (float)($row['base_salary'] ?? 0);
    $hourly_rate = (float)($row['hourly_rate'] ?? 0);
    $hours = (float)($row['working_hours'] ?? 0);
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
        'hours' => $hours,
        'bonus' => $bonus,
        'deductions' => $deductions,
        'total_salary' => $total_salary,
    ];
}

try {

    // ===============================
    // 1️⃣ 取得薪資列表 - fetch
    // ===============================
    if ($action === 'fetch') {
        check_api_admin_auth();

        $month = $input['month'] ?? date('Y-m');
        $keyword = trim($input['keyword'] ?? '');

        // 先取得該月份有打卡記錄的員工
        $sqlEmp = "
            SELECT DISTINCT e.id, e.name, e.base_salary, e.hourly_rate
            FROM `員工基本資料` e
            INNER JOIN `attendance` a ON e.id = a.user_id
            WHERE DATE_FORMAT(a.clock_in, '%Y-%m') = :month
        ";

        $params = ['month' => $month];

        if ($keyword !== '') {
            $sqlEmp .= " AND (e.name LIKE :kw OR CAST(e.id AS CHAR) LIKE :kw)";
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

            // 計算該月總工時
            $sqlHours = "
                SELECT COALESCE(SUM(hours), 0) AS att_hours
                FROM `attendance`
                WHERE user_id = :uid 
                AND DATE_FORMAT(clock_in, '%Y-%m') = :month
            ";
            $stmtHours = $pdo->prepare($sqlHours);
            $stmtHours->execute(['uid' => $userId, 'month' => $month]);
            $att_hours = (float)$stmtHours->fetchColumn();

            // 查詢薪資記錄
            $sqlSalary = "
                SELECT * FROM `薪資管理` 
                WHERE id = :uid AND salary_month = :month 
                LIMIT 1
            ";
            $stmtSalary = $pdo->prepare($sqlSalary);
            $stmtSalary->execute(['uid' => $userId, 'month' => $month]);
            $salary = $stmtSalary->fetch();

            if (!$salary) {
                // 不存在則新增
                $sqlIns = "
                    INSERT INTO `薪資管理` 
                    (id, name, salary_month, base_salary, hourly_rate, 
                     working_hours, att_last_sum, bonus, deductions)
                    VALUES (:id, :name, :month, :base_salary, :hourly_rate, 
                            :working_hours, :att_last_sum, 0, 0)
                ";
                $pdo->prepare($sqlIns)->execute([
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
                    // 已鎖定:使用資料表的工時
                    $hours_to_return = (float)($salary['working_hours'] ?? 0);
                } else {
                    // 未鎖定:更新為最新工時
                    $db_att_last_sum = (float)($salary['att_last_sum'] ?? 0);
                    $db_hours_manual = isset($salary['hours_manual']) && $salary['hours_manual'] !== null 
                        ? (float)$salary['hours_manual'] : null;

                    if ($db_hours_manual !== null) {
                        // 有手動修改記錄,計算差值
                        $delta = $att_hours - $db_att_last_sum;
                        $hours_to_return = $db_hours_manual + ($delta > 0 ? $delta : 0);
                    } else {
                        // 無手動記錄,直接使用 attendance
                        $hours_to_return = $att_hours;
                    }

                    // 更新資料庫
                    $sqlUpd = "
                        UPDATE `薪資管理` 
                        SET working_hours = :wh, att_last_sum = :att_last 
                        WHERE id = :id AND salary_month = :month
                    ";
                    $pdo->prepare($sqlUpd)->execute([
                        'wh' => $hours_to_return,
                        'att_last' => $att_hours,
                        'id' => $userId,
                        'month' => $month
                    ]);
                    $salary['working_hours'] = $hours_to_return;
                }
            }

            $records[] = normalize_record(array_merge($salary, [
                'salary_month' => $month,
                'working_hours' => $hours_to_return
            ]));
        }

        echo json_encode(['success' => true, 'records' => $records]);
        exit;
    }

    // ===============================
    // 2️⃣ 取得單一員工薪資詳情 - detail
    // ===============================
    if ($action === 'detail') {
        check_api_admin_auth();

        $id = $input['id'] ?? null;
        $month = $input['month'] ?? date('Y-m');

        if (!$id) {
            echo json_encode(['success' => false, 'message' => '缺少 id']);
            exit;
        }

        // 計算該月總工時
        $sqlAtt = "
            SELECT COALESCE(SUM(hours), 0) AS att_hours
            FROM `attendance`
            WHERE user_id = :uid AND DATE_FORMAT(clock_in, '%Y-%m') = :month
        ";
        $stmtAtt = $pdo->prepare($sqlAtt);
        $stmtAtt->execute(['uid' => $id, 'month' => $month]);
        $att_hours = (float)$stmtAtt->fetchColumn();

        // 查詢薪資記錄
        $sqlSalary = "
            SELECT * FROM `薪資管理` 
            WHERE id = :uid AND salary_month = :month 
            LIMIT 1
        ";
        $stmtSalary = $pdo->prepare($sqlSalary);
        $stmtSalary->execute(['uid' => $id, 'month' => $month]);
        $salary = $stmtSalary->fetch();

        $locked = is_salary_month_locked($month);

        if (!$salary) {
            // 不存在則新增
            $sqlEmp = "
                SELECT name, base_salary, hourly_rate 
                FROM `員工基本資料` 
                WHERE id = :id LIMIT 1
            ";
            $stmtEmp = $pdo->prepare($sqlEmp);
            $stmtEmp->execute(['id' => $id]);
            $emp = $stmtEmp->fetch();

            if (!$emp) {
                echo json_encode(['success' => false, 'message' => '找不到員工資料']);
                exit;
            }

            $sqlIns = "
                INSERT INTO `薪資管理`
                (id, name, salary_month, base_salary, hourly_rate, 
                 working_hours, att_last_sum, bonus, deductions)
                VALUES (:id, :name, :month, :base_salary, :hourly_rate, 
                        :working_hours, :att_last_sum, 0, 0)
            ";
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
            if ($locked) {
                $hours_to_return = (float)($salary['working_hours'] ?? 0);
            } else {
                $hours_to_return = $att_hours;
                $sqlUpd = "
                    UPDATE `薪資管理` 
                    SET working_hours = :wh, att_last_sum = :att_last 
                    WHERE id = :id AND salary_month = :month
                ";
                $pdo->prepare($sqlUpd)->execute([
                    'wh' => $att_hours,
                    'att_last' => $att_hours,
                    'id' => $id,
                    'month' => $month
                ]);
                $salary['working_hours'] = $att_hours;
            }
        }

        echo json_encode(['success' => true, 'record' => normalize_record(array_merge($salary, [
            'salary_month' => $month,
            'working_hours' => $hours_to_return
        ]))]);
        exit;
    }

    // ===============================
    // 3️⃣ 更新薪資 - update
    // ===============================
    if ($action === 'update') {
        check_api_admin_auth();

        $id = $input['user_id'] ?? null;
        $month = $input['salary_month'] ?? $input['month'] ?? date('Y-m');

        $provided_manual = null;
        if (isset($input['working_hours'])) $provided_manual = (float)$input['working_hours'];
        if (isset($input['hours']) && $provided_manual === null) $provided_manual = (float)$input['hours'];

        $bonus = isset($input['bonus']) ? (float)$input['bonus'] : 0;
        $deductions = isset($input['deductions']) ? (float)$input['deductions'] : 0;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => '缺少 user_id']);
            exit;
        }

        // 檢查是否存在
        $sqlCheck = "
            SELECT * FROM `薪資管理` 
            WHERE id = :id AND salary_month = :month 
            LIMIT 1
        ";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute(['id' => $id, 'month' => $month]);
        $row = $stmtCheck->fetch();

        // 取得 attendance 總時數
        $sqlAtt = "
            SELECT COALESCE(SUM(hours), 0) AS att_hours 
            FROM `attendance` 
            WHERE user_id = :uid AND DATE_FORMAT(clock_in, '%Y-%m') = :month
        ";
        $stmtAtt = $pdo->prepare($sqlAtt);
        $stmtAtt->execute(['uid' => $id, 'month' => $month]);
        $att_sum = (float)$stmtAtt->fetchColumn();

        $locked = is_salary_month_locked($month);

        if ($row) {
            // 更新現有記錄
            $fields = [];
            $params = [];

            $db_working_hours = (float)($row['working_hours'] ?? 0);
            $db_att_last_sum = (float)($row['att_last_sum'] ?? 0);
            $db_hours_manual = isset($row['hours_manual']) && $row['hours_manual'] !== null 
                ? (float)$row['hours_manual'] : null;

            // 處理工時
            if ($provided_manual !== null) {
                // 手動輸入
                $fields[] = "hours_manual = :hours_manual";
                $fields[] = "working_hours = :working_hours";
                $fields[] = "att_last_sum = :att_last_sum";
                $params['hours_manual'] = $provided_manual;
                $params['working_hours'] = $provided_manual;
                $params['att_last_sum'] = $att_sum;
            } else {
                // 無手動輸入
                if (!$locked) {
                    $delta = $att_sum - $db_att_last_sum;
                    if ($delta > 0) {
                        $fields[] = "working_hours = :working_hours";
                        $fields[] = "att_last_sum = :att_last_sum";
                        $params['working_hours'] = $db_working_hours + $delta;
                        $params['att_last_sum'] = $att_sum;
                    }
                }
            }

            // 獎金與扣款
            $fields[] = "bonus = :bonus";
            $fields[] = "deductions = :deductions";
            $params['bonus'] = $bonus;
            $params['deductions'] = $deductions;

            if (count($fields) > 0) {
                $params['id'] = $id;
                $params['month'] = $month;
                $sqlUpd = "UPDATE `薪資管理` SET " . implode(', ', $fields) . " WHERE id = :id AND salary_month = :month";
                $pdo->prepare($sqlUpd)->execute($params);
            }
        } else {
            // 新增記錄
            $new_working_hours = $provided_manual !== null ? $provided_manual : $att_sum;
            $new_hours_manual = $provided_manual;

            $sqlEmp = "SELECT base_salary, hourly_rate, name FROM `員工基本資料` WHERE id = :id LIMIT 1";
            $stmtEmp = $pdo->prepare($sqlEmp);
            $stmtEmp->execute(['id' => $id]);
            $emp = $stmtEmp->fetch();

            if (!$emp) {
                echo json_encode(['success' => false, 'message' => '找不到員工基本資料']);
                exit;
            }

            $sqlIns = "
                INSERT INTO `薪資管理`
                (id, name, salary_month, base_salary, hourly_rate, 
                 working_hours, hours_manual, att_last_sum, bonus, deductions)
                VALUES (:id, :name, :month, :base_salary, :hourly_rate, 
                        :working_hours, :hours_manual, :att_last_sum, :bonus, :deductions)
            ";
            $pdo->prepare($sqlIns)->execute([
                'id' => $id,
                'name' => $emp['name'],
                'month' => $month,
                'base_salary' => $emp['base_salary'],
                'hourly_rate' => $emp['hourly_rate'],
                'working_hours' => $new_working_hours,
                'hours_manual' => $new_hours_manual,
                'att_last_sum' => $att_sum,
                'bonus' => $bonus,
                'deductions' => $deductions
            ]);
        }

        echo json_encode(['success' => true, 'message' => '薪資資料已更新']);
        exit;
    }

    // ===============================
    // 其他 actions...
    // ===============================

    echo json_encode(['success' => false, 'message' => '未知的 action']);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '伺服器錯誤:' . $e->getMessage()]);
    exit;
}