<?php
// test_ai_full.php - 完整AI功能测试
session_start();

// 如果是浏览器访问，模拟用户登录
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // 假设用户ID为1
    $_SESSION['username'] = 'testuser';
    echo "<p>⚠️ 模拟登录用户ID: 1</p>";
}

require 'db_connect.php';
require 'AIAdvisor.php';

echo "<h2>完整AI功能测试</h2>";
echo "<p>当前时间: " . date('Y-m-d H:i:s') . "</p>";

try {
    // 1. 创建AI顾问实例
    $advisor = new AIAdvisor($conn);
    echo "<h3>1. ✅ AIAdvisor实例创建成功</h3>";
    
    // 2. 获取用户数据
    $userId = $_SESSION['user_id'];
    echo "<h3>2. 测试用户ID: {$userId}</h3>";
    
    // 3. 生成AI建议
    echo "<h3>3. 正在生成AI建议...</h3>";
    
    $startTime = microtime(true);
    $result = $advisor->generateAdvice($userId);
    $endTime = microtime(true);
    
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "<div style='background:#d4edda; padding:15px; border-radius:5px;'>";
    echo "<h4>✅ AI建议生成成功！</h4>";
    echo "<p>执行时间: {$executionTime}ms</p>";
    echo "<p>建议ID: {$result['advice_id']}</p>";
    echo "<p>建议类型: {$result['type']}</p>";
    echo "<p>AI生成: " . ($result['is_ai_generated'] ? '是' : '否') . "</p>";
    echo "<p>AI提供商: {$result['ai_provider']}</p>";
    echo "<p>生成时间: {$result['generated_at']}</p>";
    echo "</div>";
    
    // 4. 显示建议内容
    echo "<h3>4. 建议内容：</h3>";
    echo "<div style='background:#f8f9fa; padding:15px; border-radius:5px; border:1px solid #ddd;'>";
    echo nl2br(htmlspecialchars($result['content']));
    echo "</div>";
    
    // 5. 验证数据库保存
    echo "<h3>5. 验证数据库保存：</h3>";
    $checkStmt = $conn->prepare("SELECT * FROM ai_recommendations WHERE id = ?");
    $checkStmt->bind_param("i", $result['advice_id']);
    $checkStmt->execute();
    $dbResult = $checkStmt->get_result();
    
    if ($dbResult->num_rows > 0) {
        $row = $dbResult->fetch_assoc();
        echo "<div style='background:#d1ecf1; padding:15px; border-radius:5px;'>";
        echo "✅ 数据库记录验证成功！<br>";
        echo "记录ID: {$row['id']}<br>";
        echo "用户ID: {$row['user_id']}<br>";
        echo "建议日期: {$row['recommendation_date']}<br>";
        echo "创建时间: {$row['created_at']}<br>";
        echo "AI生成: " . ($row['is_ai_generated'] ? '是' : '否') . "<br>";
        echo "AI提供商: {$row['ai_provider']}<br>";
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da; padding:15px; border-radius:5px;'>";
        echo "❌ 数据库记录未找到！";
        echo "</div>";
    }
    $checkStmt->close();
    
    // 6. 测试历史记录获取
    echo "<h3>6. 测试历史记录获取：</h3>";
    try {
        $history = $advisor->getHistory($userId, 5);
        if (count($history) > 0) {
            echo "<div style='background:#e2e3e5; padding:15px; border-radius:5px;'>";
            echo "✅ 历史记录获取成功！共 " . count($history) . " 条记录";
            echo "<table border='1' cellpadding='5' style='margin-top:10px;'>";
            echo "<tr><th>ID</th><th>日期</th><th>类型</th><th>AI生成</th><th>提供商</th><th>创建时间</th></tr>";
            foreach ($history as $item) {
                echo "<tr>";
                echo "<td>{$item['id']}</td>";
                echo "<td>{$item['recommendation_date']}</td>";
                echo "<td>{$item['type']}</td>";
                echo "<td>" . ($item['is_ai_generated'] ? '是' : '否') . "</td>";
                echo "<td>{$item['ai_provider']}</td>";
                echo "<td>{$item['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
        } else {
            echo "<p>暂无历史记录</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>历史记录获取失败: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; padding:15px; border-radius:5px;'>";
    echo "<h4>❌ 测试失败</h4>";
    echo "<p>错误信息: " . $e->getMessage() . "</p>";
    echo "<p>错误追踪: <pre>" . $e->getTraceAsString() . "</pre></p>";
    echo "</div>";
    
    // 显示环境信息
    echo "<h4>环境信息：</h4>";
    echo "<pre>";
    echo "PHP版本: " . PHP_VERSION . "\n";
    echo "CURL支持: " . (function_exists('curl_version') ? '是' : '否') . "\n";
    if (function_exists('curl_version')) {
        $curlInfo = curl_version();
        echo "CURL版本: " . $curlInfo['version'] . "\n";
        echo "SSL版本: " . $curlInfo['ssl_version'] . "\n";
    }
    echo "当前目录: " . __DIR__ . "\n";
    echo "配置文件: " . (file_exists('config.php') ? '存在' : '不存在') . "\n";
    echo "</pre>";
}

echo "<hr>";
echo "<h3>下一步操作：</h3>";
echo "<ol>
<li><a href='dashboard.php' target='_blank'>测试仪表盘功能</a></li>
<li><a href='ai_stats.php' target='_blank'>查看AI统计</a></li>
<li><a href='ai_admin.php' target='_blank'>查看AI管理界面</a></li>
<li><a href='fix_ssl_test.php' target='_blank'>重新测试SSL</a></li>
</ol>";

$conn->close();
?>