<?php
// test_calorie_logic.php - 测试热量计算逻辑
echo "<h2>🧮 测试热量计算逻辑</h2>";

// 测试数据
$testCases = [
    ['today' => 0, 'goal' => 1950, 'desc' => '没有数据'],
    ['today' => 1000, 'goal' => 1950, 'desc' => '严重不足'],
    ['today' => 1700, 'goal' => 1950, 'desc' => '不足'],
    ['today' => 1950, 'goal' => 1950, 'desc' => '正好'],
    ['today' => 2100, 'goal' => 1950, 'desc' => '略高'],
    ['today' => 2500, 'goal' => 1950, 'desc' => '严重超标'],
];

echo "<h3>计算公式：差值 = 目标 - 今日</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>今日摄入</th><th>目标</th><th>差值</th><th>状态</th><th>预期建议</th></tr>";

foreach ($testCases as $case) {
    $diff = $case['goal'] - $case['today'];
    
    if ($case['today'] == 0) {
        $status = '没有数据';
        $advice = '记录饮食数据';
    } elseif ($diff > 500) {
        $status = '严重不足';
        $advice = '增加热量';
    } elseif ($diff > 100) {
        $status = '不足';
        $advice = '适当增加';
    } elseif ($diff < -500) {
        $status = '严重超标';
        $advice = '减少热量';
    } elseif ($diff < -100) {
        $status = '略高';
        $advice = '适当减少';
    } else {
        $status = '均衡';
        $advice = '保持';
    }
    
    echo "<tr>";
    echo "<td>{$case['today']}kcal</td>";
    echo "<td>{$case['goal']}kcal</td>";
    echo "<td>{$diff}kcal</td>";
    echo "<td>{$status}</td>";
    echo "<td>{$advice}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>实际测试：</h3>";
echo "<button onclick='testActual()'>测试实际API</button>";
echo "<div id='testResult' style='margin-top:10px;'></div>";

echo "<script>
function testActual() {
    fetch('simple_ai_no_emoji.php')
        .then(response => response.json())
        .then(data => {
            let html = '<div style=\"background:#f5f5f5; padding:10px;\">';
            if (data.success) {
                html += '<strong>✅ 成功生成建议</strong><br>';
                html += '类型: ' + data.data.type + '<br>';
                html += '<pre style=\"white-space: pre-wrap;\">' + data.data.content + '</pre>';
                if (data.data.metrics) {
                    html += '<strong>详细数据：</strong><br>';
                    html += '今日摄入: ' + data.data.metrics.today_calories + 'kcal<br>';
                    html += '目标: ' + data.data.metrics.goal_calories + 'kcal<br>';
                    html += '差值: ' + data.data.metrics.difference + 'kcal';
                }
            } else {
                html += '<strong>❌ 失败</strong><br>';
                html += data.message + '<br>';
                if (data.debug) {
                    html += '调试信息: ' + JSON.stringify(data.debug);
                }
            }
            html += '</div>';
            document.getElementById('testResult').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('testResult').innerHTML = '错误: ' + error;
        });
}
</script>";

echo "<h3>快速验证：</h3>";
echo "<p>请手动记录一些高热量的食物（如：炸鸡、披萨、蛋糕等），让今日摄入超过目标值，然后测试AI建议。</p>";
?>