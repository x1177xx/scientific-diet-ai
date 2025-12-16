<?php
// get_ai_history.php - 获取AI建议历史
session_start();
require 'db_connect.php';
require 'AIAdvisor.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

$userId = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

try {
    $advisor = new AIAdvisor($conn);
    $history = $advisor->getHistory($userId, $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $history,
        'count' => count($history)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>