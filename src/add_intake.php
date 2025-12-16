<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json');
require 'db_connect.php';

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "请先登录"]);
    exit();
}

$user_id = $_SESSION["user_id"];
$today = date("Y-m-d");

// 接收 JSON 数据
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data || !isset($data["food_name"]) || !isset($data["amount"]) || !isset($data["nutrition"])) {
    echo json_encode(["success" => false, "message" => "数据不完整"]);
    exit();
}

$food_name = $data["food_name"];
$amount = floatval($data["amount"]);
$calories = floatval($data["nutrition"]["calories"]) * $amount / 100;
$carbohydrates = floatval($data["nutrition"]["carbohydrates"]) * $amount / 100;
$fat = floatval($data["nutrition"]["fat"]) * $amount / 100;
$protein = floatval($data["nutrition"]["protein"]) * $amount / 100;

// 检查是否为空
if (!$food_name || $amount <= 0) {
    echo json_encode(["success" => false, "message" => "食物名或摄入量无效"]);
    exit();
}

// 插入 intake_records 表
$stmt = $conn->prepare("INSERT INTO intake_records (user_id, intake_date, food_name, amount) VALUES (?, ?, ?, ?)");
$stmt->bind_param("issd", $user_id, $today, $food_name, $amount);
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "添加失败: " . $stmt->error]);
    exit();
}
$stmt->close();

// 插入或更新 daily_nutrition
$stmt = $conn->prepare("SELECT id FROM daily_nutrition WHERE user_id = ? AND record_date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $stmt = $conn->prepare("
        UPDATE daily_nutrition
        SET calories = calories + ?, carbohydrates = carbohydrates + ?, fat = fat + ?, protein = protein + ?
        WHERE user_id = ? AND record_date = ?
    ");
    $stmt->bind_param("ddddis", $calories, $carbohydrates, $fat, $protein, $user_id, $today);
    $stmt->execute();
} else {
    $stmt->close();
    $stmt = $conn->prepare("
        INSERT INTO daily_nutrition (user_id, record_date, calories, carbohydrates, fat, protein)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isdddd", $user_id, $today, $calories, $carbohydrates, $fat, $protein);
    $stmt->execute();
}

echo json_encode(["success" => true, "message" => "记录添加成功"]);
