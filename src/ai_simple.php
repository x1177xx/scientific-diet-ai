<?php
// ai_simple.php - 简化的AI建议接口
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

// 开启错误显示
ini_set('display_errors', 0);
error_reporting(0);

try {
    // 1. 获取用户数据
    $stmt = $conn->prepare("SELECT gender, age, height, weight FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        throw new Exception('用户不存在');
    }
    
    // 2. 计算营养目标
    if ($user['gender'] == 'male' || $user['gender'] == '男') {
        $bmr = 10 * $user['weight'] + 6.25 * $user['height'] - 5 * $user['age'] + 5;
    } else {
        $bmr = 10 * $user['weight'] + 6.25 * $user['height'] - 5 * $user['age'] - 161;
    }
    
    $daily_calories = round($bmr * 1.55);
    $proteinGoal = round($user['weight'] * 1.8);
    $carbsGoal = round($daily_calories * 0.5 / 4);
    $fatGoal = round($daily_calories * 0.3 / 9);
    
    // 3. 获取今日摄入
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(f.calories * ir.amount / 100), 0) as calories,
            COALESCE(SUM(f.protein * ir.amount / 100), 0) as protein,
            COALESCE(SUM(f.carbohydrates * ir.amount / 100), 0) as carbs,
            COALESCE(SUM(f.fat * ir.amount / 100), 0) as fat
        FROM intake_records ir
        JOIN foods f ON ir.food_name = f.food_name
        WHERE ir.user_id = ? AND ir.intake_date = ?
    ");
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $intake = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $calories = floatval($intake['calories'] ?? 0);
    $protein = floatval($intake['protein'] ?? 0);
    $carbs = floatval($intake['carbs'] ?? 0);
    $fat = floatval($intake['fat'] ?? 0);
    
    // 4. 调用Moonshot API
    $apiKey = 'sk-74YajJnhmgC5nkEQzddNalehtZgKfNzAmz4s2ZAnoVh1Jv7t';
    $prompt = "用户信息：{$user['gender']}，{$user['age']}岁，身高{$user['height']}cm，体重{$user['weight']}kg。
今日摄入：热量{$calories}kcal（目标{$daily_calories}kcal），蛋白质{$protein}g（目标{$proteinGoal}g），碳水{$carbs}g（目标{$carbsGoal}g），脂肪{$fat}g（目标{$fatGoal}g）。
请提供营养建议：1.总体评价 2.具体建议 3.明日目标。用中文回答，简洁明了。";
    
    $data = [
        'model' => 'moonshot-v1-8k',
        'messages' => [
            [
                'role' => 'system',
                'content' => '你是专业的营养师和健身教练，用中文提供建议。'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 800
    ];
    
    $ch = curl_init('https://api.moonshot.cn/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("API请求失败: HTTP $httpCode");
    }
    
    $result = json_decode($response, true);
    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception("API响应格式错误");
    }
    
    $advice = $result['choices'][0]['message']['content'];
    
    // 5. 分析类型
    $type = 'general';
    if (strpos($advice, '运动') !== false) $type = 'exercise';
    if (strpos($advice, '饮食') !== false) $type = 'diet';
    
    // 6. 保存到数据库
    $conn->query("DELETE FROM ai_recommendations WHERE user_id = $userId AND recommendation_date = '$today'");
    
    $stmt = $conn->prepare("
        INSERT INTO ai_recommendations 
        (user_id, recommendation_date, content, type, is_ai_generated, ai_provider) 
        VALUES (?, ?, ?, ?, 1, 'moonshot')
    ");
    $stmt->bind_param("isss", $userId, $today, $advice, $type);
    $stmt->execute();
    $adviceId = $stmt->insert_id;
    $stmt->close();
    
    // 7. 返回结果
    echo json_encode([
        'success' => true,
        'message' => 'AI建议生成成功',
        'data' => [
            'advice_id' => $adviceId,
            'content' => $advice,
            'type' => $type,
            'is_ai_generated' => true,
            'ai_provider' => 'moonshot',
            'generated_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    // 如果API失败，使用模拟建议
    $mockAdvice = "今日营养摄入分析：\n";
    $mockAdvice .= "热量：{$calories}kcal（目标{$daily_calories}kcal）\n";
    
    if ($calories > $daily_calories * 1.1) {
        $mockAdvice .= "热量略高，建议减少晚餐主食，增加30分钟有氧运动。";
        $type = 'exercise';
    } elseif ($calories < $daily_calories * 0.9) {
        $mockAdvice .= "热量不足，建议增加蛋白质摄入，如鸡胸肉或鸡蛋。";
        $type = 'diet';
    } else {
        $mockAdvice .= "饮食均衡，继续保持当前饮食结构！";
        $type = 'general';
    }
    
    // 保存模拟建议
    $conn->query("DELETE FROM ai_recommendations WHERE user_id = $userId AND recommendation_date = '$today'");
    $stmt = $conn->prepare("
        INSERT INTO ai_recommendations 
        (user_id, recommendation_date, content, type, is_ai_generated, ai_provider) 
        VALUES (?, ?, ?, ?, 0, 'mock')
    ");
    $stmt->bind_param("isss", $userId, $today, $mockAdvice, $type);
    $stmt->execute();
    $adviceId = $stmt->insert_id;
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => '使用模拟建议（API失败: ' . $e->getMessage() . '）',
        'data' => [
            'advice_id' => $adviceId,
            'content' => $mockAdvice,
            'type' => $type,
            'is_ai_generated' => false,
            'ai_provider' => 'mock',
            'generated_at' => date('Y-m-d H:i:s')
        ]
    ]);
}

$conn->close();
?>