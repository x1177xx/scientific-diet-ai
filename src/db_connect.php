<?php
$servername = "localhost:3307";
$username = "root";
$password = "root";
$dbname = "scientific_diet";

// 创建连接
$conn = new mysqli($servername, $username, $password, $dbname);

// 检查连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}
?>