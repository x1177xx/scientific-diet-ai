<?php
// fix_ssl_test.php - 修复SSL问题的测试
echo "<h2>SSL证书问题修复测试</h2>";

$testUrl = 'https://api.moonshot.cn/v1/models';

echo "<h3>测试1：不带SSL验证</h3>";
$ch1 = curl_init();
curl_setopt_array($ch1, [
    CURLOPT_URL => $testUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer sk-74YajJnhmgC5nkEQzddNalehtZgKfNzAmz4s2ZAnoVh1Jv7t'
    ]
]);

$response1 = curl_exec($ch1);
$httpCode1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
curl_close($ch1);

echo "HTTP状态码: $httpCode1<br>";
echo "响应: <pre>" . htmlspecialchars($response1) . "</pre>";

echo "<h3>测试2：带系统证书</h3>";

// 尝试不同证书路径
$certPaths = [
    '/etc/ssl/certs/ca-certificates.crt',  // Ubuntu/Debian
    '/etc/pki/tls/certs/ca-bundle.crt',    // CentOS/RHEL
    '/usr/local/etc/openssl/cert.pem',     // macOS Homebrew
    'cacert.pem',                          // 当前目录
    __DIR__ . '/cacert.pem',               // 脚本目录
];

foreach ($certPaths as $path) {
    echo "尝试证书路径: $path<br>";
    
    if (file_exists($path)) {
        $ch2 = curl_init();
        curl_setopt_array($ch2, [
            CURLOPT_URL => $testUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO => $path,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer sk-74YajJnhmgC5nkEQzddNalehtZgKfNzAmz4s2ZAnoVh1Jv7t'
            ]
        ]);
        
        $response2 = curl_exec($ch2);
        $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        $error2 = curl_error($ch2);
        curl_close($ch2);
        
        echo "结果: HTTP $httpCode2<br>";
        if ($httpCode2 === 200) {
            echo "<span style='color:green;'>✅ 成功！可以使用证书: $path</span><br>";
            break;
        } else {
            echo "<span style='color:red;'>❌ 失败: $error2</span><br>";
        }
    } else {
        echo "证书文件不存在<br>";
    }
    echo "<hr>";
}

// 提供解决方案
echo "<h3>解决方案：</h3>";
echo "<ol>
<li><strong>快速方案（开发环境）</strong>：修改AIAdvisor.php，设置CURLOPT_SSL_VERIFYPEER为false</li>
<li><strong>永久方案</strong>：下载CA证书包
    <pre>wget https://curl.se/ca/cacert.pem
# 然后在config.php中定义
define('SSL_CERT_PATH', __DIR__ . '/cacert.pem');</pre>
</li>
<li><strong>服务器配置</strong>：更新系统CA证书包
    <pre># Ubuntu/Debian
sudo apt-get update
sudo apt-get install ca-certificates

# CentOS/RHEL
sudo yum install ca-certificates</pre>
</li>
</ol>";

echo "<hr><a href='dashboard.php'>返回仪表盘</a>";
?>