<?php
require_once __DIR__ . '/../auth/service_guard.php';
requireServiceRoles([ROLE_ADMIN, ROLE_STOCK_CLERK]);

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../inventory-items');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../inventory-items');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$item_id = (int)($_POST['item_id'] ?? 0);
$item_name = trim($_POST['item_name'] ?? '');
$category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
$supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
$unit_of_measure = trim($_POST['unit_of_measure'] ?? '');
$current_stock = (float)($_POST['current_stock'] ?? 0);
$minimum_stock = (float)($_POST['minimum_stock'] ?? 0);
$unit_cost = (float)($_POST['unit_cost'] ?? 0);
$expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
$maximum_stock = !empty($_POST['maximum_stock']) ? (float)$_POST['maximum_stock'] : null;

if ($item_id <= 0 || $item_name === '' || $unit_of_measure === '' || $unit_cost <= 0) {
    setErrorMessage('Invalid item data provided.');
}

// Check if item exists
$check_stmt = $conn->prepare('SELECT item_id FROM inventory_items WHERE item_id = ? AND is_active = 1');
$check_stmt->bind_param('i', $item_id);
$check_stmt->execute();
$check_stmt->store_result();
if ($check_stmt->num_rows === 0) {
    $check_stmt->close();
    setErrorMessage('Inventory item not found.');
}
$check_stmt->close();

// Check for duplicate item name (excluding current item)
$duplicate_stmt = $conn->prepare('SELECT item_id FROM inventory_items WHERE item_name = ? AND item_id != ? AND is_active = 1');
$duplicate_stmt->bind_param('si', $item_name, $item_id);
$duplicate_stmt->execute();
$duplicate_stmt->store_result();
if ($duplicate_stmt->num_rows > 0) {
    $duplicate_stmt->close();
    setErrorMessage('An item with this name already exists.');
}
$duplicate_stmt->close();

// Update inventory item
$stmt = $conn->prepare('UPDATE inventory_items SET item_name = ?, category_id = ?, supplier_id = ?, unit_of_measure = ?, current_stock = ?, minimum_stock = ?, unit_cost = ?, expiry_date = ?, maximum_stock = ?, updated_at = NOW() WHERE item_id = ?');
$stmt->bind_param('siisdddsdi', $item_name, $category_id, $supplier_id, $unit_of_measure, $current_stock, $minimum_stock, $unit_cost, $expiry_date, $maximum_stock, $item_id);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to update inventory item. Please try again.');
}

$stmt->close();
setSuccessMessage('Inventory item updated successfully.');
?> 