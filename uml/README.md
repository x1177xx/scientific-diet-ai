🍽️ 科学饮食系统 - AI版

📋 项目简介

基于PHP+MySQL的智能饮食管理系统，内嵌AI营养顾问智能体，实现感知-决策-执行闭环。

🎯 核心功能

- ✅ 用户注册登录
- ✅ 饮食记录管理
- ✅ 营养数据统计
- ✅ AI智能体建议（5种决策分支）
- ✅ 一键部署运行

🚀 一键运行（两种方式任选）

方式一：传统部署（推荐，无需Docker）

    # 1. 安装环境（如果已安装可跳过）
    #   - PHP 7.4+ 
    #   - MySQL 5.7+
    #   - Apache/Nginx
    
    # 2. 导入数据库
    mysql -u root -p < database/scientific_diet_full.sql
    
    # 3. 配置数据库连接
    #   修改 src/db_connect.php 中的数据库信息：
    #   $servername = "localhost";
    #   $username = "root";
    #   $password = "你的密码";
    
    # 4. 复制文件到Web目录
    cp -r src/* /var/www/html/
    
    # 5. 访问系统
    #   打开浏览器访问：http://localhost
    #   或：http://localhost:8080（取决于你的配置）

方式二：Docker部署（容器化）

    # 1. 确保已安装Docker和Docker Compose
    
    # 2. 启动服务
    cd deploy
    docker-compose up -d
    
    # 3. 等待启动（约30秒）
    
    # 4. 访问系统
    #   主系统：http://localhost:8080
    #   数据库管理：http://localhost:8081（用户名root，密码root123）
    
    # 5. 停止服务
    docker-compose down

方式三：极速测试

    # 如果你用XAMPP/WAMP：
    # 1. 把整个项目文件夹放到htdocs/www目录
    # 2. 启动Apache和MySQL
    # 3. 导入数据库（phpMyAdmin中导入scientific_diet_full.sql）
    # 4. 访问：http://localhost/科学饮食系统_AI版/src/

🔧 快速验证

测试账户

- 用户名：test7
- 密码：1234

验证步骤

1. 访问注册页面：http://localhost/register.php
2. 注册新用户
3. 登录进入仪表盘
4. 添加几种食物记录
5. 点击"生成AI建议"按钮
6. 查看个性化营养建议
7. 查看历史建议

🧪 AI智能体验证

系统已实现 5种决策分支：

1. 热量严重超标 → 减重建议
2. 热量超标 → 运动建议
3. 热量不足 → 增重建议
4. 蛋白质不足 → 蛋白质补充建议
5. 营养均衡 → 维持建议

验证方法：

- 添加不同热量水平的食物
- 点击"生成AI建议"
- 查看AI针对不同状态的建议

📊 技术栈

- 后端：PHP 7.4+（纯面向对象）
- 数据库：MySQL 5.7+（持久化存储）
- 前端：HTML5 + Bootstrap 5 + Chart.js
- AI智能体：PHP规则引擎（5种决策分支）
- 部署：支持传统部署和Docker一键部署

🐛 常见问题

Q1：数据库连接失败

检查 src/db_connect.php 中的数据库配置：

php

    $servername = "localhost";   // 或 "localhost:3307"
    $username = "root";         // 你的MySQL用户名
    $password = "root";         // 你的MySQL密码

Q2：AI建议生成失败

系统有备用机制，如果AI功能失效，会自动生成模拟建议。

Q3：页面显示错误

确保PHP版本≥7.4，并已安装MySQL扩展。

📞 技术支持

如果遇到问题：

1. 查看浏览器控制台错误信息（F12）
2. 检查PHP错误日志
3. 确保数据库已正确导入
