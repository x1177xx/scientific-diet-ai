<?php
require 'db_connect.php';

session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// 获取用户信息
$user_id = $_SESSION["user_id"];
$stmt = $conn->prepare("SELECT gender, age, height, weight FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 计算每日营养需求
function calculateNutritionGoals($user) {
    // 基础代谢率 (BMR) - Mifflin-St Jeor 公式
    if ($user['gender'] == 'male') {
        $bmr = 10 * $user['weight'] + 6.25 * $user['height'] - 5 * $user['age'] + 5;
    } else {
        $bmr = 10 * $user['weight'] + 6.25 * $user['height'] - 5 * $user['age'] - 161;
    }
    
    // 活动系数 (假设中等活动量)
    $activity_factor = 1.55;
    $daily_calories = round($bmr * $activity_factor);
    
    // 宏量营养素分配 (标准比例)
    return [
        'caloriesGoal' => $daily_calories,
        'proteinGoal' => round($user['weight'] * 1.8),  // 1.8g/kg体重
        'carbohydratesGoal' => round($daily_calories * 0.5 / 4), // 50%热量来自碳水
        'fatGoal' => round($daily_calories * 0.3 / 9)    // 30%热量来自脂肪
    ];
}

$nutritionGoals = calculateNutritionGoals($user);

$_SESSION['nutrition_goals'] = $nutritionGoals;
$_SESSION['user_info'] = $user;
$_SESSION['dashboard_calculated_at'] = date('Y-m-d H:i:s');

echo '<script>';
echo 'window.dashboardData = ' . json_encode([
    'nutritionGoals' => $nutritionGoals,
    'userInfo' => $user,
    'calculatedAt' => date('Y-m-d H:i:s')
]) . ';';
echo '</script>';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>科学饮食系统 - 仪表盘</title>
    <!-- 引入Bootstrap和图标 -->
    <!-- 引入Bootstrap和图标 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
<!-- 自定义CSS -->
<style>
    :root {
        --primary-color: #4e73df;
        --secondary-color: #858796;
        --success-color: #1cc88a;
        --info-color: #36b9cc;
        --warning-color: #f6c23e;
        --danger-color: #e74a3b;
        --light-color: #f8f9fc;
        --dark-color: #5a5c69;
    }
    
    body {
        font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background-color: #f8f9fc;
        color: var(--dark-color);
    }
    
    /* 侧边栏样式 */
    .sidebar {
        background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
        min-height: 100vh;
        transition: all 0.3s;
    }
    
    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 0.75rem 1rem;
        margin-bottom: 0.2rem;
        border-radius: 0.35rem;
    }
    
    .sidebar .nav-link:hover {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .sidebar .nav-link.active {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.2);
    }
    
    .sidebar .nav-link i {
        margin-right: 0.5rem;
    }
    
    /* 卡片样式 */
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 0.15rem 0.5rem rgba(0,0,0,0.1);
        border: none;
        margin-bottom: 1.5rem;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.15);
    }
    
    .card-header {
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        padding: 1rem 1.35rem;
        font-weight: 600;
        color: var(--dark-color);
    }
    
    /* 进度条样式 */
    .progress {
        height: 1rem;
        border-radius: 0.35rem;
        background-color: #eaecf4;
    }
    
    .progress-bar {
        background-color: var(--primary-color);
    }
    
    /* 营养卡片特殊样式 */
    .nutrition-card {
        border-left: 0.25rem solid var(--primary-color);
    }
    
    .nutrition-card.calories {
        border-left-color: var(--danger-color);
    }
    
    .nutrition-card.protein {
        border-left-color: var(--success-color);
    }
    
    .nutrition-card.carbs {
        border-left-color: var(--warning-color);
    }
    
    .nutrition-card.fat {
        border-left-color: var(--info-color);
    }
    
    /* 图表容器 */
    .chart-container {
        position: relative;
        height: 300px;
    }
    
    /* 按钮样式 */
    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    /* 响应式调整 */
    @media (max-width: 768px) {
        .sidebar {
            min-height: auto;
            width: 100%;
        }
        
        .nutrition-cards .col-md-3 {
            margin-bottom: 1rem;
        }
        
        .chart-container {
            height: 250px;
        }
    }
    
    @media (max-width: 576px) {
        .card-header {
            padding: 0.75rem 1rem;
        }
        
        .nutrition-card .card-body {
            padding: 1rem;
        }
    }

    body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* 主容器布局 */
.container-fluid {
    padding-left: 0;
    padding-right: 0;
    display: flex;
    flex: 1;
}

.row {
    margin-left: 0;
    margin-right: 0;
    width: 100%;
}

/* 主内容区域调整 */
main {
    padding: 20px;
    width: 100%;
    margin-left: 0; /* 确保没有左侧偏移 */
}

/* 导航栏调整 */
.navbar {
    padding-left: 1rem;
    padding-right: 1rem;
}

/* 响应式调整 */
@media (max-width: 768px) {
    main {
        padding: 15px;
    }
}
</style>
</head>
<body>
    <!-- 导航栏 -->
<nav class="navbar navbar-expand navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-egg-fried me-2"></i>科学饮食系统
        </a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i>
                    <span id="usernameDisplay"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>个人主页</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>退出</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

    <div class="container-fluid px-0">
        <div class="row g-0 mx-0">
            <!-- 主内容区 -->
            <main class="col-12 px-4 py-4">
                <h2 class="h4 mb-4">今日营养摄入</h2>
                
                <!-- 营养统计卡片 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-start border-primary border-4">
                            <div class="card-body">
                                <h6 class="card-title text-primary">热量</h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="mb-0" id="calories">0</h3>
                                    <span class="text-muted">/ <span id="caloriesGoal"><?= $nutritionGoals['caloriesGoal'] ?></span> kcal</span>
                                </div>
                                <div class="progress mt-2" id="caloriesProgress">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-start border-primary border-4">
                            <div class="card-body">
                                <h6 class="card-title text-primary">蛋白质</h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="mb-0" id="protein">0</h3>
                                    <span class="text-muted">/ <span id="proteinGoal"><?= $nutritionGoals['proteinGoal'] ?></span> g</span>
                                </div>
                                <div class="progress mt-2" id="proteinProgress">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-start border-primary border-4">
                            <div class="card-body">
                                <h6 class="card-title text-primary">碳水化合物</h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="mb-0" id="carbohydrates">0</h3>
                                    <span class="text-muted">/ <span id="carbohydratesGoal"><?= $nutritionGoals['carbohydratesGoal'] ?></span> g</span>
                                </div>
                                <div class="progress mt-2" id="carbohydratesProgress">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 0%"></div>
                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-start border-primary border-4">
                            <div class="card-body">
                                <h6 class="card-title text-primary">脂肪</h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="mb-0" id="fat">0</h3>
                                    <span class="text-muted">/ <span id="fatGoal"><?= $nutritionGoals['fatGoal'] ?></span> g</span>
                                </div>
                                <div class="progress mt-2" id="fatProgress">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 添加摄入记录表单 -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">添加摄入记录</h5>
                    </div>
                    <div class="card-body">
                        <form id="addFoodForm">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label">食物名称</label>
                                    <select class="form-select" id="foodSelect" required>
                                        <option value="">-- 选择食物 --</option>
                                        <!-- 动态加载食物选项 -->
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">摄入量 (克)</label>
                                    <input type="number" class="form-control" id="foodAmount" min="1" value="100" required>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-plus-circle"></i> 添加记录
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- AI营养顾问卡片 -->
<div class="card mb-4">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-robot"></i> AI营养顾问
        </h5>
        <small id="aiStatus" class="badge bg-light text-dark">待生成</small>
    </div>
    <div class="card-body">
        <!-- AI建议显示区域 -->
        <div id="aiAdviceContent" class="mb-3" style="display:none;">
            <div class="alert alert-info">
                <h6><i class="bi bi-lightbulb"></i> 今日AI建议</h6>
                <div id="adviceText"></div>
                <hr>
                <small class="text-muted" id="adviceMeta"></small>
            </div>
        </div>
        
        <!-- 控制按钮 -->
        <div class="d-flex gap-2">
            <button id="generateAdviceBtn" class="btn btn-primary" onclick="generateAIAdvice()">
                <i class="bi bi-magic"></i> 生成AI建议
            </button>
            <button id="viewHistoryBtn" class="btn btn-outline-secondary" onclick="toggleHistory()">
                <i class="bi bi-clock-history"></i> 历史建议
            </button>
        </div>
        
        <!-- 历史建议区域 -->
        <div id="historySection" class="mt-3" style="display:none;">
            <h6><i class="bi bi-list-ul"></i> 历史建议</h6>
            <div id="historyList" class="list-group">
                <!-- 动态加载历史建议 -->
            </div>
        </div>
    </div>
</div>
                <!-- 今日摄入记录表格 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">今日摄入记录</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="foodRecordsTable">
                                <thead>
                                    <tr>
                                        <th>食物名称</th>
                                        <th>分类</th>
                                        <th>摄入量 (g)</th>
                                        <th>热量 (kcal)</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 动态加载食物记录 -->
                                    <tr>
                                        <td colspan="5" class="text-center">今天还没有记录食物</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript 库 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- 图表库 -->
    
    <!-- 自定义JS -->
    <script>
      // 全局变量存储营养数据
      let nutritionData = {
          calories: 0,
          protein: 0,
          carbohydrates: 0,
          fat: 0
      };

      // 动态加载食物选项（增强版）
      async function loadFoodOptions() {
          const select = document.getElementById('foodSelect');
          const loadingOption = document.createElement('option');
          loadingOption.value = "";
          loadingOption.textContent = "加载中...";
          loadingOption.disabled = true;
          select.innerHTML = '';
          select.appendChild(loadingOption);

          try {
              const response = await fetch('./get_foods.php');
              if (!response.ok) {
                  throw new Error(`请求失败: ${response.status}`);
              }
              const result = await response.json();

              // 清空并重建下拉菜单
              select.innerHTML = '<option value="" selected disabled>-- 选择食物 --</option>';

              for (const [category, foods] of Object.entries(result.data)) {
                  const optgroup = document.createElement('optgroup');
                  optgroup.label = category;

                  foods.forEach(food => {
                      const option = document.createElement('option');
                      option.value = food.food_name;
                      option.dataset.calories = food.nutrition.calories;
                      option.dataset.protein = food.nutrition.protein;
                      option.dataset.carbohydrates = food.nutrition.carbohydrates;
                      option.dataset.fat = food.nutrition.fat;
                      option.textContent = `${food.food_name} (${food.nutrition.calories}kcal)`;
                      optgroup.appendChild(option);
                  });

                  select.appendChild(optgroup);
              }

              // 加载完成后自动加载今日记录
              loadTodayIntake();

          } catch (error) {
              console.error('加载食物列表失败:', error);
              select.innerHTML = '<option value="" disabled>加载失败，点击重试</option>';
              select.onclick = () => {
                  select.onclick = null;
                  loadFoodOptions();
              };
              showAlert('danger', '食物列表加载失败: ' + error.message);
          }
      }

      // 加载今日摄入数据
      async function loadTodayIntake() {
    try {
        const response = await fetch('get_today_intake.php');
        
        // 先检查HTTP状态码
        if (!response.ok) {
            const text = await response.text();
            throw new Error(`HTTP错误 ${response.status}: ${text}`);
        }
        
        // 尝试解析JSON
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || '数据加载失败');
        }
        
        updateNutritionDisplay(result.data);
        renderFoodRecords(result.records);
        
    } catch (error) {
        console.error('加载今日数据失败:', error);
        showAlert('warning', '今日数据加载失败: ' + error.message);
        
        // 显示空的表格
        document.querySelector('#foodRecordsTable tbody').innerHTML = 
            '<tr><td colspan="5" class="text-center">加载数据失败</td></tr>';
    }
}

      // 更新营养数据显示
      function updateNutritionDisplay(data) {
          nutritionData = data;

          document.getElementById('calories').textContent = Math.round(data.calories);
          document.getElementById('protein').textContent = Math.round(data.protein);
          document.getElementById('carbohydrates').textContent = Math.round(data.carbohydrates);
          document.getElementById('fat').textContent = Math.round(data.fat);

          // 获取目标值
        const caloriesGoal = parseInt(document.getElementById('caloriesGoal').textContent) || 2000;
        const proteinGoal = parseInt(document.getElementById('proteinGoal').textContent) || 150;
        const carbohydratesGoal = parseInt(document.getElementById('carbohydratesGoal').textContent) || 300;
        const fatGoal = parseInt(document.getElementById('fatGoal').textContent) || 80;

          updateProgressBar('caloriesProgress', data.calories, caloriesGoal);
          updateProgressBar('proteinProgress', data.protein, proteinGoal);
          updateProgressBar('carbohydratesProgress', data.carbohydrates, carbohydratesGoal);
          updateProgressBar('fatProgress', data.fat, fatGoal);
      }

      // 更新进度条
      function updateProgressBar(id, current, max) {
          const percentage = Math.min(Math.round((current / max) * 100), 100);
          const progressBar = document.getElementById(id);
          progressBar.style.width = `${percentage}%`;
          progressBar.setAttribute('aria-valuenow', percentage);

          if (percentage > 90) {
              progressBar.classList.remove('bg-success', 'bg-warning');
              progressBar.classList.add('bg-danger');
          } else if (percentage > 70) {
              progressBar.classList.remove('bg-success', 'bg-danger');
              progressBar.classList.add('bg-warning');
          } else {
              progressBar.classList.remove('bg-warning', 'bg-danger');
              progressBar.classList.add('bg-success');
          }
      }

      // 渲染食物记录表格
      function renderFoodRecords(records) {
    const tbody = document.querySelector('#foodRecordsTable tbody');
    tbody.innerHTML = '';

    if (records.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">今天还没有记录食物</td></tr>';
        return;
    }

    records.forEach(record => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${record.food_name}</td>
            <td>${record.category || '未分类'}</td>
            <td>${record.amount}g</td>
            <td>${Math.round(record.calories)}kcal</td>
            <td>
                <button class="btn btn-sm btn-danger delete-btn" 
                        data-record-id="${record.record_id}"
                        data-user-id="${record.user_id}" 
                        data-intake-date="${record.intake_date}">
                    <i class="bi bi-trash"></i> 删除
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    // 重新绑定事件
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', deleteFoodRecord);
    });
}

// 删除食物记录
async function deleteFoodRecord(e) {
    const btn = e.currentTarget;
    const recordId = btn.dataset.recordId;
    const userId = btn.dataset.userId;
    const intakeDate = btn.dataset.intakeDate;
    const recordElement = btn.closest('.food-record'); // 获取记录元素

    if (!recordId || !userId || !intakeDate) {
        showAlert('danger', '缺少必要参数');
        return;
    }

    if (!confirm('确定要删除这条记录吗？')) return;

    const originalHtml = btn.innerHTML;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> 删除中...`;
    btn.disabled = true;

    try {
        const response = await fetch('./delete_intake.php', {
            method: 'POST', // 改为POST方法，与后端一致
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                record_id: recordId,
                user_id: userId,
                intake_date: intakeDate
            })
        });

        const result = await response.json();
        
        if (!response.ok || !result.success) {
            throw new Error(result.message || `删除失败 (HTTP ${response.status})`);
        }

        // 立即从UI中移除记录，提供更快的反馈
        if (recordElement) {
            recordElement.style.opacity = '0';
            setTimeout(() => {
                recordElement.remove();
                updateNutritionTotals(); // 更新总营养数据
            }, 300); // 添加淡出动画
        } else {
            loadTodayIntake(); // 如果找不到具体元素，则刷新整个列表
        }

        showAlert('success', '记录已删除');

    } catch (error) {
        console.error('删除失败:', error);
        showAlert('danger', '删除失败: ' + error.message);
    } finally {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }
}

      // 显示提示消息
      function showAlert(type, message) {
          const alertDiv = document.createElement('div');
          alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
          alertDiv.innerHTML = `
              ${message}
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          `;

          const container = document.querySelector('.container-fluid');
          container.prepend(alertDiv);

          setTimeout(() => {
              alertDiv.classList.remove('show');
              setTimeout(() => alertDiv.remove(), 150);
          }, 5000);
      }

      document.addEventListener('DOMContentLoaded', function() {
          loadFoodOptions();
          document.getElementById('addFoodForm').addEventListener('submit', async function(e) {
              e.preventDefault();
              const form = e.target;
              const submitBtn = form.querySelector('button[type="submit"]');
              const originalBtnText = submitBtn.innerHTML;

              const foodSelect = document.getElementById('foodSelect');
              const selectedOption = foodSelect.selectedOptions[0];

              if (!selectedOption || !selectedOption.value) {
                  showAlert('warning', '请选择食物');
                  return;
              }

              const amount = parseFloat(document.getElementById('foodAmount').value);
              if (isNaN(amount) || amount <= 0) {
                  showAlert('warning', '请输入有效的摄入量');
                  return;
              }

              try {
                  submitBtn.disabled = true;
                  submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> 提交中...`;

                  const calories = (selectedOption.dataset.calories * amount / 100).toFixed(1);
                  const protein = (selectedOption.dataset.protein * amount / 100).toFixed(1);
                  const carbohydrates = (selectedOption.dataset.carbohydrates * amount / 100).toFixed(1);
                  const fat = (selectedOption.dataset.fat * amount / 100).toFixed(1);

                  const response = await fetch('./add_intake.php', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json' },
                      body: JSON.stringify({
                        food_name: selectedOption.value,
                        amount,
                        nutrition: {
                        calories: parseFloat(selectedOption.dataset.calories),  // 确保是浮动数字
                        protein: parseFloat(selectedOption.dataset.protein),
                        carbohydrates: parseFloat(selectedOption.dataset.carbohydrates),
                        fat: parseFloat(selectedOption.dataset.fat)
                    }
                })
                

                  });

                  const result = await response.json();

                  if (result.success) {
                      showAlert('success', '记录添加成功');
                      nutritionData.calories += parseFloat(calories);
                      nutritionData.protein += parseFloat(protein);
                      nutritionData.carbohydrates += parseFloat(carbohydrates);
                      nutritionData.fat += parseFloat(fat);

                      updateNutritionDisplay(nutritionData);
                      loadTodayIntake();
                      document.getElementById('foodAmount').value = '100';
                  } else {
                      throw new Error(result.message || '添加失败');
                  }
              } catch (error) {
                  console.error('添加摄入记录失败:', error);
                  showAlert('danger', '添加失败: ' + error.message);
              } finally {
                  submitBtn.disabled = false;
                  submitBtn.innerHTML = originalBtnText;
              }
          });
      });
      // ==================== AI建议功能 ====================

let aiAdviceHistory = [];

// 生成AI建议
// 在dashboard.php的JavaScript部分，更新generateAIAdvice函数：
// 修复的AI建议生成函数
// 更新generateAIAdvice函数
async function generateAIAdvice() {
    const btn = document.getElementById('generateAdviceBtn');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> AI思考中...';
    btn.disabled = true;
    
    try {
        // 尝试多个接口，直到一个成功
        const endpoints = [
            'generate_ai_advice.php',
            'ai_simple.php',
            'force_new_ai.php'
        ];
        
        let result = null;
        let lastError = null;
        
        for (const endpoint of endpoints) {
            try {
                console.log(`尝试接口: ${endpoint}`);
                const response = await fetch(endpoint);
                
                if (!response.ok) {
                    throw new Error(`HTTP错误 ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    result = data;
                    console.log(`✅ 接口 ${endpoint} 成功`);
                    break;
                } else {
                    throw new Error(data.message || '接口返回失败');
                }
            } catch (err) {
                lastError = err;
                console.warn(`接口 ${endpoint} 失败:`, err.message);
                continue;
            }
        }
        
        if (result && result.data) {
            // 显示建议内容
            document.getElementById('adviceText').innerHTML = 
                formatAdviceText(result.data.content);
            document.getElementById('adviceMeta').innerHTML = 
                `生成时间: ${result.data.generated_at} | 类型: ${result.data.type} | 来源: ${result.data.ai_provider || '未知'}`;
            document.getElementById('aiAdviceContent').style.display = 'block';
            
            // 更新状态
            const statusBadge = document.getElementById('aiStatus');
            statusBadge.className = 'badge bg-success';
            statusBadge.textContent = '今日已生成';
            
            showAlert('success', 'AI建议生成成功！');
            
            // 立即刷新历史记录
            await loadAdviceHistory();
            
        } else {
            throw new Error(lastError?.message || '所有接口都失败了');
        }
        
    } catch (error) {
        console.error('AI建议生成失败:', error);
        showAlert('danger', 'AI建议生成失败: ' + error.message);
        
        // 显示一个基本的模拟建议
        document.getElementById('adviceText').innerHTML = 
            "抱歉，AI建议生成失败。请检查网络连接或稍后重试。<br><br>" +
            "临时建议：保持均衡饮食，适量运动，多喝水。";
        document.getElementById('aiAdviceContent').style.display = 'block';
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// 加强的格式处理函数
function formatAdviceText(text) {
    if (!text) return '无建议内容';
    
    // 安全处理
    let safeText = text.toString();
    
    // 处理Markdown格式
    safeText = safeText
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>')
        .replace(/^#\s+(.*?)(?=\n|$)/gm, '<h5>$1</h5>')
        .replace(/^##\s+(.*?)(?=\n|$)/gm, '<h6>$1</h6>')
        .replace(/^(\d+\.\s+.*?)(?=\n|$)/gm, '<div class="mb-2"><strong>$1</strong></div>')
        .replace(/^[-•]\s+(.*?)(?=\n|$)/gm, '<div class="ms-3">• $1</div>');
    
    return safeText;
}

// 增强的历史记录加载
async function loadAdviceHistory() {
    try {
        const response = await fetch('get_ai_history.php?limit=10');
        const result = await response.json();
        
        if (result.success) {
            updateHistoryDisplay(result.data);
        } else {
            console.error('加载历史失败:', result.message);
        }
    } catch (error) {
        console.error('加载历史建议失败:', error);
        showAlert('warning', '加载历史记录失败');
    }
}

// 更新历史显示
function updateHistoryDisplay(historyData) {
    const historyList = document.getElementById('historyList');
    
    if (!historyData || historyData.length === 0) {
        historyList.innerHTML = '<div class="list-group-item text-muted">暂无历史建议</div>';
        return;
    }
    
    historyList.innerHTML = '';
    historyData.forEach(item => {
        const historyItem = document.createElement('div');
        historyItem.className = 'list-group-item';
        historyItem.innerHTML = `
            <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1">${getTypeIcon(item.type)} ${item.type}</h6>
                <small>${item.recommendation_date}</small>
            </div>
            <p class="mb-1">${item.content.substring(0, 100)}...</p>
            <small class="text-muted">${item.created_at} | ${item.is_ai_generated ? 'AI生成' : '模拟建议'}</small>
        `;
        historyItem.onclick = () => showAdviceDetail(item);
        historyList.appendChild(historyItem);
    });
}

// function formatAdviceText(text) {
//     // 将Markdown格式转换为HTML
//     return text
//         .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
//         .replace(/\n/g, '<br>')
//         .replace(/\- (.*?)(?=\n|$)/g, '• $1<br>');
// }

function getTypeIcon(type) {
    const icons = {
        'diet': '🍽️',
        'exercise': '🏃',
        'general': '💡'
    };
    return icons[type] || '📝';
}

// 显示建议详情
function showAdviceDetail(advice) {
    document.getElementById('adviceText').innerHTML = formatAdviceText(advice.content);
    document.getElementById('adviceMeta').innerHTML = 
        `生成时间: ${advice.created_at} | 类型: ${advice.type}`;
    document.getElementById('aiAdviceContent').style.display = 'block';
    
    // 滚动到建议区域
    document.getElementById('aiAdviceContent').scrollIntoView({ behavior: 'smooth' });
}

// 切换历史显示
function toggleHistory() {
    const historySection = document.getElementById('historySection');
    const btn = document.getElementById('viewHistoryBtn');
    
    if (historySection.style.display === 'none') {
        historySection.style.display = 'block';
        btn.innerHTML = '<i class="bi bi-chevron-up"></i> 收起历史';
        loadAdviceHistory();
    } else {
        historySection.style.display = 'none';
        btn.innerHTML = '<i class="bi bi-clock-history"></i> 历史建议';
    }
}

// 页面加载时初始化
document.addEventListener('DOMContentLoaded', function() {
    // 检查今天是否已有建议
    checkTodayAdvice();
    // 预加载历史
    loadAdviceHistory();
});

// 检查今日建议
async function checkTodayAdvice() {
    try {
        const response = await fetch('get_ai_history.php?limit=1');
        const result = await response.json();
        
        if (result.success && result.data.length > 0) {
            const today = new Date().toISOString().split('T')[0];
            const latestAdvice = result.data[0];
            
            if (latestAdvice.recommendation_date === today) {
                // 今天已有建议，自动显示
                showAdviceDetail(latestAdvice);
                document.getElementById('aiStatus').className = 'badge bg-success';
                document.getElementById('aiStatus').textContent = '今日已生成';
            }
        }
    } catch (error) {
        console.error('检查今日建议失败:', error);
    }
}

  </script>
</body>
</html>
