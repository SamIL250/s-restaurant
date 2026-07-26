<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

$purchase_order_id = intval($_GET['purchase_order_id'] ?? 0);
if ($purchase_order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Purchase order ID is required.']);
    exit;
}

// Get PO details with supplier info
$stmt = $conn->prepare("
    SELECT po.*, s.supplier_name, s.contact_person, s.email, s.phone, u.first_name, u.last_name
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    LEFT JOIN users u ON po.user_id = u.user_id
    WHERE po.purchase_order_id = ?
");
$stmt->bind_param('i', $purchase_order_id);
$stmt->execute();
$result = $stmt->get_result();
$po = $result->fetch_assoc();

if (!$po) {
    echo json_encode(['success' => false, 'message' => 'Purchase order not found.']);
    exit;
}

// Get PO items
$stmt = $conn->prepare("
    SELECT poi.*, i.item_name, i.unit_of_measure
    FROM purchase_order_items poi
    LEFT JOIN inventory_items i ON poi.item_id = i.item_id
    WHERE poi.purchase_order_id = ?
");
$stmt->bind_param('i', $purchase_order_id);
$stmt->execute();
$result = $stmt->get_result();
$po_items = [];
while ($row = $result->fetch_assoc()) {
    $po_items[] = $row;
}

$po['items'] = $po_items;
echo json_encode(['success' => true, 'purchase_order' => $po]);
$stmt->close();
$conn->close(); 