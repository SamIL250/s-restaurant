<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$purchase_order_id = intval($_POST['purchase_order_id'] ?? 0);
$po_status = trim($_POST['po_status'] ?? '');

if ($purchase_order_id <= 0 || empty($po_status)) {
    echo json_encode(['success' => false, 'message' => 'Purchase order ID and status are required.']);
    exit;
}

// Validate status
$valid_statuses = ['draft', 'sent', 'received', 'cancelled'];
if (!in_array($po_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid PO status.']);
    exit;
}

// If status is 'received', set actual delivery date
$actual_delivery_date = ($po_status == 'received') ? 'CURDATE()' : 'NULL';
$sql = "UPDATE purchase_orders SET po_status = ?, actual_delivery_date = $actual_delivery_date WHERE purchase_order_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $po_status, $purchase_order_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Purchase order status updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update purchase order status.']);
}
$stmt->close();
$conn->close(); 