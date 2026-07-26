<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$table_id = intval($_POST['table_id'] ?? 0);
if ($table_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Table ID is required.']);
    exit;
}

$stmt = $conn->prepare('UPDATE restaurant_tables SET is_available = NOT is_available WHERE table_id = ?');
$stmt->bind_param('i', $table_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Table status updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update table status.']);
}
$stmt->close();
$conn->close(); 