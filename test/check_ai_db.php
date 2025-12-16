<?php
// check_ai_db.php - 检查AI数据库状态
session_start();
require 'db_connect.php';

echo "<h2>AI数据库状态检查</h2>";

// 1. 检查表是否存在
$result = $conn->query("SHOW TABLES LIKE 'ai_recommendations'");
if ($result->num_rows > 0) {
    echo "✅ ai_recommendations 表存在<br>";
} else {
    echo "❌ ai_recommendations 表不存在<br>";
}

// 2. 查看表结构
echo "<h3>表结构：</h3>";
$result = $conn->query("DESCRIBE ai_recommendations");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>字段</th><th>类型</th><th>Null</th><th>默认</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// 3. 查看当前用户的建议记录
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    echo "<h3>当前用户 (#{$userId}) 的建议记录：</h3>";
    
    $stmt = $conn->prepare("SELECT * FROM ai_recommendations WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>日期</th><th>类型</th><th>内容预览</th><th>创建时间</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['recommendation_date']}</td>";
            echo "<td>{$row['type']}</td>";
            echo "<td>" . substr($row['content'], 0, 50) . "...</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ 该用户没有AI建议记录<br>";
    }
    $stmt->close();
}

// 4. 查看今天是否有记录
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM ai_recommendations WHERE recommendation_date = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo "<h3>今日建议统计：</h3>";
echo "今日 (" . $today . ") 共有 " . $result['count'] . " 条建议<br>";

echo "<h3>测试插入：</h3>";
echo "<button onclick='testInsert()'>测试插入一条记录</button>";
echo "<div id='testResult'></div>";

?>
<script>
function testInsert() {
    fetch('test_ai_insert.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('testResult').innerHTML = 
                JSON.stringify(data, null, 2);
        });
}
</script>