@echo off
chcp 65001 >nul
title 科学饮食系统 - 一键启动

echo ========================================
echo        科学饮食系统 AI版
echo ========================================
echo.

echo [1/4] 修复docker-compose文件...
REM 移除可能引起警告的version行
if exist docker-compose.yml (
    findstr /v /c:"version:" docker-compose.yml > temp.yml
    move /y temp.yml docker-compose.yml >nul
    echo   已移除版本警告
)

echo [2/4] 构建和启动服务...
echo   首次运行需要下载镜像，请耐心等待...
docker-compose up -d

if %errorlevel% neq 0 (
    echo   启动失败！
    echo   按任意键查看详细错误...
    pause >nul
    docker-compose logs
    pause
    exit /b 1
)

echo [3/4] 等待服务初始化（30秒）...
echo   正在启动：PHP + MySQL + phpMyAdmin...
timeout /t 30 /nobreak >nul

echo [4/4] 检查服务状态...
docker-compose ps

echo.
echo ========================================
echo            ✅ 启动成功！
echo ========================================
echo.
echo 访问地址：
echo   [主系统]     http://localhost:8080
echo   [数据库管理] http://localhost:8081
echo.
echo 测试账户：
echo   系统登录：testuser / 123456
echo   数据库管理：root / root123
echo.
echo 管理命令：
echo   查看日志：docker-compose logs
echo   停止服务：docker-compose down
echo.
echo 按任意键打开浏览器...
pause >nul
start http://localhost:8080
exit /b 0