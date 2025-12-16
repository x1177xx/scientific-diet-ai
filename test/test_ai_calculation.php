<?php
require 'db_connect.php';

$user_id = 1; // 修改为你的用户ID
$today = date('Y-m-d');

echo "<h2>🧮 AI计算逻辑测试</h2>";

// 获取用户数据
$user_query = $conn->query("
    SELECT gender, age, height, weight 
    FROM users WHERE user_id = $user_id
");
$user = $user_query->fetch_assoc();

echo "<h3>用户数据:</h3>";
echo "<pre>";
print_r($user);
echo "</pre>";

// 测试计算逻辑
echo "<h3>测试营养目标计算:</h3>";

if ($user['gender'] == 'male') {
    $bmr = 10 * $user['weight'] + 6.25 * $user['height'] - 5 * $user['age'] + 5;
} else {
    $bmr = 10 * $user['weight'] + 6.25 * $user['height'] - 5 * $user['age'] - 161;
}

$activity_factor = 1.55;
$daily_calories = round($bmr * $activity_factor);
$protein = round($user['weight'] * 1.8);
$carbs = round($daily_calories * 0.5 / 4);
$fat = round($daily_calories * 0.3 / 9);

echo "BMR: $bmr kcal<br>";
echo "活动系数: $activity_factor<br>";
echo "每日热量目标: $daily_calories kcal<br>";
echo "蛋白质目标: $protein g<br>";
echo "碳水化合物目标: $carbs g<br>";
echo "脂肪目标: $fat g<br>";

// 获取今日实际摄入
$daily_query = $conn->query("
    SELECT calories, protein, carbohydrates, fat 
    FROM daily_nutrition 
    WHERE user_id = $user_id AND record_date = '$today'
");
$today_intake = $daily_query->fetch_assoc();

echo "<h3>今日实际摄入:</h3>";
echo "热量: {$today_intake['calories']} kcal<br>";
echo "蛋白质: {$today_intake['protein']} g<br>";
echo "碳水: {$today_intake['carbohydrates']} g<br>";
echo "脂肪: {$today_intake['fat']} g<br>";

// 计算百分比
echo "<h3>完成度计算:</h3>";
$cal_percent = round($today_intake['calories'] / $daily_calories * 100);
$pro_percent = round($today_intake['protein'] / $protein * 100);

echo "热量完成度: $cal_percent%<br>";
echo "蛋白质完成度: $pro_percent%<br>";

// 计算BMI
$height_m = $user['height'] / 100;
$bmi = round($user['weight'] / ($height_m * $height_m), 1);

echo "BMI: $bmi<br>";

// 根据数据判断应该生成什么类型的建议
echo "<h3>AI建议类型判断:</h3>";

if ($cal_percent > 120) {
    echo "应该生成: 热量严重超标建议<br>";
    $expected_type = 'exercise';
} elseif ($cal_percent > 105) {
    echo "应该生成: 热量略高建议<br>";
    $expected_type = 'exercise';
} elseif ($cal_percent < 80) {
    echo "应该生成: 热量不足建议<br>";
    $expected_type = 'diet';
} elseif ($bmi > 24) {
    echo "应该生成: 减重建议<br>";
    $expected_type = 'diet';
} elseif ($bmi < 18.5) {
    echo "应该生成: 增重建议<br>";
    $expected_type = 'diet';
} else {
    echo "应该生成: 均衡建议<br>";
    $expected_type = 'general';
}

echo "预期建议类型: $expected_type<br>";

// 测试AIAdvisor_simple的方法
echo "<h3>测试AIAdvisor_simple的getUserData()方法:</h3>";

require 'AIAdvisor_simple.php';
$advisor = new AIAdvisorSimple($conn);

try {
    $userData = $advisor->getUserData($user_id);
    echo "<h4>返回的数据结构:</h4>";
    echo "<pre>";
    print_r($userData);
    echo "</pre>";
    
    // 测试生成建议
    echo "<h3>测试generatePersonalizedAdvice()方法:</h3>";
    
    // 由于是私有方法，我们需要通过反射调用
    $reflection = new ReflectionClass('AIAdvisorSimple');
    $method = $reflection->getMethod('generatePersonalizedAdvice');
    $method->setAccessible(true);
    
    $advice = $method->invoke($advisor, $userData);
    echo "<div style='background:#e8f5e8; padding:15px;'>";
    echo "<strong>生成的建议:</strong><br>";
    echo nl2br(htmlspecialchars($advice));
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color:red;'>错误: " . $e->getMessage() . "</div>";
    echo "<pre>追踪: " . $e->getTraceAsString() . "</pre>";
}

$conn->close();
?>