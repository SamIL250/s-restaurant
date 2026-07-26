<?php
require_once '../../../config/config.php';
session_start();

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if it's a GET request with an ID
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$item_id = (int)$_GET['id'];

if ($item_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid item ID']);
    exit();
}

// Get inventory item data
$stmt = $conn->prepare('
    SELECT i.*, c.category_name, s.supplier_name 
    FROM inventory_items i 
    LEFT JOIN categories c ON i.category_id = c.category_id 
    LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id 
    WHERE i.item_id = ? AND i.is_active = 1
');
$stmt->bind_param('i', $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    http_response_code(404);
    echo json_encode(['error' => 'Inventory item not found']);
    exit();
}

$item = $result->fetch_assoc();
$stmt->close();

// Return item data as JSON
header('Content-Type: application/json');
echo json_encode($item);
?> 