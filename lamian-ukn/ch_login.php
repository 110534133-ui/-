<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if(isset($_SESSION['user_id'])){
    echo json_encode([
        'ok' => true,
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'],
        'user_account' => $_SESSION['user_account']
    ]);
}else{
    echo json_encode(['ok' => false]);
}
