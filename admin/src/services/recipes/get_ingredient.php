<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

$recipe_id = intval($_GET['id'] ?? 0);
if ($recipe_id <= 0) {
    echo json_encode(['error' => 'Invalid recipe ingredient ID']);
    exit;
}
$stmt = $conn->prepare('SELECT * FROM recipe_ingredients WHERE recipe_id = ?');
$stmt->bind_param('i', $recipe_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Ingredient not found']);
}
$stmt->close(); 