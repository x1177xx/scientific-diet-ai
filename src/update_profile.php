<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// 获取表单数据
$username = $_POST['username'];
$password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
$gender = $_POST['gender'];
$age = (int)$_POST['age'];
$height = (float)$_POST['height'];
$weight = (float)$_POST['weight'];

// 更新用户信息
if ($password) {
    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, gender = ?, age = ?, height = ?, weight = ? WHERE user_id = ?");
    $stmt->bind_param("sssiddi", $username, $password, $gender, $age, $height, $weight, $user_id);
} else {
    $stmt = $conn->prepare("UPDATE users SET username = ?, gender = ?, age = ?, height = ?, weight = ? WHERE user_id = ?");
    $stmt->bind_param("ssiddi", $username, $gender, $age, $height, $weight, $user_id);
}

if ($stmt->execute()) {
    $_SESSION['username'] = $username;
    header("Location: dashboard.php");
} else {
    header("Location: profile.php?error=1");
}
exit();
?>