<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

$customer_id = intval($_GET['customer_id'] ?? 0);
if ($customer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Customer ID is required.']);
    exit;
}

$stmt = $conn->prepare('SELECT * FROM customers WHERE customer_id = ?');
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'customer' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Customer not found.']);
}
$stmt->close();
$conn->close(); 