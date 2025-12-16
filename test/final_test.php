<?php
// final_test.php - 最终完整测试
session_start();

// 确保有用户会话
if (!isset($_SESSION['user_id'])) {
    // 使用第一个存在的用户
    require 'db_connect.php';
    $result = $conn->query("SELECT user_id FROM users LIMIT 1");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = 'test_user';
        echo "<p>📝 自动登录用户ID: {$_SESSION['user_id']}</p>";
    }
}

echo "<h2>🎯 AI功能最终测试</h2>";

// 测试1: 数据库连接
echo "<h3>1. 数据库连接测试</h3>";
require 'db_connect.php';
echo "连接状态: " . ($conn->ping() ? "✅ 成功" : "❌ 失败") . "<br>";

// 测试2: 表结构验证
echo "<h3>2. 表结构验证</h3>";
$tables = ['users', 'foods', 'intake_records', 'ai_recommendations'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    echo "$table: " . ($result->num_rows > 0 ? "✅ 存在" : "❌ 缺失") . "<br>";
}

// 测试3: AI建议表字段验证
echo "<h3>3. AI建议表字段验证</h3>";
$result = $conn->query("DESCRIBE ai_recommendations");
$fields = [];
while ($row = $result->fetch_assoc()) {
    $fields[] = $row['Field'];
}

$requiredFields = ['id', 'user_id', 'recommendation_date', 'content', 'type', 'is_ai_generated', 'ai_provider', 'created_at'];
foreach ($requiredFields as $field) {
    echo "$field: " . (in_array($field, $fields) ? "✅ 存在" : "❌ 缺失") . "<br>";
}

// 测试4: 使用主AIAdvisor类生成建议
echo "<h3>4. 使用主AIAdvisor类测试</h3>";
require 'AIAdvisor.php';

try {
    $advisor = new AIAdvisor($conn);
    $userId = $_SESSION['user_id'] ?? 1;
    
    echo "用户ID: $userId<br>";
    echo "开始生成建议...<br>";
    
    $result = $advisor->generateAdvice($userId);
    
    echo "<div style='background:#d4edda; padding:15px; border-radius:5px;'>";
    echo "🎉 <strong>主AIAdvisor测试成功！</strong><br>";
    echo "建议ID: {$result['advice_id']}<br>";
    echo "类型: {$result['type']}<br>";
    echo "AI生成: " . ($result['is_ai_generated'] ? '✅ 是' : '❌ 否') . "<br>";
    echo "提供商: {$result['ai_provider']}<br>";
    echo "生成时间: {$result['generated_at']}<br>";
    echo "<hr>";
    echo "<strong>建议内容预览：</strong><br>";
    echo nl2br(htmlspecialchars(substr($result['content'], 0, 300))) . "...";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; padding:15px; border-radius:5px;'>";
    echo "❌ 主AIAdvisor测试失败<br>";
    echo "错误: " . $e->getMessage() . "<br>";
    echo "追踪: <pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
    
    // 尝试使用修复版
    echo "<h3>5. 尝试使用修复版AIAdvisor_fixed</h3>";
    require 'AIAdvisor_fixed.php';
    try {
        $advisor = new AIAdvisor_fixed($conn);
        $result = $advisor->generateAdvice($userId);
        
        echo "<div style='background:#d4edda; padding:15px; border-radius:5px;'>";
        echo "✅ 修复版测试成功！<br>";
        echo "建议ID: {$result['advice_id']}<br>";
        echo "内容长度: " . strlen($result['content']) . " 字符<br>";
        echo "</div>";
    } catch (Exception $e2) {
        echo "<div style='background:#f8d7da; padding:15px; border-radius:5px;'>";
        echo "❌ 修复版也失败: " . $e2->getMessage();
        echo "</div>";
    }
}

// 测试5: 验证数据库记录
echo "<h3>5. 验证数据库记录</h3>";
$today = date('Y-m-d');
$checkSql = "SELECT * FROM ai_recommendations WHERE user_id = ? AND recommendation_date = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("is", $_SESSION['user_id'], $today);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<div style='background:#c3e6cb; padding:15px; border-radius:5px;'>";
    echo "✅ 数据库记录验证成功！<br>";
    echo "记录ID: {$row['id']}<br>";
    echo "is_ai_generated: " . ($row['is_ai_generated'] ? '1 (是)' : '0 (否)') . "<br>";
    echo "ai_provider: {$row['ai_provider']}<br>";
    echo "创建时间: {$row['created_at']}<br>";
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da; padding:15px; border-radius:5px;'>";
    echo "❌ 未找到今天的数据库记录";
    echo "</div>";
}

// 测试6: 历史记录功能
echo "<h3>6. 历史记录功能测试</h3>";
try {
    $history = $advisor->getHistory($_SESSION['user_id'], 3);
    echo "获取到 " . count($history) . " 条历史记录<br>";
    
    if (count($history) > 0) {
        echo "<table border='1' cellpadding='5' style='margin-top:10px;'>";
        echo "<tr><th>日期</th><th>类型</th><th>AI生成</th><th>提供商</th><th>创建时间</th></tr>";
        foreach ($history as $item) {
            echo "<tr>";
            echo "<td>{$item['recommendation_date']}</td>";
            echo "<td>{$item['type']}</td>";
            echo "<td>" . ($item['is_ai_generated'] ? '是' : '否') . "</td>";
            echo "<td>{$item['ai_provider']}</td>";
            echo "<td>{$item['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "历史记录获取失败: " . $e->getMessage() . "<br>";
}

$conn->close();

echo "<hr>";
echo "<h2>🎉 测试完成！</h2>";
echo "<p>现在AI功能应该已经完全修复。请测试：</p>";
echo "<ol>
<li><strong><a href='dashboard.php' target='_blank'>仪表盘AI建议功能</a></strong> - 最重要的测试</li>
<li><a href='ai_stats.php' target='_blank'>AI统计数据</a></li>
<li><a href='ai_admin.php' target='_blank'>AI管理界面</a>（需要管理员）</li>
<li><a href='get_ai_history.php' target='_blank'>JSON格式历史数据</a></li>
</ol>";

echo "<h3>如果仪表盘仍然有问题：</h3>";
echo "<p>请检查浏览器控制台(F12)是否有JavaScript错误，然后告诉我具体现象。</p>";
?>