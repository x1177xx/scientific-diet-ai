<?php
// fix_ai_data.php - 直接修复AI数据问题
require 'db_connect.php';

echo "<h2>🔧 直接修复AI数据问题</h2>";

$userId = 1; // 你的用户ID
$today = date('Y-m-d');

// 1. 先删除所有可能的问题数据
echo "<h3>1. 清理数据</h3>";
$conn->query("DELETE FROM ai_recommendations WHERE user_id = $userId");
echo "已删除用户 $userId 的所有建议记录<br>";

// 2. 直接插入一个个性化建议
echo "<h3>2. 插入个性化建议</h3>";
$personalizedAdvice = "**今日饮食评价**：热量摄入不足！缺少473kcal，需要增加营养。

**具体建议**：
1. **增加热量**：每餐增加半碗米饭或一个红薯
2. **优质脂肪**：每日增加一把坚果（约30g）
3. **蛋白质**：确保每餐有优质蛋白
4. **加餐**：上午10点和下午4点各加餐一次

**推荐运动**：
🏋️‍♂️ 力量训练45分钟（增肌为主）
🤸‍♂️ 轻度有氧20分钟

**明日目标**：
📈 达到2100kcal热量摄入
🥚 蛋白质充足
🌰 健康脂肪摄入30-50g";

$stmt = $conn->prepare("
    INSERT INTO ai_recommendations (user_id, recommendation_date, content, type)
    VALUES (?, ?, ?, 'diet')
");
$stmt->bind_param("iss", $userId, $today, $personalizedAdvice);

if ($stmt->execute()) {
    echo "✅ 成功插入个性化建议，ID: " . $stmt->insert_id . "<br>";
} else {
    echo "❌ 插入失败: " . $stmt->error . "<br>";
}
$stmt->close();

// 3. 再插入一条历史建议（昨天）
echo "<h3>3. 插入历史建议</h3>";
$yesterday = date('Y-m-d', strtotime('-1 day'));
$historyAdvice = "**昨日饮食评价**：饮食均衡，继续保持！

**具体建议**：
1. 多样化蛋白质来源
2. 增加蔬菜种类
3. 控制晚餐时间

**推荐运动**：游泳30分钟";

$stmt2 = $conn->prepare("
    INSERT INTO ai_recommendations (user_id, recommendation_date, content, type)
    VALUES (?, ?, ?, 'general')
");
$stmt2->bind_param("iss", $userId, $yesterday, $historyAdvice);

if ($stmt2->execute()) {
    echo "✅ 成功插入历史建议，ID: " . $stmt2->insert_id . "<br>";
} else {
    echo "❌ 插入失败: " . $stmt2->error . "<br>";
}
$stmt2->close();

// 4. 验证数据
echo "<h3>4. 验证数据</h3>";
$result = $conn->query("
    SELECT id, recommendation_date, type, LEFT(content, 50) as preview
    FROM ai_recommendations 
    WHERE user_id = $userId
    ORDER BY recommendation_date DESC
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>日期</th><th>类型</th><th>内容预览</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['recommendation_date']}</td>";
    echo "<td>{$row['type']}</td>";
    echo "<td>{$row['preview']}</td>";
    echo "</tr>";
}
echo "</table>";

// 5. 提供测试链接
echo "<h3>5. 测试链接</h3>";
echo "<ul>";
echo "<li><a href='dashboard.php' target='_blank'>前往仪表盘测试</a></li>";
echo "<li><a href=\"javascript:void(0);\" onclick=\"testHistory()\">测试历史API</a></li>";
echo "<li><a href='get_ai_history.php?limit=10' target='_blank'>直接访问历史API</a></li>";
echo "</ul>";

echo "<div id='testResult'></div>";

echo "<script>
function testHistory() {
    fetch('get_ai_history.php?limit=10')
        .then(response => response.json())
        .then(data => {
            document.getElementById('testResult').innerHTML = 
                '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(error => {
            document.getElementById('testResult').innerHTML = '错误: ' + error;
        });
}
</script>";

$conn->close();
?>