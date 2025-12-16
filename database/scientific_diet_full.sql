CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    gender ENUM('男','女','其他') DEFAULT NULL,
    age INT DEFAULT NULL,
    height FLOAT DEFAULT NULL COMMENT '单位cm',
    weight FLOAT DEFAULT NULL COMMENT '单位kg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE foods (
    food_name VARCHAR(100) PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    calories FLOAT NOT NULL COMMENT '每100g热量(kcal)',
    carbohydrates FLOAT NOT NULL COMMENT '每100g碳水化合物(g)',
    fat FLOAT NOT NULL COMMENT '每100g脂肪(g)',
    protein FLOAT NOT NULL COMMENT '每100g蛋白质(g)'
);

CREATE TABLE intake_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    intake_date DATE NOT NULL,
    food_name VARCHAR(100) NOT NULL,
    amount FLOAT NOT NULL COMMENT '摄入量(g)',
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (food_name) REFERENCES foods(food_name),
    INDEX (user_id, intake_date)
);

CREATE TABLE daily_nutrition (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    record_date DATE NOT NULL,
    calories FLOAT NOT NULL,
    carbohydrates FLOAT NOT NULL,
    fat FLOAT NOT NULL,
    protein FLOAT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY (user_id, record_date)
);

CREATE TABLE nutrition_recommendations (
    user_id INT PRIMARY KEY,
    calories FLOAT NOT NULL,
    carbohydrates FLOAT NOT NULL,
    fat FLOAT NOT NULL,
    protein FLOAT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

DELIMITER //

-- 创建更新后触发器
CREATE TRIGGER after_intake_update
AFTER UPDATE ON intake_records
FOR EACH ROW
BEGIN
    -- 更新原日期和新日期的营养数据（如果日期被修改）
    IF OLD.intake_date != NEW.intake_date OR OLD.user_id != NEW.user_id THEN
        -- 更新原日期的记录
        UPDATE daily_nutrition dn
        SET 
            dn.calories = (
                SELECT COALESCE(SUM(f.calories * ir.amount / 100), 0)
                FROM intake_records ir
                LEFT JOIN foods f ON ir.food_name = f.name
                WHERE ir.user_id = OLD.user_id 
                AND ir.intake_date = OLD.intake_date
            ),
            dn.protein = (
                SELECT COALESCE(SUM(f.protein * ir.amount / 100), 0)
                FROM intake_records ir
                LEFT JOIN foods f ON ir.food_name = f.name
                WHERE ir.user_id = OLD.user_id 
                AND ir.intake_date = OLD.intake_date
            ),
            dn.carbohydrates = (
                SELECT COALESCE(SUM(f.carbs * ir.amount / 100), 0)
                FROM intake_records ir
                LEFT JOIN foods f ON ir.food_name = f.name
                WHERE ir.user_id = OLD.user_id 
                AND ir.intake_date = OLD.intake_date
            ),
            dn.fat = (
                SELECT COALESCE(SUM(f.fat * ir.amount / 100), 0)
                FROM intake_records ir
                LEFT JOIN foods f ON ir.food_name = f.name
                WHERE ir.user_id = OLD.user_id 
                AND ir.intake_date = OLD.intake_date
            )
        WHERE dn.user_id = OLD.user_id 
        AND dn.record_date = OLD.intake_date;
    END IF;
    
    -- 更新新日期的记录
    IF NOT EXISTS (
        SELECT 1 FROM daily_nutrition 
        WHERE user_id = NEW.user_id AND record_date = NEW.intake_date
    ) THEN
        INSERT INTO daily_nutrition (user_id, record_date, calories, protein, carbohydrates, fat)
        VALUES (NEW.user_id, NEW.intake_date, 0, 0, 0, 0);
    END IF;
    
    UPDATE daily_nutrition dn
    SET 
        dn.calories = (
            SELECT COALESCE(SUM(f.calories * ir.amount / 100), 0)
            FROM intake_records ir
            LEFT JOIN foods f ON ir.food_name = f.name
            WHERE ir.user_id = NEW.user_id 
            AND ir.intake_date = NEW.intake_date
        ),
        dn.protein = (
            SELECT COALESCE(SUM(f.protein * ir.amount / 100), 0)
            FROM intake_records ir
            LEFT JOIN foods f ON ir.food_name = f.name
            WHERE ir.user_id = NEW.user_id 
            AND ir.intake_date = NEW.intake_date
        ),
        dn.carbohydrates = (
            SELECT COALESCE(SUM(f.carbs * ir.amount / 100), 0)
            FROM intake_records ir
            LEFT JOIN foods f ON ir.food_name = f.name
            WHERE ir.user_id = NEW.user_id 
            AND ir.intake_date = NEW.intake_date
        ),
        dn.fat = (
            SELECT COALESCE(SUM(f.fat * ir.amount / 100), 0)
            FROM intake_records ir
            LEFT JOIN foods f ON ir.food_name = f.name
            WHERE ir.user_id = NEW.user_id 
            AND ir.intake_date = NEW.intake_date
        )
    WHERE dn.user_id = NEW.user_id 
    AND dn.record_date = NEW.intake_date;
END//

-- 创建删除后触发器
CREATE TRIGGER after_intake_delete
AFTER DELETE ON intake_records
FOR EACH ROW
BEGIN
    -- 更新daily_nutrition表中的营养数据
    UPDATE daily_nutrition dn
    SET 
        dn.calories = (
            SELECT COALESCE(SUM(f.calories * ir.amount / 100), 0)
            FROM intake_records ir
            LEFT JOIN foods f ON ir.food_name = f.name
            WHERE ir.user_id = OLD.user_id 
            AND ir.intake_date = OLD.intake_date
        ),
        dn.protein = (
            SELECT COALESCE(SUM(f.protein * ir.amount / 100), 0)
            FROM intake_records ir
            LEFT JOIN foods f ON ir.food_name = f.name
            WHERE ir.user_id = OLD.user_id 
            AND ir.intake_date = OLD.intake_date
        ),
        dn.carbohydrates = (
            SELECT COALESCE(SUM(f.carbs * ir.amount / 100), 0)
            FROM intake_records ir
            LEFT JOIN foods f ON ir.food_name = f.name
            WHERE ir.user_id = OLD.user_id 
            AND ir.intake_date = OLD.intake_date
        ),
        dn.fat = (
            SELECT COALESCE(SUM(f.fat * ir.amount / 100), 0)
            FROM intake_records ir
            LEFT JOIN foods f ON ir.food_name = f.name
            WHERE ir.user_id = OLD.user_id 
            AND ir.intake_date = OLD.intake_date
        )
    WHERE dn.user_id = OLD.user_id 
    AND dn.record_date = OLD.intake_date;
    
    -- 如果没有记录，删除daily_nutrition中的空记录
    DELETE FROM daily_nutrition 
    WHERE user_id = OLD.user_id 
    AND record_date = OLD.intake_date
    AND calories = 0 
    AND protein = 0 
    AND carbohydrates = 0 
    AND fat = 0;
END//

DELIMITER ;

CREATE TABLE IF NOT EXISTS ai_recommendations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        recommendation_date DATE NOT NULL,
        content TEXT NOT NULL,
        type ENUM('diet', 'exercise', 'general') DEFAULT 'general',
        is_ai_generated BOOLEAN DEFAULT FALSE,
        ai_provider VARCHAR(50) DEFAULT 'mock',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_date (user_id, recommendation_date),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4