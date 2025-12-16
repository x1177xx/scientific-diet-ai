<?php
// recreate_ai_table.php - 重建表结构
require 'db_connect.php';

echo "<h2>重建AI建议表</h2>";

// 备份现有数据
echo "<h3>1. 备份现有数据...</h3>";
$backup = $conn->query("SELECT * FROM ai_recommendations");
$backupData = [];
while ($row = $backup->fetch_assoc()) {
    $backupData[] = $row;
}
echo "备份了 " . count($backupData) . " 条记录<br>";

// 删除旧表
echo "<h3>2. 删除旧表...</h3>";
if ($conn->query("DROP TABLE IF EXISTS ai_recommendations")) {
    echo "✅ 旧表已删除<br>";
} else {
    echo "❌ 删除失败: " . $conn->error . "<br>";
}

// 创建新表（包含所有字段）
echo "<h3>3. 创建新表...</h3>";
$createSql = "CREATE TABLE ai_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recommendation_date DATE NOT NULL,
    content TEXT NOT NULL,
    type ENUM('diet', 'exercise', 'general') DEFAULT 'general',
    is_ai_generated BOOLEAN DEFAULT FALSE,
    ai_provider VARCHAR(50) DEFAULT 'mock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_date (user_id, recommendation_date),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($createSql)) {
    echo "✅ 新表创建成功<br>";
} else {
    echo "❌ 创建失败: " . $conn->error . "<br>";
    exit;
}

// 恢复数据（如果存在）
if (count($backupData) > 0) {
    echo "<h3>4. 恢复数据...</h3>";
    
    $inserted = 0;
    foreach ($backupData as $row) {
        $sql = "INSERT INTO ai_recommendations 
                (user_id, recommendation_date, content, type, is_ai_generated, ai_provider) 
                VALUES (
                    " . intval($row['user_id']) . ",
                    '" . $conn->real_escape_string($row['recommendation_date']) . "',
                    '" . $conn->real_escape_string($row['content']) . "',
                    '" . $conn->real_escape_string($row['type']) . "',
                    " . (isset($row['is_ai_generated']) ? intval($row['is_ai_generated']) : 0) . ",
                    '" . (isset($row['ai_provider']) ? $conn->real_escape_string($row['ai_provider']) : 'mock') . "'
                )";
        
        if ($conn->query($sql)) {
            $inserted++;
        }
    }
    
    echo "恢复了 {$inserted}/" . count($backupData) . " 条记录<br>";
}

// 验证表结构
echo "<h3>5. 验证表结构：</h3>";
$result = $conn->query("DESCRIBE ai_recommendations");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>字段</th><th>类型</th><th>为空</th><th>键</th><th>默认</th><th>额外</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr><h3>完成！</h3>";
echo "<p>现在可以：</p>";
echo "<ol>
<li><a href='test_direct.php'>测试AIAdvisor_fixed</a></li>
<li><a href='dashboard.php'>测试仪表盘</a></li>
</ol>";

$conn->close();
?>