<?php
// test_ai.php - 测试DeepSeek API
require 'AIAdvisor.php';
require 'db_connect.php';

echo "<h1>DeepSeek API 测试</h1>";

try {
    $advisor = new AIAdvisor($conn);
    
    // 测试配置
    echo "<h3>配置信息：</h3>";
    echo "API密钥: " . (defined('DEEPSEEK_API_KEY') ? '已设置' : '未设置') . "<br>";
    echo "使用模拟AI: " . (defined('USE_MOCK_AI') && USE_MOCK_AI ? '是' : '否') . "<br>";
    
    // 测试API连接
    echo "<h3>测试API连接：</h3>";
    if (defined('USE_MOCK_AI') && USE_MOCK_AI) {
        echo "⚠️ 当前使用模拟模式，不会调用真实API<br>";
        echo "要启用真实API，请编辑config.php，确保DEEPSEEK_API_KEY有效且USE_MOCK_AI为false<br>";
    }
    
    // 测试生成建议（使用测试用户ID 1）
    echo "<h3>测试生成建议：</h3>";
    $result = $advisor->generateAdvice(1); // 使用用户ID 1进行测试
    echo "<div style='background:#f0f0f0; padding:15px; border-radius:5px;'>";
    echo "<strong>建议内容：</strong><br>";
    echo nl2br(htmlspecialchars($result['content']));
    echo "</div>";
    
    echo "<h3>建议详情：</h3>";
    echo "类型: " . $result['type'] . "<br>";
    echo "是否AI生成: " . ($result['is_ai_generated'] ? '是' : '否') . "<br>";
    echo "生成时间: " . $result['generated_at'] . "<br>";
    
} catch (Exception $e) {
    echo "<div style='color:red; padding:10px; background:#ffe6e6;'>";
    echo "<h3>错误：</h3>";
    echo $e->getMessage() . "<br>";
    echo "文件: " . $e->getFile() . " 行: " . $e->getLine();
    echo "</div>";
}

echo "<hr>";
echo "<a href='dashboard.php'>返回仪表盘</a> | ";
echo "<a href='ai_admin.php'>查看AI建议管理</a>";
?>