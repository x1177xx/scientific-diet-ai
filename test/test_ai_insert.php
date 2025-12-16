<?php
// test_ai_insert.php - 测试AI建议插入
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit();
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');
$testContent = "这是一个测试建议，生成于 " . date('Y-m-d H:i:s');
$testType = 'general';

$conn->begin_transaction();

try {
    // 删除现有的测试记录
    $deleteStmt = $conn->prepare("DELETE FROM ai_recommendations WHERE user_id = ? AND recommendation_date = ? AND type = 'test'");
    $deleteStmt->bind_param("is", $userId, $today);
    $deleteStmt->execute();
    $deleteStmt->close();
    
    // 插入新记录
    $insertStmt = $conn->prepare("INSERT INTO ai_recommendations (user_id, recommendation_date, content, type) VALUES (?, ?, ?, ?)");
    $insertStmt->bind_param("isss", $userId, $today, $testContent, $testType);
    
    if ($insertStmt->execute()) {
        $newId = $insertStmt->insert_id;
        $insertStmt->close();
        
        // 验证插入
        $checkStmt = $conn->prepare("SELECT * FROM ai_recommendations WHERE id = ?");
        $checkStmt->bind_param("i", $newId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '测试插入成功',
            'data' => $checkResult
        ]);
    } else {
        throw new Exception("插入失败: " . $insertStmt->error);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => '测试插入失败',
        'error' => $e->getMessage()
    ]);
}
?>