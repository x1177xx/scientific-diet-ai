<?php
// AIAdvisor.php - 修复版本，防止重复定义

// 检查类是否已经定义
if (!class_exists('AIAdvisor')) {

class AIAdvisor {
    private $conn;
    private $apiKey;
    private $apiUrl;
    private $useMockAI;
    
    public function __construct($conn) {
        $this->conn = $conn;
        
        // 直接包含config，不要用require_once避免重复常量定义
        if (!defined('MOONSHOT_API_KEY')) {
            // 如果常量未定义，定义它们
            define('MOONSHOT_API_KEY', 'sk-74YajJnhmgC5nkEQzddNalehtZgKfNzAmz4s2ZAnoVh1Jv7t');
            define('MOONSHOT_API_URL', 'https://api.moonshot.cn/v1');
            define('USE_MOCK_AI', false);
            define('AI_MODEL', 'moonshot-v1-8k');
            define('AI_TEMPERATURE', 0.7);
            define('AI_MAX_TOKENS', 1000);
            define('AI_DEBUG', true);
        }
        
        $this->apiKey = MOONSHOT_API_KEY;
        $this->apiUrl = MOONSHOT_API_URL;
        $this->useMockAI = USE_MOCK_AI;
        
        if (AI_DEBUG) {
            error_log("AIAdvisor初始化 - 使用API: " . ($this->useMockAI ? '模拟模式' : 'Moonshot API'));
        }
    }
    
    // 其他方法保持不变...
    // 复制你原来的方法，但确保callMoonshotAPI中有SSL修复：
    private function callMoonshotAPI($userData, $intakeData, $nutritionGoals) {
        $prompt = $this->buildPrompt($userData, $intakeData, $nutritionGoals);
        
        $data = [
            'model' => AI_MODEL,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '你是一名专业的营养师和健身教练。请根据用户的营养数据提供个性化的、科学的、实用的饮食和运动建议。用中文回答，语气友好专业，提供具体可执行的建议。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => AI_TEMPERATURE,
            'max_tokens' => AI_MAX_TOKENS,
            'stream' => false
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . '/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 30,
            // SSL修复
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Moonshot API请求失败: HTTP {$httpCode}");
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception('API响应格式错误');
        }
        
        return $result['choices'][0]['message']['content'];
    }
    
    // 其他方法...
    // 确保所有方法都包含在这里
    
    public function generateAdvice($userId, $date = null) {
        // 你的原始代码
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        // 获取用户数据
        $userData = $this->getUserData($userId);
        if (!$userData) {
            throw new Exception('无法获取用户数据');
        }
        
        // 获取今日摄入数据
        $intakeData = $this->getTodayIntake($userId, $date);
        
        // 获取营养目标
        $nutritionGoals = $this->getNutritionGoals($userId);
        
        $advice = '';
        $isAIGenerated = false;
        
        if (!$this->useMockAI && !empty($this->apiKey)) {
            try {
                $advice = $this->callMoonshotAPI($userData, $intakeData, $nutritionGoals);
                $isAIGenerated = true;
            } catch (Exception $e) {
                error_log("API调用失败: " . $e->getMessage());
                $advice = $this->generateMockAdvice($userData, $intakeData, $nutritionGoals);
                $isAIGenerated = false;
            }
        } else {
            $advice = $this->generateMockAdvice($userData, $intakeData, $nutritionGoals);
            $isAIGenerated = false;
        }
        
        // 分析建议类型
        $type = $this->analyzeAdviceType($advice);
        
        // 保存到数据库
        $adviceId = $this->saveAdvice($userId, $date, $advice, $type, $isAIGenerated);
        
        return [
            'advice_id' => $adviceId,
            'content' => $advice,
            'type' => $type,
            'is_ai_generated' => $isAIGenerated,
            'ai_provider' => $isAIGenerated ? 'moonshot' : 'mock',
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function getUserData($userId) {
        $stmt = $this->conn->prepare("SELECT gender, age, height, weight FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }
    
    private function getTodayIntake($userId, $date) {
        $stmt = $this->conn->prepare("
            SELECT 
                COALESCE(SUM(f.calories * ir.amount / 100), 0) as calories,
                COALESCE(SUM(f.protein * ir.amount / 100), 0) as protein,
                COALESCE(SUM(f.carbohydrates * ir.amount / 100), 0) as carbohydrates,
                COALESCE(SUM(f.fat * ir.amount / 100), 0) as fat
            FROM intake_records ir
            JOIN foods f ON ir.food_name = f.food_name
            WHERE ir.user_id = ? AND ir.intake_date = ?
        ");
        $stmt->bind_param("is", $userId, $date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return [
            'calories' => floatval($result['calories'] ?? 0),
            'protein' => floatval($result['protein'] ?? 0),
            'carbohydrates' => floatval($result['carbohydrates'] ?? 0),
            'fat' => floatval($result['fat'] ?? 0)
        ];
    }
    
    private function getNutritionGoals($userId) {
        $userData = $this->getUserData($userId);
        
        if ($userData['gender'] == 'male' || $userData['gender'] == '男') {
            $bmr = 10 * $userData['weight'] + 6.25 * $userData['height'] - 5 * $userData['age'] + 5;
        } else {
            $bmr = 10 * $userData['weight'] + 6.25 * $userData['height'] - 5 * $userData['age'] - 161;
        }
        
        $daily_calories = round($bmr * 1.55);
        
        return [
            'caloriesGoal' => $daily_calories,
            'proteinGoal' => round($userData['weight'] * 1.8),
            'carbohydratesGoal' => round($daily_calories * 0.5 / 4),
            'fatGoal' => round($daily_calories * 0.3 / 9)
        ];
    }
    
    private function generateMockAdvice($userData, $intakeData, $nutritionGoals) {
        $caloriesPercent = round($intakeData['calories'] / $nutritionGoals['caloriesGoal'] * 100, 1);
        
        if ($intakeData['calories'] > $nutritionGoals['caloriesGoal'] * 1.2) {
            return "🚨 **热量摄入超标**\n\n今日摄入热量为{$intakeData['calories']}kcal，超过了推荐目标{$nutritionGoals['caloriesGoal']}kcal。\n\n**建议：**\n1. 减少晚餐的主食分量\n2. 增加30分钟有氧运动\n3. 多喝水促进新陈代谢\n4. 明天控制零食摄入";
        } elseif ($intakeData['calories'] < $nutritionGoals['caloriesGoal'] * 0.8) {
            return "⚠️ **热量摄入不足**\n\n今日摄入热量为{$intakeData['calories']}kcal，低于推荐目标{$nutritionGoals['caloriesGoal']}kcal。\n\n**建议：**\n1. 增加一份蛋白质食物（如鸡蛋、鸡胸肉）\n2. 适当增加健康脂肪（如坚果、牛油果）\n3. 考虑增加一餐点心\n4. 保持规律的力量训练";
        } else {
            return "✅ **饮食均衡良好**\n\n今日摄入热量为{$intakeData['calories']}kcal，完成度{$caloriesPercent}%，非常接近目标值！\n\n**保持建议：**\n1. 继续维持当前的饮食结构\n2. 确保蛋白质摄入充足\n3. 多样化蔬菜选择\n4. 保持适量运动\n\n**明日目标：**继续保持在{$nutritionGoals['caloriesGoal']}kcal左右";
        }
    }
    
    private function analyzeAdviceType($advice) {
        $advice = strtolower($advice);
        
        if (strpos($advice, '运动') !== false || strpos($advice, '锻炼') !== false) {
            return 'exercise';
        } elseif (strpos($advice, '饮食') !== false || strpos($advice, '食物') !== false) {
            return 'diet';
        } else {
            return 'general';
        }
    }
    
    private function saveAdvice($userId, $date, $content, $type, $isAIGenerated) {
        // 删除当天的旧建议
        $deleteStmt = $this->conn->prepare("DELETE FROM ai_recommendations WHERE user_id = ? AND recommendation_date = ?");
        $deleteStmt->bind_param("is", $userId, $date);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        // 插入新建议
        $insertStmt = $this->conn->prepare("
            INSERT INTO ai_recommendations 
            (user_id, recommendation_date, content, type, is_ai_generated, ai_provider) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $provider = $isAIGenerated ? 'moonshot' : 'mock';
        $isAIGeneratedInt = $isAIGenerated ? 1 : 0;
        
        $insertStmt->bind_param("isssis", $userId, $date, $content, $type, $isAIGeneratedInt, $provider);
        
        if (!$insertStmt->execute()) {
            throw new Exception('保存建议失败: ' . $this->conn->error);
        }
        
        $adviceId = $insertStmt->insert_id;
        $insertStmt->close();
        
        return $adviceId;
    }
    
    public function getHistory($userId, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM ai_recommendations 
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();
        
        return $history;
    }
}

} // 结束if (!class_exists('AIAdvisor'))
?>