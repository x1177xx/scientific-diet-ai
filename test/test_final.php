<?php
// test_final.php - 测试最终版本
session_start();
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'test';

echo "<h2>测试AIAdvisorFinal</h2>";

require 'db_connect.php';
require 'AIAdvisor_final.php';

echo "<h3>1. 实例化测试</h3>";
try {
    $advisor = new AIAdvisorFinal($conn);
    echo "✅ AIAdvisorFinal实例创建成功<br>";
} catch (Exception $e) {
    echo "❌ 实例化失败: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>2. 获取用户数据测试</h3>";
try {
    $userData = $advisor->getUserData(4);
    if ($userData) {
        echo "✅ 用户数据获取成功:<br>";
        echo "性别: {$userData['gender']}<br>";
        echo "年龄: {$userData['age']}<br>";
        echo "身高: {$userData['height']}cm<br>";
        echo "体重: {$userData['weight']}kg<br>";
    } else {
        echo "❌ 用户数据获取失败<br>";
    }
} catch (Exception $e) {
    echo "❌ 获取用户数据失败: " . $e->getMessage() . "<br>";
}

echo "<h3>3. 获取今日摄入测试</h3>";
try {
    $intakeData = $advisor->getTodayIntake(4, date('Y-m-d'));
    echo "✅ 今日摄入数据获取成功:<br>";
    echo "热量: {$intakeData['calories']}kcal<br>";
    echo "蛋白质: {$intakeData['protein']}g<br>";
    echo "碳水: {$intakeData['carbohydrates']}g<br>";
    echo "脂肪: {$intakeData['fat']}g<br>";
} catch (Exception $e) {
    echo "❌ 获取摄入数据失败: " . $e->getMessage() . "<br>";
}

echo "<h3>4. 生成完整AI建议测试</h3>";
try {
    echo "开始生成AI建议...<br>";
    
    $startTime = microtime(true);
    $result = $advisor->generateAdvice(4);
    $endTime = microtime(true);
    
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "<div style='background:#d4edda; padding:15px; border-radius:5px;'>";
    echo "🎉 <strong>AI建议生成成功！</strong><br>";
    echo "执行时间: {$executionTime}ms<br>";
    echo "建议ID: {$result['advice_id']}<br>";
    echo "类型: {$result['type']}<br>";
    echo "AI生成: " . ($result['is_ai_generated'] ? '✅ 是' : '❌ 否') . "<br>";
    echo "提供商: {$result['ai_provider']}<br>";
    echo "生成时间: {$result['generated_at']}<br>";
    echo "<hr>";
    echo "<strong>建议内容：</strong><br>";
    echo nl2br(htmlspecialchars($result['content']));
    echo "</div>";
    
    // 验证数据库保存
    echo "<h3>5. 验证数据库保存</h3>";
    $check = $conn->query("SELECT * FROM ai_recommendations WHERE id = " . $result['advice_id']);
    if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        echo "✅ 数据库记录验证成功！<br>";
        echo "记录ID: {$row['id']}<br>";
        echo "is_ai_generated: {$row['is_ai_generated']}<br>";
        echo "ai_provider: {$row['ai_provider']}<br>";
    } else {
        echo "❌ 数据库记录未找到<br>";
    }
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; padding:15px; border-radius:5px;'>";
    echo "❌ AI建议生成失败<br>";
    echo "错误: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🎯 下一步</h3>";
echo "<p>如果上述测试成功，请：</p>";
echo "<ol>
<li><a href='create_final_interface.php'>创建最终接口文件</a></li>
<li><a href='dashboard.php' target='_blank'>测试仪表盘</a></li>
</ol>";

$conn->close();
?>