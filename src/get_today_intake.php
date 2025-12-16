<?php
// 在脚本最开始处设置错误处理
ini_set('display_errors', 0); // 不直接显示错误
error_reporting(E_ALL);
header('Content-Type: application/json');

// 自定义错误处理函数
function handleError($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler('handleError');

// 自定义异常处理函数
function handleException($e) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => '服务器错误: ' . $e->getMessage()
    ]));
}
set_exception_handler('handleException');

session_start();
require 'db_connect.php';

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    die(json_encode(["success" => false, "message" => "请先登录"]));
}

$user_id = $_SESSION["user_id"];
$today = date("Y-m-d");

try {
    // 获取今日营养汇总数据
    $stmt = $conn->prepare("
        SELECT calories, carbohydrates, fat, protein 
        FROM daily_nutrition 
        WHERE user_id = ? AND record_date = ?
    ");
    
    if (!$stmt) {
        throw new Exception("数据库准备语句失败: " . $conn->error);
    }
    
    $stmt->bind_param("is", $user_id, $today);
    
    $stmt->execute();
    
    $result = $stmt->get_result();
    $nutritionData = [
        'calories' => 0,
        'carbohydrates' => 0,
        'fat' => 0,
        'protein' => 0
    ];
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nutritionData = [
            'calories' => (float)$row['calories'],
            'carbohydrates' => (float)$row['carbohydrates'],
            'fat' => (float)$row['fat'],
            'protein' => (float)$row['protein']
        ];
    }
    $stmt->close();
    
    // 获取今日食物记录
    $stmt = $conn->prepare("
        SELECT 
            ir.user_id,
            ir.record_id,
            ir.food_name,
            ir.amount,
            ir.intake_date,
            f.category,
            (f.calories * ir.amount / 100) AS calories,
            (f.protein * ir.amount / 100) AS protein,
            (f.carbohydrates * ir.amount / 100) AS carbohydrates,
            (f.fat * ir.amount / 100) AS fat
        FROM intake_records ir
        LEFT JOIN foods f ON ir.food_name = f.food_name
        WHERE ir.user_id = ? AND ir.intake_date = ?
        ORDER BY ir.record_id DESC
    ");
    
    if (!$stmt) {
        throw new Exception("数据库准备语句失败: " . $conn->error);
    }
    
    $stmt->bind_param("is", $user_id, $today);
    
    if (!$stmt->execute()) {
        throw new Exception("执行查询失败: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $records = [];
    
    while ($row = $result->fetch_assoc()) {
        $records[] = [
            'user_id' => $row['user_id'],
            'record_id' => $row['record_id'],
            'food_name' => $row['food_name'],
            'amount' => (float)$row['amount'],
            'intake_date' => $row['intake_date'],
            'category' => $row['category'] ?? '未分类',
            'calories' => (float)$row['calories'],
            'protein' => (float)$row['protein'],
            'carbohydrates' => (float)$row['carbohydrates'],
            'fat' => (float)$row['fat']
        ];
    }
    $stmt->close();
    
    echo json_encode([
        "success" => true,
        "data" => $nutritionData,
        "records" => $records
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "获取数据失败: " . $e->getMessage()
    ]);
}
?>