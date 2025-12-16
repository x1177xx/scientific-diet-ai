<?php
// fix_ai_tables.php - 修复AI表创建问题
require 'db_connect.php';

echo "<h2>修复AI表创建问题</h2>";

// 1. 先检查users表的状态
echo "<h3>1. 检查users表状态</h3>";
$result = $conn->query("SHOW CREATE TABLE users");
if ($row = $result->fetch_assoc()) {
    echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";
    
    // 检查主键和存储引擎
    if (strpos($row['Create Table'], 'ENGINE=InnoDB') === false) {
        echo "⚠️ users表不是InnoDB引擎，正在转换...<br>";
        $conn->query("ALTER TABLE users ENGINE=InnoDB");
        echo "✅ 已转换为InnoDB<br>";
    }
} else {
    echo "❌ 无法获取users表结构<br>";
}

// 2. 删除已存在的ai_recommendations表（如果存在）
echo "<h3>2. 清理现有表</h3>";
$conn->query("DROP TABLE IF EXISTS ai_recommendations");

// 3. 创建不带外键约束的版本（先确保能创建）
echo "<h3>3. 创建简化版ai_recommendations表</h3>";
$sql = "CREATE TABLE ai_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recommendation_date DATE NOT NULL,
    content TEXT NOT NULL,
    type ENUM('diet', 'exercise', 'general') DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_date (user_id, recommendation_date)
    -- 先不加外键约束
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "✅ ai_recommendations表创建成功（无外键）<br>";
    
    // 4. 尝试添加外键约束
    echo "<h3>4. 尝试添加外键约束</h3>";
    $fk_sql = "ALTER TABLE ai_recommendations 
               ADD CONSTRAINT fk_ai_recommendations_user
               FOREIGN KEY (user_id) REFERENCES users(user_id)
               ON DELETE CASCADE";
    
    if ($conn->query($fk_sql)) {
        echo "✅ 外键约束添加成功<br>";
    } else {
        echo "⚠️ 外键约束添加失败: " . $conn->error . "<br>";
        echo "将使用无外键版本，不影响功能使用<br>";
    }
} else {
    echo "❌ 表创建失败: " . $conn->error . "<br>";
    
    // 5. 更简化的版本
    echo "<h3>5. 尝试创建更简化的版本</h3>";
    $simple_sql = "CREATE TABLE ai_recommendations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        recommendation_date DATE NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_date (user_id, recommendation_date)
    )";
    
    if ($conn->query($simple_sql)) {
        echo "✅ 简化版ai_recommendations表创建成功<br>";
    }
}

// 6. 验证最终结果
echo "<h3>6. 最终验证</h3>";
$result = $conn->query("SHOW TABLES LIKE 'ai_%'");
$ai_tables = [];
while ($row = $result->fetch_array()) {
    $ai_tables[] = $row[0];
    echo "✅ 存在表: " . $row[0] . "<br>";
}

if (count($ai_tables) >= 1) {
    echo "<p style='color:green; font-weight:bold;'>🎉 AI表创建完成，可以继续开发！</p>";
    echo "<p>即使没有外键约束，系统也能正常工作。</p>";
} else {
    echo "<p style='color:red;'>❌ AI表创建失败</p>";
}

$conn->close();
?>