<?php
header('Content-Type: application/json');
session_start();
require 'db_connect.php';

// 验证会话用户ID
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => '未授权访问']));
}

// 获取输入数据
$input = json_decode(file_get_contents('php://input'), true);

// 验证必要字段
$requiredFields = ['record_id', 'user_id', 'intake_date'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => "缺少字段: $field"]));
    }
}

// 确保用户只能删除自己的记录
if ($input['user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => '无权操作他人记录']));
}

try {
    // 精准删除（三字段联合主键）
    $stmt = $conn->prepare("
        DELETE FROM intake_records 
        WHERE record_id = ? 
        AND user_id = ? 
        AND intake_date = ?
    ");
    $stmt->bind_param("iis", $input['record_id'], $input['user_id'], $input['intake_date']);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("记录不存在或已被删除");
    }

    // 更新每日营养统计（需同步修改）
    updateDailyNutrition($conn, $input['user_id'], $input['intake_date']);

    echo json_encode(['success' => true, 'message' => '删除成功']);

} catch (Exception $e) {
    error_log("删除错误: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '删除失败: ' . $e->getMessage()]);
}

// 辅助函数：更新每日营养统计
function updateDailyNutrition($conn, $userId, $date) {
    $stmt = $conn->prepare("
        UPDATE daily_nutrition dn
        SET 
            calories = COALESCE((
                SELECT SUM(f.calories * ir.amount / 100)
                FROM intake_records ir
                JOIN foods f ON ir.food_name = f.food_name
                WHERE ir.user_id = ? AND ir.intake_date = ?
            ), 0),
            protein = COALESCE((
                SELECT SUM(f.protein * ir.amount / 100)
                FROM intake_records ir
                JOIN foods f ON ir.food_name = f.food_name
                WHERE ir.user_id = ? AND ir.intake_date = ?
            ), 0),
            carbohydrates = COALESCE((
                SELECT SUM(f.carbohydrates * ir.amount / 100)
                FROM intake_records ir
                JOIN foods f ON ir.food_name = f.food_name
                WHERE ir.user_id = ? AND ir.intake_date = ?
            ), 0),
            fat = COALESCE((
                SELECT SUM(f.fat * ir.amount / 100)
                FROM intake_records ir
                JOIN foods f ON ir.food_name = f.food_name
                WHERE ir.user_id = ? AND ir.intake_date = ?
            ), 0)
        WHERE dn.user_id = ? AND dn.record_date = ?
    ");
    $stmt->bind_param("isisisis", $userId, $date, $userId, $date, $userId, $date, $userId, $date, $userId, $date);
    $stmt->execute();
}
?>