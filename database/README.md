# 数据库说明

## 文件说明
- `scientific_diet_full.sql` - 完整数据库（包含AI表）

## 表结构
主要表：
1. `users` - 用户信息
2. `foods` - 食物营养成分
3. `intake_records` - 饮食记录
4. `daily_nutrition` - 每日营养汇总
5. `nutrition_recommendations` - 营养推荐值表
6. `ai_recommendations` - **AI建议表**

## 导入方法
```bash
# 方法1：命令行导入
mysql -u root -p < scientific_diet_full.sql

# 方法2：phpMyAdmin导入
# 1. 登录phpMyAdmin
# 2. 新建数据库 scientific_diet
# 3. 选择该数据库
# 4. 点击"导入"
# 5. 选择本SQL文件，字符集选utf8mb4
```