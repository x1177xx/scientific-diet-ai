<?php
// generate_ai_advice.php - 修复版本
// 防止重复定义和session问题

// 在文件顶部开始会话并设置头部
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 设置头部
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

require 'db_connect.php';

// 检查类是否已加载，如果没有则加载
if (!class_exists('AIAdvisor')) {
    require 'AIAdvisor.php';
}

$userId = $_SESSION['user_id'];
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

try {
    $advisor = new AIAdvisor($conn);
    $result = $advisor->generateAdvice($userId, $date);
    
    echo json_encode([
        'success' => true,
        'message' => 'AI建议生成成功',
        'data' => $result,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'AI建议生成失败: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>