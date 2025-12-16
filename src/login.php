<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db_connect.php';

echo "login.php 开始执行<br>";  // ✅ 用于验证页面是否执行

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "收到POST请求<br>";  // ✅ 添加调试信息

    $username = $_POST["username"];
    $password = $_POST["password"];

    // 查询用户信息
    $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION["user_id"] = $user_id;
            $_SESSION["username"] = $username;

            header("Location: dashboard.php");
            exit();
        } else {
            echo "密码错误";
        }
    } else {
        echo "用户不存在";
    }

    $stmt->close();
    $conn->close();
}
?>
