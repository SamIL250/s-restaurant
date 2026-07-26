<?php
require_once 'order_service.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$payment_status = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : '';
$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : null;

if ($order_id <= 0 || empty($payment_status)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

$result = updatePaymentStatus($order_id, $payment_status, $payment_method);

if ($result['success']) {
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode($result);
}
?>
