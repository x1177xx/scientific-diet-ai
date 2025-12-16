<?php
// test_ai_function.php - 测试AI建议功能
session_start();
require 'db_connect.php';

// 模拟登录（选择一个测试用户）
echo "<h2>🔍 测试AI建议功能</h2>";

// 1. 首先检查表结构
echo "<h3>1. 检查ai_recommendations表结构</h3>";
$result = $conn->query("DESCRIBE ai_recommendations");
if (!$result) {
    die("❌ ai_recommendations表不存在或无法访问！请先运行fix_ai_tables.php");
}

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>字段</th><th>类型</th><th>NULL</th><th>默认值</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// 2. 检查现有数据
echo "<h3>2. 检查现有AI建议记录</h3>";
$count = $conn->query("SELECT COUNT(*) as total FROM ai_recommendations")->fetch_assoc()['total'];
echo "当前总记录数: " . $count . "<br>";

if ($count > 0) {
    $recent = $conn->query("SELECT * FROM ai_recommendations ORDER BY created_at DESC LIMIT 3");
    echo "<h4>最近3条记录:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>用户ID</th><th>日期</th><th>类型</th><th>创建时间</th><th>内容预览</th></tr>";
    while ($row = $recent->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['recommendation_date']}</td>";
        echo "<td>{$row['type']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "<td>" . substr($row['content'], 0, 50) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. 手动模拟生成AI建议
echo "<h3>3. 手动测试AI建议生成</h3>";

// 选择一个测试用户（这里用第一个用户）
$user_result = $conn->query("SELECT user_id, username FROM users LIMIT 1");
if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $test_user_id = $user['user_id'];
    
    echo "测试用户: {$user['username']} (ID: {$test_user_id})<br>";
    
    // 设置会话用户（模拟登录）
    $_SESSION['user_id'] = $test_user_id;
    $_SESSION['username'] = $user['username'];
    
    // 包含AI顾问类
    require 'AIAdvisor_simple.php';
    
    // 创建实例
    $advisor = new AIAdvisorSimple($conn);
    
    // 测试生成建议
    echo "开始生成AI建议...<br>";
    
    try {
        $result = $advisor->generateAdvice($test_user_id);
        
        echo "<div style='background:#e8f5e8; padding:10px; margin:10px 0;'>";
        echo "<strong>✅ 生成结果:</strong><br>";
        echo "成功: " . ($result['success'] ? '是' : '否') . "<br>";
        echo "建议ID: " . ($result['advice_id'] ?? '无') . "<br>";
        echo "类型: " . ($result['type'] ?? '未知') . "<br>";
        echo "是否为降级建议: " . ($result['is_fallback'] ?? '否') . "<br>";
        echo "</div>";
        
        // 显示建议内容
        echo "<div style='background:#f0f8ff; padding:15px; margin:10px 0; border-left:4px solid #2196F3;'>";
        echo "<strong>建议内容:</strong><br>";
        echo "<pre style='white-space: pre-wrap;'>" . htmlspecialchars($result['content'] ?? '无内容') . "</pre>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background:#ffebee; padding:10px; margin:10px 0;'>";
        echo "<strong>❌ 生成失败:</strong> " . $e->getMessage();
        echo "</div>";
    }
    
    // 验证是否真的写入数据库
    echo "<h3>4. 验证数据库写入</h3>";
    $new_count = $conn->query("SELECT COUNT(*) as total FROM ai_recommendations")->fetch_assoc()['total'];
    
    if ($new_count > $count) {
        echo "<span style='color:green;'>✅ 成功！数据库记录数从 {$count} 增加到 {$new_count}</span><br>";
        
        // 显示最新的一条记录
        $new_record = $conn->query("SELECT * FROM ai_recommendations ORDER BY id DESC LIMIT 1")->fetch_assoc();
        echo "<div style='background:#f9f9f9; padding:10px; margin:10px 0;'>";
        echo "<strong>最新记录详情:</strong><br>";
        echo "ID: {$new_record['id']}<br>";
        echo "用户ID: {$new_record['user_id']}<br>";
        echo "日期: {$new_record['recommendation_date']}<br>";
        echo "类型: {$new_record['type']}<br>";
        echo "创建时间: {$new_record['created_at']}<br>";
        echo "</div>";
    } else {
        echo "<span style='color:red;'>❌ 失败！数据库记录数未增加，仍为 {$count}</span><br>";
        
        // 尝试直接测试保存函数
        echo "<h4>尝试直接测试保存功能...</h4>";
        
        $test_content = "这是测试建议内容 - " . date('Y-m-d H:i:s');
        $test_type = 'general';
        $today = date('Y-m-d');
        
        $stmt = $conn->prepare("
            INSERT INTO ai_recommendations (user_id, recommendation_date, content, type)
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt) {
            $stmt->bind_param("isss", $test_user_id, $today, $test_content, $test_type);
            if ($stmt->execute()) {
                $insert_id = $stmt->insert_id;
                echo "<span style='color:green;'>✅ 直接SQL插入成功！插入ID: {$insert_id}</span><br>";
            } else {
                echo "<span style='color:red;'>❌ 直接SQL插入失败: " . $stmt->error . "</span><br>";
            }
            $stmt->close();
        } else {
            echo "<span style='color:red;'>❌ 准备SQL语句失败: " . $conn->error . "</span><br>";
        }
    }
    
} else {
    echo "<span style='color:orange;'>⚠️ 没有找到测试用户，请先创建用户</span><br>";
}

// 4. 检查其他可能的问题
echo "<h3>5. 问题排查</h3>";

// 检查外键约束
echo "<h4>外键约束状态:</h4>";
$fk_check = $conn->query("
    SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'ai_recommendations'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");

if ($fk_check->num_rows > 0) {
    echo "<span style='color:green;'>✅ 存在外键约束</span><br>";
} else {
    echo "<span style='color:orange;'>⚠️ 无外键约束（可能不是InnoDB引擎或未设置外键）</span><br>";
    
    // 检查表引擎
    $engine_check = $conn->query("SHOW TABLE STATUS LIKE 'ai_recommendations'")->fetch_assoc();
    echo "表引擎: " . $engine_check['Engine'] . "<br>";
    if ($engine_check['Engine'] != 'InnoDB') {
        echo "<span style='color:red;'>❌ 表引擎不是InnoDB，无法使用外键！</span><br>";
    }
}

// 测试历史获取功能
echo "<h3>6. 测试历史获取功能</h3>";
if (isset($test_user_id)) {
    require 'AIAdvisor_simple.php';
    $advisor = new AIAdvisorSimple($conn);
    
    $history = $advisor->getHistory($test_user_id, 5);
    
    echo "获取到 " . count($history) . " 条历史记录<br>";
    if (count($history) > 0) {
        echo "<table border='1' cellpadding='5' style='margin-top:10px;'>";
        echo "<tr><th>ID</th><th>日期</th><th>类型</th><th>创建时间</th><th>内容预览</th></tr>";
        foreach ($history as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['recommendation_date']}</td>";
            echo "<td>{$item['type']}</td>";
            echo "<td>{$item['created_at']}</td>";
            echo "<td>" . substr($item['content'], 0, 50) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// 最后总结
echo "<h3>🎯 测试总结</h3>";
echo "<ul>";
echo "<li>如果直接SQL插入成功但通过generateAdvice()方法失败，可能是AIAdvisor_simple.php中的saveRecommendation方法有问题</li>";
echo "<li>检查PHP错误日志，看是否有未捕获的异常</li>";
echo "<li>确保数据库用户有INSERT权限</li>";
echo "<li>检查AIAdvisor_simple.php文件是否完整包含</li>";
echo "</ul>";

// 提供一个修复建议
echo "<h3>🔧 修复建议</h3>";
echo "<p>如果发现问题，可以：</p>";
echo "<ol>";
echo "<li>检查AIAdvisor_simple.php中的saveRecommendation方法</li>";
echo "<li>运行<code>fix_ai_tables.php</code>确保表结构正确</li>";
echo "<li>查看浏览器控制台和PHP错误日志</li>";
echo "<li>在dashboard.php页面的AI顾问区域点击'生成AI建议'按钮，看是否正常</li>";
echo "</ol>";

$conn->close();
?>