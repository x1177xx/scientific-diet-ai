<?php
// test_final_fix.php - 最终修复测试
session_start();
require 'db_connect.php';

// 设置测试用户
$user_id = 1; // 修改为你的用户ID
$_SESSION['user_id'] = $user_id;

echo "<h2>🎯 最终修复测试</h2>";

// 1. 直接调用generate_ai_advice.php的逻辑
echo "<h3>1. 模拟API调用</h3>";

require 'AIAdvisor_simple.php';
$advisor = new AIAdvisorSimple($conn);

echo "调用generateAdvice()方法...<br>";

$result = $advisor->generateAdvice($user_id);

echo "<h4>结果:</h4>";
echo "<pre>";
print_r($result);
echo "</pre>";

if (isset($result['is_fallback']) && $result['is_fallback']) {
    echo "<div style='color:red; font-weight:bold;'>❌ 仍然是降级建议！</div>";
    echo "<p>检查错误日志：</p>";
    echo "<pre>";
    // 模拟查看最后几行错误日志
    $log_file = '/tmp/php_errors.log';
    if (file_exists($log_file)) {
        echo "最后20行日志:\n";
        echo shell_exec("tail -n 20 " . $log_file);
    } else {
        echo "日志文件不存在: $log_file";
    }
    echo "</pre>";
} else {
    echo "<div style='color:green; font-weight:bold;'>✅ 生成个性化建议成功！</div>";
    
    echo "<h4>生成的建议内容:</h4>";
    echo "<div style='background:#e8f5e8; padding:15px; border-radius:5px;'>";
    echo nl2br(htmlspecialchars($result['content']));
    echo "</div>";
    
    echo "<h4>数据库验证:</h4>";
    
    // 检查是否保存到数据库
    $check_query = $conn->query("
        SELECT * FROM ai_recommendations 
        WHERE user_id = $user_id 
        ORDER BY id DESC 
        LIMIT 1
    ");
    
    if ($check_query->num_rows > 0) {
        $last_record = $check_query->fetch_assoc();
        
        echo "<div style='color:green;'>✅ 成功保存到数据库！</div>";
        echo "记录ID: " . $last_record['id'] . "<br>";
        echo "类型: " . $last_record['type'] . "<br>";
        echo "日期: " . $last_record['recommendation_date'] . "<br>";
        echo "创建时间: " . $last_record['created_at'] . "<br>";
        
        // 对比内容
        echo "<h5>内容对比:</h5>";
        echo "生成的内容开头: " . substr($result['content'], 0, 50) . "...<br>";
        echo "保存的内容开头: " . substr($last_record['content'], 0, 50) . "...<br>";
        
        if (strpos($last_record['content'], "热量摄入不足") !== false) {
            echo "<div style='color:green;'>✅ 内容匹配：是热量不足建议</div>";
        } else {
            echo "<div style='color:orange;'>⚠️ 内容可能不匹配</div>";
        }
    } else {
        echo "<div style='color:red;'>❌ 没有保存到数据库！</div>";
        echo "建议ID: " . ($result['advice_id'] ?? 0) . "<br>";
        
        // 尝试手动保存
        echo "<h5>尝试手动保存...</h5>";
        $today = date('Y-m-d');
        $test_content = "测试手动保存 - " . date('Y-m-d H:i:s');
        $test_type = 'diet';
        
        $stmt = $conn->prepare("
            INSERT INTO ai_recommendations (user_id, recommendation_date, content, type)
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $today, $test_content, $test_type);
            if ($stmt->execute()) {
                echo "✅ 手动保存成功，ID: " . $stmt->insert_id . "<br>";
            } else {
                echo "❌ 手动保存失败: " . $stmt->error . "<br>";
            }
            $stmt->close();
        } else {
            echo "❌ 准备语句失败: " . $conn->error . "<br>";
        }
    }
}

$conn->close();

// 2. 提示下一步操作
echo "<h3>2. 浏览器测试</h3>";
echo "<p>现在请到 dashboard.php 页面，点击 '生成AI建议' 按钮测试。</p>";
echo "<p>如果还有问题，请检查：</p>";
echo "<ol>";
echo "<li>浏览器控制台（F12 → Console）</li>";
echo "<li>PHP错误日志</li>";
echo "<li>确保AIAdvisor_simple.php已更新</li>";
echo "</ol>";

echo "<h3>3. 临时解决方案</h3>";
echo "<p>如果问题仍然存在，可以使用这个临时修复：</p>";
echo "<pre style='background:#f5f5f5; padding:10px;'>
// 在 dashboard.php 的 JavaScript 中，修改 generateAIAdvice 函数：
async function generateAIAdvice() {
    // ... 原有代码
    
    try {
        const response = await fetch('generate_ai_advice.php');
        const result = await response.json();
        
        if (result.success) {
            // 如果返回的是降级建议，但标记不是降级，说明是数据库保存问题
            if (result.data.content.includes('根据您的饮食记录提供建议') && 
                !result.data.is_fallback) {
                // 使用测试脚本生成个性化建议
                await generatePersonalizedAdviceDirect();
            } else {
                // 正常显示
                document.getElementById('adviceText').innerHTML = 
                    formatAdviceText(result.data.content);
                // ... 其余代码
            }
        }
    } catch (error) {
        // ... 原有代码
    }
}
</pre>";
?>