<?php
// debug_history.php - 调试历史建议数据
session_start();
require 'db_connect.php';

// 模拟登录（如果需要）
$_SESSION['user_id'] = 1; // 修改为你的用户ID

$userId = $_SESSION['user_id'];

echo "<h2>🔍 调试历史建议数据</h2>";

// 1. 检查用户
echo "<h3>1. 用户信息</h3>";
$userQuery = $conn->query("SELECT user_id, username FROM users WHERE user_id = $userId");
$user = $userQuery->fetch_assoc();
echo "用户ID: " . $user['user_id'] . "<br>";
echo "用户名: " . $user['username'] . "<br>";

// 2. 检查ai_recommendations表
echo "<h3>2. ai_recommendations表数据</h3>";
$tableQuery = $conn->query("
    SELECT COUNT(*) as total, 
           COUNT(DISTINCT user_id) as users,
           MIN(created_at) as first,
           MAX(created_at) as last
    FROM ai_recommendations
");
$tableStats = $tableQuery->fetch_assoc();
echo "总记录数: " . $tableStats['total'] . "<br>";
echo "用户数: " . $tableStats['users'] . "<br>";
echo "最早记录: " . $tableStats['first'] . "<br>";
echo "最新记录: " . $tableStats['last'] . "<br>";

// 3. 当前用户的建议
echo "<h3>3. 当前用户的建议记录</h3>";
$userAdviceQuery = $conn->query("
    SELECT id, recommendation_date, type, LEFT(content, 50) as preview, created_at
    FROM ai_recommendations 
    WHERE user_id = $userId
    ORDER BY created_at DESC
    LIMIT 10
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>日期</th><th>类型</th><th>内容预览</th><th>创建时间</th></tr>";
while ($row = $userAdviceQuery->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['recommendation_date']}</td>";
    echo "<td>{$row['type']}</td>";
    echo "<td>{$row['preview']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";

// 4. 测试get_ai_history.php的SQL
echo "<h3>4. 测试历史查询SQL</h3>";
$testStmt = $conn->prepare("
    SELECT id, recommendation_date, content, type, created_at
    FROM ai_recommendations 
    WHERE user_id = ?
    ORDER BY recommendation_date DESC, created_at DESC
    LIMIT 10
");

if (!$testStmt) {
    echo "❌ SQL准备失败: " . $conn->error . "<br>";
} else {
    $testStmt->bind_param("i", $userId);
    $testStmt->execute();
    $testResult = $testStmt->get_result();
    
    $testData = [];
    while ($row = $testResult->fetch_assoc()) {
        $testData[] = $row;
    }
    
    echo "查询成功，返回 " . count($testData) . " 条记录<br>";
    
    if (count($testData) > 0) {
        echo "<h4>查询结果预览：</h4>";
        echo "<pre>";
        print_r($testData[0]); // 显示第一条记录
        echo "</pre>";
    }
    
    $testStmt->close();
}

// 5. 模拟API响应
echo "<h3>5. 模拟API响应</h3>";
echo "<button onclick=\"testAPI()\">测试get_ai_history.php API</button>";
echo "<div id='apiResult' style='margin-top:10px;'></div>";

echo "<script>
function testAPI() {
    fetch('get_ai_history.php?limit=5')
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(error => {
            document.getElementById('apiResult').innerHTML = '错误: ' + error;
        });
}
</script>";

$conn->close();
?>