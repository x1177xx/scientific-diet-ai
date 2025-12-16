<?php
// ai_admin.php - AI建议管理界面
session_start();
require 'db_connect.php';

// 简单权限检查
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 检查是否为管理员（简单实现）
$isAdmin = ($_SESSION['username'] ?? '') === 'admin'; // 可以根据需要修改

if (!$isAdmin) {
    die('<h3>需要管理员权限</h3>');
}

echo '<!DOCTYPE html>
<html>
<head>
    <title>AI建议管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .advice-card { margin-bottom: 15px; border-left: 4px solid #4CAF50; }
        .advice-diet { border-left-color: #2196F3; }
        .advice-exercise { border-left-color: #FF9800; }
        .stats-card { background: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1><i class="bi bi-robot"></i> AI建议管理系统</h1>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <h5>总建议数</h5>
                <h3>';
                
$result = $conn->query("SELECT COUNT(*) as count FROM ai_recommendations");
echo $result->fetch_assoc()['count'];

echo '</h3></div></div><div class="col-md-3"><div class="stats-card">
            <h5>今日建议</h5>
            <h3>';

$result = $conn->query("SELECT COUNT(*) as count FROM ai_recommendations WHERE recommendation_date = CURDATE()");
echo $result->fetch_assoc()['count'];

echo '</h3></div></div><div class="col-md-3"><div class="stats-card">
            <h5>用户数</h5>
            <h3>';

$result = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM ai_recommendations");
echo $result->fetch_assoc()['count'];

echo '</h3></div></div><div class="col-md-3"><div class="stats-card">
            <h5>建议类型</h5>
            <h3>';

$result = $conn->query("SELECT type, COUNT(*) as count FROM ai_recommendations GROUP BY type");
while ($row = $result->fetch_assoc()) {
    echo $row['type'] . ': ' . $row['count'] . '<br>';
}

echo '</h3></div></div></div>';

// 显示所有建议
echo '<h3>所有AI建议</h3>';
echo '<div class="mb-3">
    <input type="text" id="searchInput" class="form-control" placeholder="搜索建议内容...">
</div>';

$result = $conn->query("
    SELECT ar.*, u.username 
    FROM ai_recommendations ar
    LEFT JOIN users u ON ar.user_id = u.user_id
    ORDER BY ar.created_at DESC
    LIMIT 100
");

echo '<div id="adviceList">';
while ($row = $result->fetch_assoc()) {
    $typeClass = 'advice-' . $row['type'];
    echo "<div class='card advice-card $typeClass' data-content='" . htmlspecialchars($row['content']) . "'>
        <div class='card-body'>
            <div class='d-flex justify-content-between'>
                <h6 class='card-title'>用户: {$row['username']} (#{$row['user_id']})</h6>
                <small class='text-muted'>{$row['recommendation_date']}</small>
            </div>
            <p class='card-text'>" . nl2br(htmlspecialchars(substr($row['content'], 0, 150))) . "...</p>
            <div class='d-flex justify-content-between'>
                <span class='badge bg-secondary'>{$row['type']}</span>
                <small>{$row['created_at']}</small>
            </div>
        </div>
    </div>";
}
echo '</div></div>

<script>
// 搜索功能
document.getElementById("searchInput").addEventListener("input", function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll(".advice-card");
    
    cards.forEach(card => {
        const content = card.getAttribute("data-content").toLowerCase();
        if (content.includes(searchTerm)) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
});
</script>
</body>
</html>';
?>