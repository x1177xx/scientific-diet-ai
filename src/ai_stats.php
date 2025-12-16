<?php
// ai_stats.php - AI功能统计
require 'db_connect.php';

echo "<h2>📊 AI功能使用统计</h2>";

// 1. 总体统计
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_recommendations,
        COUNT(DISTINCT user_id) as active_users,
        MIN(created_at) as first_recommendation,
        MAX(created_at) as last_recommendation
    FROM ai_recommendations
")->fetch_assoc();

echo "<div style='background:#e8f5e8; padding:15px; border-radius:5px; margin-bottom:20px;'>";
echo "<h4>总体统计</h4>";
echo "总建议数: " . $stats['total_recommendations'] . "<br>";
echo "活跃用户数: " . $stats['active_users'] . "<br>";
echo "首次建议: " . $stats['first_recommendation'] . "<br>";
echo "最近建议: " . $stats['last_recommendation'] . "<br>";
echo "</div>";

// 2. 每日统计
echo "<h4>📅 每日建议数量</h4>";
$daily = $conn->query("
    SELECT recommendation_date, COUNT(*) as count 
    FROM ai_recommendations 
    GROUP BY recommendation_date 
    ORDER BY recommendation_date DESC
    LIMIT 10
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>日期</th><th>建议数</th></tr>";
while ($row = $daily->fetch_assoc()) {
    echo "<tr><td>{$row['recommendation_date']}</td><td>{$row['count']}</td></tr>";
}
echo "</table>";

// 3. 用户活跃度
echo "<h4>👤 用户活跃度排行</h4>";
$users = $conn->query("
    SELECT u.username, COUNT(ar.id) as recommendation_count,
           MAX(ar.created_at) as last_active
    FROM users u
    LEFT JOIN ai_recommendations ar ON u.user_id = ar.user_id
    GROUP BY u.user_id
    ORDER BY recommendation_count DESC
    LIMIT 10
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>用户名</th><th>建议数</th><th>最后活跃</th></tr>";
while ($row = $users->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['username']}</td>";
    echo "<td>{$row['recommendation_count']}</td>";
    echo "<td>{$row['last_active']}</td>";
    echo "</tr>";
}
echo "</table>";

// 4. 建议类型分布
echo "<h4>📋 建议类型分布</h4>";
$types = $conn->query("
    SELECT type, COUNT(*) as count,
           ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM ai_recommendations), 1) as percentage
    FROM ai_recommendations
    GROUP BY type
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>类型</th><th>数量</th><th>占比</th></tr>";
while ($row = $types->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['type']}</td>";
    echo "<td>{$row['count']}</td>";
    echo "<td>{$row['percentage']}%</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p style='margin-top:20px; color:green;'>
    ✅ 统计数据显示AI功能正在被使用！
</p>";
?>