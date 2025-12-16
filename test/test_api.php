<?php
// test_api.php - API连接测试脚本
require 'config.php';

echo "<h2>API连接测试</h2>";

// 测试配置
echo "<h3>配置检查:</h3>";
echo "API密钥长度: " . strlen(MOONSHOT_API_KEY) . "<br>";
echo "API URL: " . MOONSHOT_API_URL . "<br>";
echo "使用模拟模式: " . (USE_MOCK_AI ? '是' : '否') . "<br>";

// 测试API连接
if (!USE_MOCK_AI && !empty(MOONSHOT_API_KEY)) {
    echo "<h3>API连接测试:</h3>";
    
    $data = [
        'model' => 'moonshot-v1-8k',
        'messages' => [
            [
                'role' => 'user',
                'content' => '请回复"测试成功"'
            ]
        ],
        'max_tokens' => 50
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => MOONSHOT_API_URL . '/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . MOONSHOT_API_KEY
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP状态码: " . $httpCode . "<br>";
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        echo "API连接: <span style='color:green'>成功</span><br>";
        echo "响应: " . ($result['choices'][0]['message']['content'] ?? '无内容');
    } else {
        echo "API连接: <span style='color:red'>失败</span><br>";
        echo "错误信息: " . $response;
    }
}
?>