<?php
// ===== salary_record.php - 員工薪資記錄API =====
session_start();

// 登入檢查
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

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
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗:' . $e->getMessage()]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$userId = $_SESSION['uid']; // 使用登入的員工ID

try {
    // ===============================
    // 1️⃣ 獲取員工個人薪資記錄列表
    // ===============================
    if ($action === 'fetch_my_records') {
        $year = $input['year'] ?? date('Y');
        
        $sql = "
            SELECT 
                e.id,
                e.name,
                e.base_salary AS employee_base_salary,
                e.hourly_rate AS employee_hourly_rate,
                s.salary_month,
                s.base_salary,
                s.hourly_rate,
                s.working_hours,
                s.bonus,
                s.deductions,
                s.total_salary,
                s.created_at
            FROM `員工基本資料` e
            LEFT JOIN `薪資管理` s 
                ON e.id = s.id 
                AND YEAR(s.salary_month) = :year
            WHERE e.id = :userId
            ORDER BY s.salary_month DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['userId' => $userId, 'year' => $year]);
        $records = $stmt->fetchAll();
        
        // 處理每筆記錄
        $processedRecords = [];
        foreach ($records as $row) {
            if ($row['salary_month']) {
                // 判斷薪資類型
                $base_salary = (float)($row['base_salary'] ?? 0);
                $hourly_rate = (float)($row['hourly_rate'] ?? 0);
                
                // 如果薪資管理表沒有值,使用員工基本資料的值
                if ($base_salary == 0 && $hourly_rate == 0) {
                    $base_salary = (float)($row['employee_base_salary'] ?? 0);
                    $hourly_rate = (float)($row['employee_hourly_rate'] ?? 0);
                }
                
                $salary_type = $base_salary > 0 ? '月薪' : '時薪';
                
                $processedRecords[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'salary_month' => $row['salary_month'],
                    'salary_type' => $salary_type,
                    'base_salary' => $base_salary,
                    'hourly_rate' => $hourly_rate,
                    'working_hours' => (float)($row['working_hours'] ?? 0),
                    'bonus' => (float)($row['bonus'] ?? 0),
                    'deductions' => (float)($row['deductions'] ?? 0),
                    'total_salary' => (float)($row['total_salary'] ?? 0),
                    'created_at' => $row['created_at']
                ];
            }
        }
        
        echo json_encode(['success' => true, 'records' => $processedRecords]);
        exit;
    }
    
    // ===============================
    // 2️⃣ 獲取員工個人薪資詳細資料
    // ===============================
    if ($action === 'fetch_detail') {
        $month = $input['month'] ?? '';
        
        if (!$month) {
            echo json_encode(['success' => false, 'message' => '缺少月份參數']);
            exit;
        }
        
        $sql = "
            SELECT 
                e.id,
                e.name,
                e.base_salary AS employee_base_salary,
                e.hourly_rate AS employee_hourly_rate,
                s.salary_month,
                s.base_salary,
                s.hourly_rate,
                s.working_hours,
                s.bonus,
                s.deductions,
                s.total_salary,
                s.created_at
            FROM `員工基本資料` e
            LEFT JOIN `薪資管理` s 
                ON e.id = s.id 
                AND s.salary_month = :month
            WHERE e.id = :userId
            LIMIT 1
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['userId' => $userId, 'month' => $month]);
        $row = $stmt->fetch();
        
        if (!$row) {
            echo json_encode(['success' => false, 'message' => '查無資料']);
            exit;
        }
        
        // 判斷薪資類型
        $base_salary = (float)($row['base_salary'] ?? 0);
        $hourly_rate = (float)($row['hourly_rate'] ?? 0);
        
        // 如果薪資管理表沒有值,使用員工基本資料的值
        if ($base_salary == 0 && $hourly_rate == 0) {
            $base_salary = (float)($row['employee_base_salary'] ?? 0);
            $hourly_rate = (float)($row['employee_hourly_rate'] ?? 0);
        }
        
        $salary_type = $base_salary > 0 ? '月薪' : '時薪';
        
        $record = [
            'id' => $row['id'],
            'name' => $row['name'],
            'salary_month' => $row['salary_month'] ?? $month,
            'salary_type' => $salary_type,
            'base_salary' => $base_salary,
            'hourly_rate' => $hourly_rate,
            'working_hours' => (float)($row['working_hours'] ?? 0),
            'bonus' => (float)($row['bonus'] ?? 0),
            'deductions' => (float)($row['deductions'] ?? 0),
            'total_salary' => (float)($row['total_salary'] ?? 0),
            'created_at' => $row['created_at']
        ];
        
        echo json_encode(['success' => true, 'record' => $record]);
        exit;
    }
    
    // ===============================
    // 3️⃣ 獲取員工年度薪資統計
    // ===============================
    if ($action === 'fetch_yearly_summary') {
        $year = $input['year'] ?? date('Y');
        
        $sql = "
            SELECT 
                COUNT(*) as total_months,
                SUM(working_hours) as total_hours,
                SUM(bonus) as total_bonus,
                SUM(deductions) as total_deductions,
                SUM(total_salary) as total_salary
            FROM `薪資管理`
            WHERE id = :userId 
            AND YEAR(salary_month) = :year
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['userId' => $userId, 'year' => $year]);
        $summary = $stmt->fetch();
        
        echo json_encode(['success' => true, 'summary' => $summary]);
        exit;
    }
    
    // ===============================
    // 4️⃣ 未知 action
    // ===============================
    echo json_encode(['success' => false, 'message' => '未知的 action']);
    exit;
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '伺服器錯誤:' . $e->getMessage()]);
    exit;
}