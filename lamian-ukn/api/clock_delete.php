<?php
// clock_delete.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// 連線資料庫
$conn = new mysqli('localhost', 'root', '', 'lamian');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// 從 GET 接收 id（前端 fetch 使用 ?id=XXX）
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

// 刪除 attendance 內的指定流水號
$stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Record deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'No record matched']);
}

$stmt->close();
$conn->close();
