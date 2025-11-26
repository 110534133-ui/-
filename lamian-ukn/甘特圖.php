<?php
// 甘特圖.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = 'localhost';
$db   = 'lamian';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $date = $_GET['date'] ?? date('Y-m-d');

    // 抓取班表資料並關聯員工姓名
    $stmt = $pdo->prepare("
        SELECT b.roster_id, b.user_id, u.name, b.work_date, b.start_time, b.end_time, b.shift_type, b.note
        FROM 班表 b
        LEFT JOIN `員工基本資料` u ON b.user_id = u.id
        WHERE b.work_date = ?
        ORDER BY b.start_time
    ");
    $stmt->execute([$date]);
    $rows = $stmt->fetchAll();

    $data = [];
    foreach ($rows as $row) {
        // 判斷上午/晚上
        $hour = (int)substr($row['start_time'],0,2);
        $period = ($hour < 16) ? '上午' : '晚上';

        $data[] = [
            'period' => $period,
            'name'   => $row['name'] ?? '未命名',
            'time'   => $row['start_time'].'-'.$row['end_time']
        ];
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
