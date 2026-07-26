<?php
require_once 'order_service.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

$order_details = getOrderDetails($order_id);

if ($order_details) {
    echo json_encode(['success' => true, 'order' => $order_details]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Order not found']);
}
?>
