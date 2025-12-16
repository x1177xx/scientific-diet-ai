<?php
session_start();

// // 如果已登录，重定向到仪表盘
// if (isset($_SESSION["user_id"])) {
//     header("Location: dashboard.php");
//     exit();
// }

require 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $gender = $_POST["gender"];
    $age = (int)$_POST["age"];
    $height = (float)$_POST["height"];
    $weight = (float)$_POST["weight"];

    // 验证密码匹配
    if ($password !== $confirm_password) {
        $error = "两次输入的密码不一致";
    } else {
        // 检查用户名是否已存在
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "用户名已存在";
        } else {
            // 插入用户信息
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, gender, age, height, weight) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssidd", $username, $hashed_password, $gender, $age, $height, $weight);

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;

                // 计算推荐摄入
                $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + ($gender === 'male' ? 5 : -161);
                $calories = $bmr * 1.375;
                $protein = $weight * 1.5;
                $fat = $calories * 0.25 / 9;
                $carbohydrates = ($calories - ($protein * 4 + $fat * 9)) / 4;

                // 插入营养建议
                $stmt2 = $conn->prepare("INSERT INTO nutrition_recommendations (user_id, calories, carbohydrates, fat, protein) VALUES (?, ?, ?, ?, ?)");
                $stmt2->bind_param("idddd", $user_id, $calories, $carbohydrates, $fat, $protein);
                $stmt2->execute();

                $success = "注册成功！即将跳转到登录页面...";
                echo "<meta http-equiv='refresh' content='3;url=login.html'>";
            } else {
                $error = "注册失败：" . $conn->error;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>科学饮食系统 - 注册</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            max-width: 800px;
            margin-top: 50px;
            margin-bottom: 50px;
        }
        
        .register-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background: white;
            transition: transform 0.3s ease;
        }
        
        .register-card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
            border-bottom: none;
        }
        
        .card-body {
            padding: 2.5rem;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .gender-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .gender-option {
            flex: 1;
            text-align: center;
        }
        
        .gender-option input[type="radio"] {
            display: none;
        }
        
        .gender-option label {
            display: block;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .gender-option input[type="radio"]:checked + label {
            border-color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .login-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .login-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .input-icon input {
            padding-left: 40px;
        }
        
        .feature-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .feature-card {
            padding: 20px;
            border-radius: 15px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            height: 100%;
            transition: all 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .password-meter {
            height: 5px;
            background-color: #e9ecef;
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .password-meter-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .register-container {
                margin-top: 20px;
                margin-bottom: 20px;
            }
            
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="register-card bg-white p-5 mx-auto">
            <h2 class="text-center mb-4">
                <i class="bi bi-egg-fried"></i> 用户注册
            </h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php else: ?>
            
            <form method="POST">
                <div class="row">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">确认密码</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="gender" class="form-label">性别</label>
                        <input type="text" class="form-control" id="gender" name="gender" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="age" class="form-label">年龄</label>
                        <input type="number" class="form-control" id="age" name="age" min="1" max="120" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="height" class="form-label">身高 (cm)</label>
                        <input type="number" class="form-control" id="height" name="height" min="100" max="250" step="0.1" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="weight" class="form-label">体重 (kg)</label>
                        <input type="number" class="form-control" id="weight" name="weight" min="30" max="300" step="0.1" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mt-3">
                    <i class="bi bi-person-plus"></i> 注册
                </button>
                
                <div class="mt-3 text-center">
                    <a href="login.html">已有账号？立即登录</a>
                </div>
            </form>
            
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>