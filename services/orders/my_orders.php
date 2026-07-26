<?php
session_start();

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../auth/CustomerSession.php';

header('Content-Type: application/json');

$customer = CustomerSession::requireLogin();

$stmt = $conn->prepare("
    SELECT o.order_id, o.order_number, o.order_type, o.order_status, o.total_amount, o.order_date,
           o.delivery_address, o.special_instructions
    FROM orders o
    WHERE o.customer_id = ? AND o.deleted_at IS NULL
    ORDER BY o.order_date DESC
    LIMIT 50
");
$stmt->bind_param('i', $customer['customer_id']);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($order = $result->fetch_assoc()) {
    $itemStmt = $conn->prepare("
        SELECT oi.quantity, oi.unit_price, oi.total_price, mi.item_name
        FROM order_items oi
        JOIN menu_items mi ON mi.menu_item_id = oi.menu_item_id
        WHERE oi.order_id = ? AND oi.deleted_at IS NULL
    ");
    $itemStmt->bind_param('i', $order['order_id']);
    $itemStmt->execute();
    $itemsResult = $itemStmt->get_result();
    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
    }
    $itemStmt->close();

    $order['items'] = $items;
    $orders[] = $order;
}
$stmt->close();

echo json_encode(['success' => true, 'orders' => $orders]);
