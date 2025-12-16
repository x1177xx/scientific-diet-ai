<?php
// check_error.php - 检查PHP错误
echo "<h2>PHP错误检查</h2>";

// 显示所有错误
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 检查文件是否存在
$files = [
    'db_connect.php',
    'AIAdvisor.php',
    'config.php',
    'generate_ai_advice.php'
];

echo "<h3>1. 文件检查</h3>";
foreach ($files as $file) {
    echo "$file: " . (file_exists($file) ? "✅ 存在" : "❌ 不存在") . "<br>";
}

// 测试数据库连接
echo "<h3>2. 数据库连接测试</h3>";
try {
    require 'db_connect.php';
    echo "数据库连接: ✅ 成功<br>";
    echo "服务器信息: " . $conn->server_info . "<br>";
} catch (Exception $e) {
    echo "数据库连接: ❌ 失败 - " . $e->getMessage() . "<br>";
}

// 测试AIAdvisor
echo "<h3>3. AIAdvisor测试</h3>";
try {
    require 'AIAdvisor.php';
    $advisor = new AIAdvisor($conn);
    echo "AIAdvisor实例: ✅ 创建成功<br>";
} catch (Exception $e) {
    echo "AIAdvisor实例: ❌ 失败 - " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// 测试API配置
echo "<h3>4. API配置测试</h3>";
if (file_exists('config.php')) {
    require 'config.php';
    echo "API Key定义: " . (defined('MOONSHOT_API_KEY') ? "✅ 已定义" : "❌ 未定义") . "<br>";
    echo "API URL定义: " . (defined('MOONSHOT_API_URL') ? "✅ 已定义" : "❌ 未定义") . "<br>";
    echo "使用模拟AI: " . (defined('USE_MOCK_AI') ? (USE_MOCK_AI ? '是' : '否') : '未定义') . "<br>";
} else {
    echo "config.php文件不存在<br>";
}

// 直接测试generate_ai_advice.php
echo "<h3>5. 直接测试generate_ai_advice.php</h3>";
if (file_exists('generate_ai_advice.php')) {
    // 模拟会话
    session_start();
    $_SESSION['user_id'] = 4;
    
    ob_start();
    include 'generate_ai_advice.php';
    $output = ob_get_clean();
    
    echo "输出内容: <pre>" . htmlspecialchars($output) . "</pre>";
} else {
    echo "文件不存在，已在上方创建<br>";
}

echo "<hr><h3>环境信息</h3>";
echo "<pre>";
echo "PHP版本: " . PHP_VERSION . "\n";
echo "内存限制: " . ini_get('memory_limit') . "\n";
echo "最大执行时间: " . ini_get('max_execution_time') . "\n";
echo "时区: " . date_default_timezone_get() . "\n";
echo "当前目录: " . __DIR__ . "\n";
echo "</pre>";

$conn->close();
?>