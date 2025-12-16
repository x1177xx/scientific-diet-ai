<?php
require 'db_connect.php';
header('Content-Type: application/json');

$response = [];

$sql = "SELECT food_name, category, calories, carbohydrates, fat, protein FROM foods ORDER BY category, food_name";
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["success" => false, "message" => "数据库查询失败"]);
    exit;
}

$foods_by_category = [];

while ($row = $result->fetch_assoc()) {
    $category = $row['category'];
    $foods_by_category[$category][] = [
        "food_name" => $row['food_name'],
        "category" => $row['category'],
        "nutrition" => [
            "calories" => $row['calories'],
            "carbohydrates" => $row['carbohydrates'],
            "fat" => $row['fat'],
            "protein" => $row['protein']
        ]
    ];
}

echo json_encode(["success" => true, "data" => $foods_by_category], JSON_UNESCAPED_UNICODE);
$conn->close();
?>
