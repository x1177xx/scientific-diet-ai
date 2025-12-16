<?php
// test_emergency.php - 直接测试
session_start();
$_SESSION['user_id'] = 4;

echo "<h2>紧急修复测试</h2>";

// 开启所有错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. 测试数据库连接
echo "<h3>1. 测试数据库连接</h3>";
require 'db_connect.php';

if ($conn->connect_error) {
    die("❌ 数据库连接失败: " . $conn->connect_error);
}

echo "✅ 数据库连接成功<br>";
echo "服务器版本: " . $conn->server_info . "<br>";

// 2. 测试用户查询
echo "<h3>2. 测试用户查询</h3>";
$userId = 4;
$sql = "SELECT * FROM users WHERE user_id = $userId";
$result = $conn->query($sql);

if (!$result) {
    echo "❌ 查询失败: " . $conn->error . "<br>";
} elseif ($result->num_rows === 0) {
    echo "❌ 用户ID {$userId} 不存在<br>";
    
    // 检查有哪些用户
    echo "检查现有用户:<br>";
    $allUsers = $conn->query("SELECT user_id, username FROM users LIMIT 5");
    while ($user = $allUsers->fetch_assoc()) {
        echo "- ID: {$user['user_id']}, 用户名: {$user['username']}<br>";
    }
} else {
    $user = $result->fetch_assoc();
    echo "✅ 用户存在:<br>";
    echo "ID: {$user['user_id']}<br>";
    echo "用户名: {$user['username']}<br>";
    echo "性别: {$user['gender']}<br>";
    echo "年龄: {$user['age']}<br>";
    echo "身高: {$user['height']}cm<br>";
    echo "体重: {$user['weight']}kg<br>";
}

// 3. 测试AIAdvisorEmergency
echo "<h3>3. 测试AIAdvisorEmergency</h3>";
require 'AIAdvisor_emergency.php';

try {
    $advisor = new AIAdvisorEmergency($conn);
    echo "✅ 实例创建成功<br>";
    
    echo "开始生成建议...<br>";
    
    $startTime = microtime(true);
    $result = $advisor->generateAdvice($userId);
    $endTime = microtime(true);
    
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "<div style='background:#d4edda; padding:15px; border-radius:5px;'>";
    echo "🎉 <strong>AI建议生成成功！</strong><br>";
    echo "执行时间: {$executionTime}ms<br>";
    echo "建议ID: {$result['advice_id']}<br>";
    echo "类型: {$result['type']}<br>";
    echo "AI生成: " . ($result['is_ai_generated'] ? '✅ 是' : '❌ 否') . "<br>";
    echo "提供商: {$result['ai_provider']}<br>";
    echo "<hr>";
    echo "<strong>建议内容：</strong><br>";
    echo nl2br(htmlspecialchars($result['content']));
    echo "</div>";
    
    // 验证数据库
    echo "<h3>4. 验证数据库保存</h3>";
    $check = $conn->query("SELECT * FROM ai_recommendations WHERE id = " . $result['advice_id']);
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        echo "✅ 数据库记录验证成功！<br>";
        echo "ID: {$row['id']}<br>";
        echo "用户ID: {$row['user_id']}<br>";
        echo "日期: {$row['recommendation_date']}<br>";
        echo "类型: {$row['type']}<br>";
        echo "AI生成: {$row['is_ai_generated']}<br>";
        echo "提供商: {$row['ai_provider']}<br>";
    } else {
        echo "❌ 数据库记录未找到<br>";
    }
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; padding:15px; border-radius:5px;'>";
    echo "❌ 测试失败<br>";
    echo "错误: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

// 4. 测试历史记录
echo "<h3>5. 测试历史记录</h3>";
try {
    $history = $advisor->getHistory($userId, 3);
    echo "获取到 " . count($history) . " 条历史记录<br>";
    
    if (count($history) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>日期</th><th>类型</th><th>AI生成</th><th>提供商</th></tr>";
        foreach ($history as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['recommendation_date']}</td>";
            echo "<td>{$item['type']}</td>";
            echo "<td>" . ($item['is_ai_generated'] ? '是' : '否') . "</td>";
            echo "<td>{$item['ai_provider']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "历史记录获取失败: " . $e->getMessage() . "<br>";
}

$conn->close();

echo "<hr>";
echo "<h3>🎯 立即操作</h3>";
echo "<p>如果上述测试成功：</p>";
echo "<ol>
<li><a href='create_simple_interface.php'>创建简单接口</a></li>
<li><a href='dashboard.php' target='_blank'>测试仪表盘</a></li>
</ol>";
?>