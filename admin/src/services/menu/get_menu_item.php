<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['error' => 'Invalid menu item ID']);
    exit;
}
$stmt = $conn->prepare('SELECT * FROM menu_items WHERE menu_item_id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Menu item not found']);
}
$stmt->close(); 