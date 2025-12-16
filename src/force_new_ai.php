<?php
// force_new_ai.php - 从dashboard获取统一数据
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

// ==================== 1. 获取dashboard计算的目标值 ====================
if (!isset($_SESSION['nutrition_goals']) || !isset($_SESSION['user_info'])) {
    // 如果session中没有数据，可能是直接访问了AI页面，尝试重新计算
    $userQuery = $conn->prepare("SELECT gender, age, height, weight FROM users WHERE user_id = ?");
    $userQuery->bind_param("i", $userId);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    $user = $userResult->fetch_assoc();
    $userQuery->close();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => '用户数据不存在']);
        exit();
    }
    
    // 使用与dashboard相同的函数计算
    function calculateNutritionGoals($user) {
        if ($user['gender'] == 'male' || $user['gender'] == '男') {
            $bmr = 10 * $user['weight'] + 6.25 * $user['height'] - 5 * $user['age'] + 5;
        } else {
            $bmr = 10 * $user['weight'] + 6.25 * $user['height'] - 5 * $user['age'] - 161;
        }
        
        $activity_factor = 1.55;
        $daily_calories = round($bmr * $activity_factor);
        
        return [
            'caloriesGoal' => $daily_calories,
            'proteinGoal' => round($user['weight'] * 1.8),
            'carbohydratesGoal' => round($daily_calories * 0.5 / 4),
            'fatGoal' => round($daily_calories * 0.3 / 9)
        ];
    }
    
    $nutritionGoals = calculateNutritionGoals($user);
    
    // 存储到session以便下次使用
    $_SESSION['nutrition_goals'] = $nutritionGoals;
    $_SESSION['user_info'] = $user;
    
    $dataSource = 'recalculated';
} else {
    // 直接从session获取dashboard计算好的数据
    $nutritionGoals = $_SESSION['nutrition_goals'];
    $user = $_SESSION['user_info'];
    $dataSource = 'dashboard_session';
}

$goalCalories = $nutritionGoals['caloriesGoal'];

// ==================== 2. 获取今日实际摄入量 ====================
// 使用预处理语句防止SQL注入
$stmt = $conn->prepare("
    SELECT 
        SUM(f.calories * ir.amount / 100) as calories,
        SUM(f.protein * ir.amount / 100) as protein,
        SUM(f.carbohydrates * ir.amount / 100) as carbs,
        SUM(f.fat * ir.amount / 100) as fat
    FROM intake_records ir
    JOIN foods f ON ir.food_name = f.food_name
    WHERE ir.user_id = ? AND ir.intake_date = ?
");

$stmt->bind_param("is", $userId, $today);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

$todayCalories = floatval($result['calories'] ?? 0);
$todayProtein = floatval($result['protein'] ?? 0);
$todayCarbs = floatval($result['carbs'] ?? 0);
$todayFat = floatval($result['fat'] ?? 0);

// ==================== 3. 生成AI建议（使用统一的目标值） ====================
$difference = $todayCalories - $goalCalories;
$percentage = $goalCalories > 0 ? round($todayCalories / $goalCalories * 100, 1) : 0;

if ($todayCalories > $goalCalories) {
    // 超标情况
    $excessPercent = round(($todayCalories - $goalCalories) / $goalCalories * 100);
    
     if ($excessPercent > 50) { // 超过50%
        $advice = "**⚠️ 热量严重超标！**\n\n" .
                 "**数据分析**：\n" .
                 "- 今日摄入：{$todayCalories}kcal\n" .
                 "- 推荐目标：{$goalCalories}kcal\n" .
                 "- 超出：{$excess}kcal（超过目标{$excessPercent}%）\n\n" .
                 "**紧急建议**：\n" .
                 "1. 立即停止高热量食物\n" .
                 "2. 今日剩余时间只喝水/无糖茶\n" .
                 "3. 增加90分钟高强度有氧运动\n" .
                 "4. 明天严格控制饮食\n\n" .
                 "**明日目标**：控制在" . round($goalCalories * 0.8) . "kcal以内";
        $type = 'exercise';
    } elseif ($excessPercent > 20) { // 超过20%
        $advice = "**热量摄入超标**\n\n" .
                 "**数据分析**：\n" .
                 "- 今日摄入：{$todayCalories}kcal\n" .
                 "- 推荐目标：{$goalCalories}kcal\n" .
                 "- 超出：{$excess}kcal\n\n" .
                 "**具体建议**：\n" .
                 "1. 减少主食摄入\n" .
                 "2. 避免油炸和高糖食物\n" .
                 "3. 增加蔬菜摄入\n" .
                 "4. 增加60分钟有氧运动\n\n" .
                 "**明日目标**：控制在" . round($goalCalories * 0.9) . "kcal以内";
        $type = 'exercise';
    } else { // 略高
        $advice = "**热量摄入略高**\n\n" .
                 "**数据分析**：\n" .
                 "- 今日摄入：{$todayCalories}kcal\n" .
                 "- 推荐目标：{$goalCalories}kcal\n" .
                 "- 超出：{$excess}kcal\n\n" .
                 "**微调建议**：\n" .
                 "1. 适当减少晚餐份量\n" .
                 "2. 增加30分钟有氧运动\n" .
                 "3. 多喝水促进代谢\n\n" .
                 "**明日目标**：控制在{$goalCalories}kcal以内";
        $type = 'exercise';
    }
    
} elseif ($todayCalories < $goalCalories * 0.8) {
    // 严重不足（低于目标80%）
    $deficit = $goalCalories - $todayCalories;
    $deficitPercent = round($deficit / $goalCalories * 100);
    
    $advice = "**热量摄入不足**\n\n" .
             "**数据分析**：\n" .
             "- 今日摄入：{$todayCalories}kcal\n" .
             "- 推荐目标：{$goalCalories}kcal\n" .
             "- 缺少：{$deficit}kcal（低于目标{$deficitPercent}%）\n\n" .
             "**具体建议**：\n" .
             "1. 增加主食摄入\n" .
             "2. 补充健康脂肪（坚果、牛油果）\n" .
             "3. 保证蛋白质充足\n" .
             "4. 考虑加餐\n\n" .
             "**推荐运动**：力量训练为主\n\n" .
             "**明日目标**：达到" . round($goalCalories * 0.9) . "kcal";
    $type = 'diet';
    
} elseif ($todayCalories < $goalCalories * 0.9) {
    // 略低（低于目标90%）
    $deficit = $goalCalories - $todayCalories;
    
    $advice = "**热量摄入略低**\n\n" .
             "**数据分析**：\n" .
             "- 今日摄入：{$todayCalories}kcal\n" .
             "- 推荐目标：{$goalCalories}kcal\n" .
             "- 缺少：{$deficit}kcal\n\n" .
             "**微调建议**：\n" .
             "1. 适当增加主食\n" .
             "2. 补充蛋白质\n\n" .
             "**明日目标**：达到{$goalCalories}kcal";
    $type = 'diet';
    
} else {
    // 均衡（在目标90%-110%之间）
    $percent = round($todayCalories / $goalCalories * 100);
    
    $advice = "**✅ 饮食均衡**\n\n" .
             "**数据分析**：\n" .
             "- 今日摄入：{$todayCalories}kcal\n" .
             "- 推荐目标：{$goalCalories}kcal\n" .
             "- 完成度：{$percent}%\n\n" .
             "**保持建议**：\n" .
             "1. 维持当前饮食结构\n" .
             "2. 多样化食物选择\n" .
             "3. 规律运动作息\n\n" .
             "**明日目标**：继续保持！";
    $type = 'general';
}


// ==================== 4. 保存建议到数据库 ====================
// 先删除今天的旧建议
$deleteStmt = $conn->prepare("DELETE FROM ai_recommendations WHERE user_id = ? AND recommendation_date = ?");
$deleteStmt->bind_param("is", $userId, $today);
$deleteStmt->execute();
$deleteStmt->close();

// 插入新建议
$insertStmt = $conn->prepare("INSERT INTO ai_recommendations (user_id, recommendation_date, content, type) VALUES (?, ?, ?, ?)");
$insertStmt->bind_param("isss", $userId, $today, $advice, $type);

if ($insertStmt->execute()) {
    $adviceId = $insertStmt->insert_id;
    $insertStmt->close();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'advice_id' => $adviceId,
            'content' => $advice,
            'type' => $type,
            'generated_at' => date('Y-m-d H:i:s'),
            'data_source' => $dataSource
        ],
        'nutrition_info' => [
            'actual' => [
                'calories' => $todayCalories,
                'protein' => $todayProtein,
                'carbohydrates' => $todayCarbs,
                'fat' => $todayFat
            ],
            'goals' => $nutritionGoals,
            'user_info' => $user,
            'difference' => $difference,
            'percentage' => $percentage,
            'calculated_at' => $_SESSION['dashboard_calculated_at'] ?? '未记录'
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => '保存失败',
        'error' => $conn->error,
        'debug' => [
            'goalCalories' => $goalCalories,
            'todayCalories' => $todayCalories,
            'data_source' => $dataSource
        ]
    ]);
}
?>