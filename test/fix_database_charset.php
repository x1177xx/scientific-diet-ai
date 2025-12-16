<?php
// fix_database_charset.php - 修复数据库字符集
require 'db_connect.php';

echo "<h2>🔧 修复数据库字符集</h2>";

// 1. 修改数据库字符集
echo "<h3>1. 修改数据库字符集</h3>";
$conn->query("ALTER DATABASE scientific_diet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "✅ 数据库字符集已修改为utf8mb4<br>";

// 2. 修改ai_recommendations表字符集
echo "<h3>2. 修改ai_recommendations表字符集</h3>";
$conn->query("ALTER TABLE ai_recommendations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "✅ ai_recommendations表字符集已修改<br>";

// 3. 修改content字段为LONGTEXT（支持更多字符）
echo "<h3>3. 修改content字段类型</h3>";
$conn->query("ALTER TABLE ai_recommendations MODIFY content LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "✅ content字段已修改为LONGTEXT<br>";

// 4. 设置连接字符集
echo "<h3>4. 设置连接字符集</h3>";
$conn->set_charset("utf8mb4");
echo "✅ 连接字符集已设置为utf8mb4<br>";

// 5. 验证修改
echo "<h3>5. 验证修改结果</h3>";
$result = $conn->query("SHOW CREATE TABLE ai_recommendations");
$row = $result->fetch_assoc();
echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";

echo "<div style='background:#e8f5e8; padding:15px; margin-top:20px;'>";
echo "<h3>✅ 字符集修复完成！</h3>";
echo "<p>现在可以重新插入数据了。</p>";
echo "</div>";

$conn->close();
?>
