<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

$category_id = intval($_GET['category_id'] ?? 0);
if ($category_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Category ID is required.']);
    exit;
}

$stmt = $conn->prepare('SELECT * FROM categories WHERE category_id = ?');
$stmt->bind_param('i', $category_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'category' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Category not found.']);
}
$stmt->close();
$conn->close(); 