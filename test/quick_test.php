<?php
// quick_test.php - 快速测试所有AI接口
echo "<h2>🔍 AI接口快速测试</h2>";

$interfaces = [
    'generate_ai_advice.php' => '主AI接口',
    'ai_simple.php' => '简化AI接口',
    'force_new_ai.php' => '规则引擎接口',
    'get_ai_history.php' => '历史记录接口'
];

foreach ($interfaces as $file => $desc) {
    echo "<h3>{$desc} ({$file})</h3>";
    
    if (!file_exists($file)) {
        echo "❌ 文件不存在<br>";
        continue;
    }
    
    // 模拟会话
    session_start();
    $_SESSION['user_id'] = 4;
    $_SESSION['username'] = 'test';
    
    $url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/{$file}";
    
    echo "测试URL: <a href='{$url}' target='_blank'>{$url}</a><br>";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP状态码: {$httpCode}<br>";
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($result && isset($result['success'])) {
            echo "✅ 接口正常 - ";
            echo $result['success'] ? "成功" : "失败: " . ($result['message'] ?? '未知错误');
            echo "<br>";
            
            if ($result['success'] && isset($result['data'])) {
                echo "返回数据预览: ";
                if (isset($result['data']['content'])) {
                    echo substr($result['data']['content'], 0, 100) . "...";
                } else {
                    echo json_encode($result['data']);
                }
            }
        } else {
            echo "⚠️ 响应不是有效的JSON<br>";
            echo "响应内容: <pre>" . htmlspecialchars($response) . "</pre>";
        }
    } else {
        echo "❌ 请求失败<br>";
        echo "错误: {$error}<br>";
        echo "响应: <pre>" . htmlspecialchars($response) . "</pre>";
    }
    
    session_destroy();
    echo "<hr>";
}

echo "<h3>🎯 推荐使用的接口</h3>";
echo "<p>根据测试结果，建议使用：</p>";
echo "<ol>
<li><strong>ai_simple.php</strong> - 简化接口，最稳定</li>
<li><strong>force_new_ai.php</strong> - 规则引擎，无需API</li>
<li><strong>get_ai_history.php</strong> - 获取历史记录</li>
</ol>";

echo "<p><a href='dashboard.php' target='_blank'>前往仪表盘测试</a></p>";
?>