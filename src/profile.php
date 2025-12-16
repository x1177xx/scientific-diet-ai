<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// 获取用户信息
$user_id = $_SESSION["user_id"];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人主页</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fc;
            color: var(--dark-color);
        }
        
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
        }
        
        .profile-card {
            border-radius: 0.75rem;
            box-shadow: 0 0.3rem 0.8rem rgba(0,0,0,0.1);
            border: none;
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            padding: 1.5rem;
            position: relative;
        }
        
        .profile-header h2 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .profile-header .bi-person {
            font-size: 2.5rem;
            margin-right: 1rem;
        }
        
        .profile-body {
            padding: 2rem;
            background-color: white;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            border: 1px solid #e3e6f0;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-save {
            background-color: var(--primary-color);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-save:hover {
            background-color: #3a5bc7;
            transform: translateY(-2px);
        }
        
        .avatar-container {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 0.2rem 0.5rem rgba(0,0,0,0.1);
        }
        
        .avatar-container .bi-person {
            font-size: 3.5rem;
            color: var(--secondary-color);
        }
        
        /* 响应式调整 */
        @media (max-width: 768px) {
            .profile-container {
                padding: 0 1rem;
            }
            
            .profile-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container py-5">
        <div class="card profile-card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">个人资料</h4>
            </div>
            <div class="card-body">
                <form id="profileForm" action="update_profile.php" method="post">
                    <div class="mb-3">
                        <label class="form-label">用户ID</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['user_id']) ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="留空则不修改">
                    </div>
                    
                    <div class="mb-3">
    <label for="gender" class="form-label">性别</label>
    <input type="text" class="form-control" id="gender" name="gender" 
           value="<?= htmlspecialchars($user['gender']) ?>" required>
</div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="age" class="form-label">年龄</label>
                            <input type="number" class="form-control" id="age" name="age" 
                                   value="<?= htmlspecialchars($user['age']) ?>" min="1" max="120" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="height" class="form-label">身高 (cm)</label>
                            <input type="number" class="form-control" id="height" name="height" 
                                   value="<?= htmlspecialchars($user['height']) ?>" min="100" max="250" step="0.1" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="weight" class="form-label">体重 (kg)</label>
                            <input type="number" class="form-control" id="weight" name="weight" 
                                   value="<?= htmlspecialchars($user['weight']) ?>" min="30" max="300" step="0.1" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">保存并退出</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>