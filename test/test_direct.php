<?php
// test_direct.php - 直接测试
echo "<h2>直接API测试</h2>";

// 1. 测试纯API调用
echo "<h3>1. 测试纯API调用</h3>";
$apiKey = 'sk-74YajJnhmgC5nkEQzddNalehtZgKfNzAmz4s2ZAnoVh1Jv7t';

$data = [
    'model' => 'moonshot-v1-8k',
    'messages' => [
        [
            'role' => 'user',
            'content' => '你好，请简单介绍一下你自己。用中文回答。'
        ]
    ],
    'temperature' => 0.7,
    'max_tokens' => 100
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
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP状态码: $httpCode<br>";
echo "错误信息: " . ($error ?: '无') . "<br>";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "<div style='background:#d4edda; padding:15px; border-radius:5px;'>";
    echo "✅ API调用成功！<br>";
    echo "响应: " . htmlspecialchars($result['choices'][0]['message']['content']);
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da; padding:15px; border-radius:5px;'>";
    echo "❌ API调用失败<br>";
    echo "响应内容: <pre>" . htmlspecialchars($response) . "</pre>";
    echo "</div>";
}

// 2. 测试数据库
echo "<h3>2. 测试数据库</h3>";
require 'db_connect.php';
echo "数据库连接: " . ($conn->ping() ? "✅ 成功" : "❌ 失败") . "<br>";

// 3. 测试修复版AIAdvisor
echo "<h3>3. 测试修复版AIAdvisor</h3>";
require 'AIAdvisor_fixed.php';

try {
    $advisor = new AIAdvisor_fixed($conn);
    echo "AIAdvisor_fixed实例: ✅ 创建成功<br>";
    
    // 使用一个存在的用户ID
    $result = $advisor->generateAdvice(4);
    
    echo "<div style='background:#d4edda; padding:15px; border-radius:5px;'>";
    echo "✅ 建议生成成功！<br>";
    echo "建议ID: {$result['advice_id']}<br>";
    echo "类型: {$result['type']}<br>";
    echo "AI生成: " . ($result['is_ai_generated'] ? '是' : '否') . "<br>";
    echo "提供商: {$result['ai_provider']}<br>";
    echo "内容预览: " . htmlspecialchars(substr($result['content'], 0, 100)) . "...";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; padding:15px; border-radius:5px;'>";
    echo "❌ 测试失败: " . $e->getMessage() . "<br>";
    echo "追踪: <pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<hr><h3>调试信息</h3>";
echo "<pre>";
echo "PHP版本: " . PHP_VERSION . "\n";
echo "CURL支持: " . (function_exists('curl_init') ? '是' : '否') . "\n";
echo "内存限制: " . ini_get('memory_limit') . "\n";
echo "最大执行时间: " . ini_get('max_execution_time') . "\n";
echo "</pre>";

echo "<hr><a href='dashboard.php'>返回仪表盘测试</a>";
?>