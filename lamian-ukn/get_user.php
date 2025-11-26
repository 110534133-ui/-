<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 確認是否已登入
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'msg' => '未登入']);
    exit;
}

require_once 'db_connect.php'; // ← 請確認這檔案連線正確
$user_id = $_SESSION['user_id'];  // 從登入時存的 session 抓使用者ID
$page = $_GET['page'] ?? '';      // 前端傳入頁面名稱
$data = [];

// 根據不同頁面，回傳個人資料
switch ($page) {
    // === 日報表 ===
    case '日報表':
    case '日報表紀錄':
        $stmt = $pdo->prepare("SELECT * FROM 日報表 WHERE filled_by = (SELECT name FROM 員工基本資料 WHERE id = ?)");
        $stmt->execute([$user_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    // === 員工資料 ===
    case '員工資料表':
        $stmt = $pdo->prepare("SELECT id, name, account, phone, hire_date, role, salary_type, base_salary, hourly_wage 
                               FROM 員工基本資料 WHERE id = ?");
        $stmt->execute([$user_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        break;

    // === 薪資管理、薪資記錄 ===
    case '薪資管理':
    case '薪資記錄':
        $stmt = $pdo->prepare("SELECT * FROM 薪資 WHERE employee_id = ?");
        $stmt->execute([$user_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    // === 打卡管理 ===
    case '打卡管理':
        $stmt = $pdo->prepare("SELECT * FROM 打卡紀錄 WHERE employee_id = ?");
        $stmt->execute([$user_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    // === 班表、班表管理 ===
    case '班表':
    case '班表管理':
        $stmt = $pdo->prepare("SELECT * FROM 班表 WHERE employee_id = ?");
        $stmt->execute([$user_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => '無效的頁面參數']);
        exit;
}

// 成功回傳
echo json_encode(['ok' => true, 'data' => $data]);
?>
