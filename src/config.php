<?php
// config.php - 配置文件

// Moonshot (Kimi) API配置
define('MOONSHOT_API_KEY', 'sk-74YajJnhmgC5nkEQzddNalehtZgKfNzAmz4s2ZAnoVh1Jv7t'); // 你的实际密钥
define('MOONSHOT_API_URL', 'https://api.moonshot.cn/v1');

// 保持向后兼容（可选）
define('DEEPSEEK_API_KEY', MOONSHOT_API_KEY); // 如果你有其他代码还在用这个
define('DEEPSEEK_API_URL', MOONSHOT_API_URL);

// 检测密钥是否有效
if (!defined('MOONSHOT_API_KEY') || empty(MOONSHOT_API_KEY)) {
    define('USE_MOCK_AI', true);
    error_log("⚠️ 使用模拟AI模式，请设置有效的Moonshot API密钥");
} else {
    define('USE_MOCK_AI', false);
    error_log("✅ 使用Moonshot API模式");
}

// API模型配置 - Moonshot支持的模型
define('AI_MODEL', 'moonshot-v1-8k'); // 或者 'moonshot-v1-32k', 'moonshot-v1-128k'
define('AI_TEMPERATURE', 0.7);
define('AI_MAX_TOKENS', 1000);

// 调试模式
define('AI_DEBUG', true);
?>