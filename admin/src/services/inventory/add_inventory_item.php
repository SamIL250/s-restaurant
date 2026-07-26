<?php
require_once '../../../config/config.php';
session_start();

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

$item_name = trim($_POST['item_name'] ?? '');
$category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
$supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
$unit_of_measure = trim($_POST['unit_of_measure'] ?? '');
$current_stock = (float)($_POST['current_stock'] ?? 0);
$minimum_stock = (float)($_POST['minimum_stock'] ?? 0);
$unit_cost = (float)($_POST['unit_cost'] ?? 0);
$expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
$maximum_stock = !empty($_POST['maximum_stock']) ? (float)$_POST['maximum_stock'] : null;

if ($item_name === '' || $unit_of_measure === '' || $unit_cost <= 0) {
    setErrorMessage('Item name, unit of measure, and unit cost are required.');
}

// Check for duplicate item name
$stmt = $conn->prepare('SELECT item_id FROM inventory_items WHERE item_name = ? AND is_active = 1');
$stmt->bind_param('s', $item_name);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('An item with this name already exists.');
}
$stmt->close();

// Insert new inventory item
$stmt = $conn->prepare('INSERT INTO inventory_items (item_name, category_id, supplier_id, unit_of_measure, current_stock, minimum_stock, unit_cost, expiry_date, maximum_stock, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
$stmt->bind_param('siisdddsd', $item_name, $category_id, $supplier_id, $unit_of_measure, $current_stock, $minimum_stock, $unit_cost, $expiry_date, $maximum_stock);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to add inventory item. Please try again.');
}

$item_id = $conn->insert_id;
$stmt->close();

// If there's initial stock, create a stock movement record
if ($current_stock > 0) {
    $total_cost = $current_stock * $unit_cost;
    $reason = 'Initial stock';
    $user_id = $_SESSION['user_id'];
    
    $movement_stmt = $conn->prepare('INSERT INTO stock_movements (item_id, movement_type, quantity, unit_cost, total_cost, reason, user_id, movement_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    $movement_type = 'in';
    $movement_stmt->bind_param('isddssi', $item_id, $movement_type, $current_stock, $unit_cost, $total_cost, $reason, $user_id);
    $movement_stmt->execute();
    $movement_stmt->close();
}

setSuccessMessage('Inventory item added successfully.');
?> 