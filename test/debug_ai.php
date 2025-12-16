<?php
// test_moonshot.php - 测试Moonshot API连接
require 'config.php';

echo "<h2>Moonshot API 连接测试</h2>";

// 显示配置
echo "<h3>配置信息：</h3>";
echo "API Key: " . (defined('MOONSHOT_API_KEY') ? substr(MOONSHOT_API_KEY, 0, 10) . '...' : '未定义') . "<br>";
echo "API URL: " . (defined('MOONSHOT_API_URL') ? MOONSHOT_API_URL : '未定义') . "<br>";
echo "模型: " . (defined('AI_MODEL') ? AI_MODEL : '未定义') . "<br>";
echo "使用模拟AI: " . (USE_MOCK_AI ? '是' : '否') . "<br>";

// 直接测试API
echo "<h3>直接API测试：</h3>";
$apiKey = MOONSHOT_API_KEY;
$apiUrl = MOONSHOT_API_URL . '/chat/completions';

$data = [
    'model' => AI_MODEL,
    'messages' => [
        [
            'role' => 'system',
            'content' => '你是一个测试助手。请回复"Moonshot API连接成功!"'
        ],
        [
            'role' => 'user',
            'content' => '测试连接'
        ]
    ],
    'temperature' => 0.7,
    'max_tokens' => 100
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP状态码: $httpCode<br>";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        echo "<span style='color:green;'>✅ API连接成功!</span><br>";
        echo "响应: " . htmlspecialchars($result['choices'][0]['message']['content']);
    } else {
        echo "<span style='color:red;'>❌ API响应格式错误</span><br>";
        echo "响应内容: <pre>" . htmlspecialchars($response) . "</pre>";
    }
} else {
    echo "<span style='color:red;'>❌ API连接失败</span><br>";
    echo "错误信息: " . htmlspecialchars($error) . "<br>";
    echo "响应内容: <pre>" . htmlspecialchars($response) . "</pre>";
}

echo "<hr><h3>下一步：</h3>";
echo "<ol>
<li><a href='create_ai_tables.php'>更新数据库表结构</a></li>
<li><a href='debug_ai.php'>测试AI功能</a></li>
<li><a href='dashboard.php'>返回仪表盘测试</a></li>
</ol>";
?>