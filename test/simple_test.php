<?php
// simple_test.php - 最简单的测试
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>最简单的AI测试</h1>";

// 1. 测试基本包含
echo "<h3>1. 测试文件包含</h3>";
if (@include_once 'db_connect.php') {
    echo "✅ db_connect.php 包含成功<br>";
    
    // 测试数据库连接
    if (isset($conn) && $conn instanceof mysqli) {
        echo "✅ 数据库连接对象存在<br>";
        
        // 测试查询
        $result = $conn->query("SELECT 1");
        if ($result) {
            echo "✅ 数据库查询成功<br>";
        } else {
            echo "❌ 数据库查询失败: " . $conn->error . "<br>";
        }
    } else {
        echo "❌ 数据库连接对象不存在或不是mysqli<br>";
    }
} else {
    echo "❌ db_connect.php 包含失败<br>";
}

echo "<h3>2. 测试AI类</h3>";
if (@include_once 'AIAdvisor.php') {
    echo "✅ AIAdvisor.php 包含成功<br>";
    
    // 尝试创建实例
    try {
        if (!isset($conn)) {
            die("❌ 数据库连接不存在，无法创建AIAdvisor");
        }
        
        $advisor = new AIAdvisor($conn);
        echo "✅ AIAdvisor实例创建成功<br>";
        
        // 测试一个简单方法
        try {
            $userData = $advisor->getUserData(1);
            echo "✅ getUserData(1) 调用成功<br>";
            
            if (is_array($userData)) {
                echo "✅ 返回数据是数组<br>";
                
                // 显示部分数据
                echo "<pre>" . print_r([
                    'user_info' => isset($userData['user_info']) ? '存在' : '不存在',
                    'today_intake' => isset($userData['today_intake']) ? '存在' : '不存在',
                    'nutrition_goals' => isset($userData['nutrition_goals']) ? '存在' : '不存在'
                ], true) . "</pre>";
            } else {
                echo "❌ 返回数据不是数组<br>";
            }
        } catch (Exception $e) {
            echo "❌ getUserData 出错: " . $e->getMessage() . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ 创建AIAdvisor失败: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ AIAdvisor.php 包含失败<br>";
}

echo "<h3>3. 测试API接口</h3>";
echo "<button onclick='testAPI()'>测试 generate_ai_advice.php</button>";
echo "<div id='apiResult'></div>";

echo <<<HTML
<script>
async function testAPI() {
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = '测试中...';
    
    try {
        const response = await fetch('generate_ai_advice.php');
        const text = await response.text();
        
        resultDiv.innerHTML = `<div style="background:#f0f0f0; padding:10px; border-radius:5px;">
            <strong>HTTP状态:</strong> ${response.status}<br>
            <strong>响应:</strong><br>
            <pre>${text}</pre>
        </div>`;
    } catch (error) {
        resultDiv.innerHTML = `❌ API调用失败: ${error.message}`;
    }
}
</script>
HTML;
?>