<?php
// test_fast.php - 测试快速版本
session_start();
$_SESSION['user_id'] = 4;

echo "<h2>🚀 测试快速AI版本</h2>";

require 'db_connect.php';
require 'AIAdvisor_fast.php';

echo "<h3>1. 实例化测试</h3>";
try {
    $advisor = new AIAdvisorFast($conn);
    echo "✅ 实例创建成功<br>";
} catch (Exception $e) {
    die("❌ 实例化失败: " . $e->getMessage());
}

echo "<h3>2. 生成AI建议测试</h3>";
echo "开始生成...<br>";

$startTime = microtime(true);

try {
    $result = $advisor->generateAdvice(4);
    $endTime = microtime(true);
    
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "<div style='background:#d4edda; padding:15px; border-radius:5px;'>";
    echo "⏱️ <strong>执行时间: {$executionTime}ms</strong><br>";
    echo "✅ <strong>AI建议生成成功！</strong><br>";
    echo "建议ID: {$result['advice_id']}<br>";
    echo "类型: {$result['type']}<br>";
    echo "AI生成: " . ($result['is_ai_generated'] ? '✅ 是' : '❌ 否') . "<br>";
    echo "提供商: {$result['ai_provider']}<br>";
    echo "<hr>";
    echo "<strong>建议内容：</strong><br>";
    echo nl2br(htmlspecialchars($result['content']));
    echo "</div>";
    
    // 验证
    echo "<h3>3. 验证数据库</h3>";
    $check = $conn->query("SELECT * FROM ai_recommendations WHERE id = " . $result['advice_id']);
    if ($check->num_rows > 0) {
        echo "✅ 数据库记录验证成功<br>";
    }
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; padding:15px; border-radius:5px;'>";
    echo "❌ 生成失败: " . $e->getMessage() . "<br>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🎯 下一步</h3>";
echo "<p>根据执行时间判断：</p>";
echo "<ul>";
if ($executionTime > 10000) {
    echo "<li>⏱️ 执行时间 > 10秒：API调用可能超时</li>";
    echo "<li>建议：使用备用方案或优化网络</li>";
} elseif ($executionTime > 5000) {
    echo "<li>⏱️ 执行时间 5-10秒：API响应较慢</li>";
    echo "<li>建议：增加超时时间或使用缓存</li>";
} else {
    echo "<li>⏱️ 执行时间 < 5秒：API响应正常</li>";
    echo "<li>✅ 可以正常使用</li>";
}
echo "</ul>";

echo "<p><a href='create_fast_endpoint.php'>创建快速接口</a></p>";
?>