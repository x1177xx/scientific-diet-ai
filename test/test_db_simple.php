<?php
// test_db_simple.php
require 'db_connect.php';

echo "✅ 数据库连接成功！<br>";
echo "当前数据库: " . $conn->query("SELECT DATABASE()")->fetch_array()[0] . "<br>";

// 检查核心表是否存在
$tables = ['users', 'foods', 'intake_records'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    echo ($result->num_rows > 0 ? "✅" : "❌") . " 表 $table 存在<br>";
}

// 测试数据读写
$test_user = $conn->query("SELECT username FROM users LIMIT 1");
if ($test_user->num_rows > 0) {
    echo "✅ 有用户数据: " . $test_user->fetch_assoc()['username'] . "<br>";
} else {
    echo "⚠️ 没有用户数据，但表结构正常<br>";
}

$conn->close();
?>