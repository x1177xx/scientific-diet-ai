<?php
// diagnose_ai.php - 诊断AI建议问题
require 'db_connect.php';

// 使用第一个用户测试
$user_id = 1; // 修改为你的测试用户ID

echo "<h2>🔍 AI建议问题诊断</h2>";

// 包含AI类
require 'AIAdvisor_simple.php';
$advisor = new AIAdvisorSimple($conn);

echo "<h3>1. 测试用户数据获取</h3>";

try {
    // 1.1 获取用户信息
    echo "<h4>用户基本信息:</h4>";
    $userInfo = $advisor->getUserInfo($user_id);
    if (empty($userInfo)) {
        echo "❌ 获取用户信息失败<br>";
    } else {
        echo "✅ 用户信息获取成功<br>";
        echo "<pre>";
        print_r($userInfo);
        echo "</pre>";
    }
    
    // 1.2 获取今日摄入
    echo "<h4>今日摄入数据:</h4>";
    $todayIntake = $advisor->getTodayIntake($user_id);
    echo "<pre>";
    print_r($todayIntake);
    echo "</pre>";
    
    // 1.3 获取营养目标
    echo "<h4>营养目标:</h4>";
    $nutritionGoals = $advisor->getNutritionGoals($user_id);
    echo "<pre>";
    print_r($nutritionGoals);
    echo "</pre>";
    
    // 1.4 获取历史趋势
    echo "<h4>历史趋势分析:</h4>";
    $historyTrend = $advisor->getHistoryTrend($user_id);
    echo $historyTrend . "<br>";
    
    // 1.5 测试完整用户数据
    echo "<h4>完整用户数据:</h4>";
    $userData = $advisor->getUserData($user_id);
    echo "数据结构检查:<br>";
    echo "user_info: " . (isset($userData['user_info']) ? '存在' : '缺失') . "<br>";
    echo "today_intake: " . (isset($userData['today_intake']) ? '存在' : '缺失') . "<br>";
    echo "nutrition_goals: " . (isset($userData['nutrition_goals']) ? '存在' : '缺失') . "<br>";
    echo "metrics: " . (isset($userData['metrics']) ? '存在' : '缺失') . "<br>";
    
    echo "<h4>详细数据:</h4>";
    echo "<pre>";
    print_r($userData);
    echo "</pre>";
    
    // 1.6 测试个性化建议生成
    echo "<h3>2. 测试个性化建议生成</h3>";
    
    // 手动调用私有方法（需要修改类为public或使用反射）
    echo "<h4>测试生成个性化建议:</h4>";
    
    try {
        $adviceContent = $advisor->generatePersonalizedAdvice($userData);
        echo "✅ 成功生成个性化建议<br>";
        echo "<div style='background:#e8f5e8; padding:15px;'>";
        echo "<strong>生成的建议:</strong><br>";
        echo nl2br(htmlspecialchars($adviceContent));
        echo "</div>";
    } catch (Exception $e) {
        echo "❌ 生成个性化建议失败: " . $e->getMessage() . "<br>";
    }
    
    // 1.7 测试完整生成流程
    echo "<h3>3. 测试完整生成流程</h3>";
    
    echo "调用generateAdvice()方法...<br>";
    $result = $advisor->generateAdvice($user_id);
    
    echo "<h4>结果:</h4>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if (isset($result['is_fallback']) && $result['is_fallback']) {
        echo "<div style='color:red;'><strong>⚠️ 注意：返回的是降级建议！</strong></div>";
        
        // 检查是否有异常被捕获
        echo "<h4>检查异常:</h4>";
        
        // 修改AIAdvisor_simple.php来记录异常
        // 或者查看PHP错误日志
        error_log("AI建议生成使用了降级方案 - 用户ID: $user_id");
    }
    
} catch (Exception $e) {
    echo "<div style='color:red;'><strong>捕获到异常:</strong> " . $e->getMessage() . "</div>";
    echo "<pre>异常追踪: " . $e->getTraceAsString() . "</pre>";
}

echo "<h3>4. 数据库检查</h3>";

// 检查daily_nutrition表
echo "<h4>今日营养记录:</h4>";
$today = date('Y-m-d');
$daily_query = $conn->prepare("
    SELECT * FROM daily_nutrition 
    WHERE user_id = ? AND record_date = ?
");
$daily_query->bind_param("is", $user_id, $today);
$daily_query->execute();
$daily_result = $daily_query->get_result();

if ($daily_result->num_rows > 0) {
    echo "✅ 找到今日营养记录<br>";
    $row = $daily_result->fetch_assoc();
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "❌ 没有今日营养记录<br>";
    echo "用户可能还没有记录今日饮食<br>";
}

// 检查intake_records表
echo "<h4>今日摄入记录:</h4>";
$intake_query = $conn->prepare("
    SELECT COUNT(*) as count, SUM(amount) as total_amount
    FROM intake_records 
    WHERE user_id = ? AND intake_date = ?
");
$intake_query->bind_param("is", $user_id, $today);
$intake_query->execute();
$intake_result = $intake_query->get_result();
$intake_data = $intake_result->fetch_assoc();

echo "今日记录数: " . $intake_data['count'] . "<br>";
echo "总摄入量: " . $intake_data['total_amount'] . "g<br>";

if ($intake_data['count'] == 0) {
    echo "<div style='color:orange;'>⚠️ 用户今天还没有记录任何食物摄入！</div>";
    echo "<p>AI建议需要今日的饮食数据才能生成个性化建议。</p>";
}

$conn->close();
?>