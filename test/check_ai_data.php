<?php
// check_ai_data.php - 快速检查AI数据
require 'db_connect.php';

$user_id = 1; // 修改为你的用户ID
$today = date('Y-m-d');

echo "<h2>📊 AI数据快速检查</h2>";

// 1. 检查用户表
echo "<h3>1. 用户表数据</h3>";
$user_query = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
if ($user = $user_query->fetch_assoc()) {
    echo "✅ 用户存在: " . $user['username'] . "<br>";
    echo "性别: " . ($user['gender'] ?? '未设置') . "<br>";
    echo "年龄: " . ($user['age'] ?? '未设置') . "<br>";
    echo "身高: " . ($user['height'] ?? '未设置') . "cm<br>";
    echo "体重: " . ($user['weight'] ?? '未设置') . "kg<br>";
} else {
    echo "❌ 用户不存在<br>";
}

// 2. 检查daily_nutrition
echo "<h3>2. 今日营养记录 (daily_nutrition)</h3>";
$daily_query = $conn->query("
    SELECT * FROM daily_nutrition 
    WHERE user_id = $user_id AND record_date = '$today'
");
if ($daily_query->num_rows > 0) {
    $daily = $daily_query->fetch_assoc();
    echo "✅ 有今日营养记录<br>";
    echo "热量: " . $daily['calories'] . "kcal<br>";
    echo "蛋白质: " . $daily['protein'] . "g<br>";
    echo "碳水: " . $daily['carbohydrates'] . "g<br>";
    echo "脂肪: " . $daily['fat'] . "g<br>";
} else {
    echo "❌ 没有今日营养记录<br>";
    echo "用户可能还没有记录今日饮食<br>";
}

// 3. 检查intake_records
echo "<h3>3. 今日摄入记录 (intake_records)</h3>";
$intake_query = $conn->query("
    SELECT * FROM intake_records 
    WHERE user_id = $user_id AND intake_date = '$today'
");
if ($intake_query->num_rows > 0) {
    echo "✅ 有" . $intake_query->num_rows . "条摄入记录<br>";
    while ($row = $intake_query->fetch_assoc()) {
        echo "- {$row['food_name']}: {$row['amount']}g<br>";
    }
} else {
    echo "❌ 没有今日摄入记录<br>";
}

// 4. 检查nutrition_recommendations
echo "<h3>4. 营养目标 (nutrition_recommendations)</h3>";
$goal_query = $conn->query("
    SELECT * FROM nutrition_recommendations 
    WHERE user_id = $user_id
");
if ($goal_query->num_rows > 0) {
    $goal = $goal_query->fetch_assoc();
    echo "✅ 有营养目标<br>";
    echo "热量目标: " . $goal['calories'] . "kcal<br>";
    echo "蛋白质目标: " . $goal['protein'] . "g<br>";
    echo "碳水目标: " . $goal['carbohydrates'] . "g<br>";
    echo "脂肪目标: " . $goal['fat'] . "g<br>";
} else {
    echo "⚠️ 没有预设营养目标，将根据用户信息计算<br>";
}

// 5. 测试AIAdvisor_simple的方法
echo "<h3>5. 测试AIAdvisor_simple方法</h3>";

require 'AIAdvisor_simple.php';
$advisor = new AIAdvisorSimple($conn);

// 测试各个方法
echo "<h4>5.1 getUserInfo()</h4>";
$userInfo = $advisor->getUserInfo($user_id);
echo "<pre>";
print_r($userInfo);
echo "</pre>";

echo "<h4>5.2 getTodayIntake()</h4>";
$todayIntake = $advisor->getTodayIntake($user_id);
echo "<pre>";
print_r($todayIntake);
echo "</pre>";

echo "<h4>5.3 getNutritionGoals()</h4>";
$nutritionGoals = $advisor->getNutritionGoals($user_id);
echo "<pre>";
print_r($nutritionGoals);
echo "</pre>";

// 6. 查看PHP错误日志
echo "<h3>6. 错误日志检查</h3>";
echo "请查看以下位置的错误日志：<br>";
echo "- /var/log/apache2/error.log (Apache)<br>";
echo "- /var/log/nginx/error.log (Nginx)<br>";
echo "- php_error.log (PHP-FPM)<br>";
echo "或运行: <code>tail -f /var/log/apache2/error.log</code>";

$conn->close();
?>