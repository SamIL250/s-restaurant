<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message, $redirect_url = null)
{
    $_SESSION['notification'] = $message;
    if ($redirect_url) {
        header('Location: ' . $redirect_url);
    } else {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
    exit();
}

function setErrorMessage($message, $redirect_url = null)
{
    $_SESSION['notification'] = $message;
    if ($redirect_url) {
        header('Location: ' . $redirect_url);
    } else {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$table_name = trim($_POST['table_name'] ?? '');
$id_column = trim($_POST['id_column'] ?? '');
$id_value = intval($_POST['id_value'] ?? 0);

if (empty($table_name) || empty($id_column) || $id_value <= 0) {
    setErrorMessage('Invalid parameters for restoration.');
}

// Validate table name to prevent SQL injection
$allowed_tables = [
    'categories', 'suppliers', 'customers', 'users', 'restaurant_tables',
    'menu_items', 'promotions', 'recipe_ingredients', 'subscription_types',
    'reservations', 'reservation_items', 'inventory_items', 'orders',
    'order_items', 'stock_movements', 'purchase_orders', 'purchase_order_items'
];

if (!in_array($table_name, $allowed_tables)) {
    setErrorMessage('Invalid table specified.');
}

// Check if item exists and is deleted
$check_stmt = $conn->prepare("SELECT * FROM $table_name WHERE $id_column = ? AND deleted_at IS NOT NULL");
$check_stmt->bind_param('i', $id_value);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    setErrorMessage('Item not found or not deleted.');
}
$check_stmt->close();

// Restore the item
$restore_stmt = $conn->prepare("UPDATE $table_name SET deleted_at = NULL WHERE $id_column = ?");
$restore_stmt->bind_param('i', $id_value);

if (!$restore_stmt->execute()) {
    $restore_stmt->close();
    setErrorMessage('Failed to restore item. Please try again.');
}

$restore_stmt->close();

// Set appropriate redirect URL based on table
$redirect_urls = [
    'categories' => '../../../categories',
    'suppliers' => '../../../suppliers',
    'customers' => '../../../customers',
    'users' => '../../../staff',
    'restaurant_tables' => '../../../tables',
    'menu_items' => '../../../menu',
    'promotions' => '../../../promotions',
    'recipe_ingredients' => '../../../recipes',
    'subscription_types' => '../../../customers-subscriptions-types',
    'reservations' => '../../../reservations',
    'reservation_items' => '../../../reservations',
    'inventory_items' => '../../../inventory-items',
    'orders' => '../../../orders',
    'order_items' => '../../../orders',
    'stock_movements' => '../../../inventory-movements',
    'purchase_orders' => '../../../purchase-orders',
    'purchase_order_items' => '../../../purchase-orders'
];

$redirect_url = $redirect_urls[$table_name] ?? $_SERVER['HTTP_REFERER'];
setSuccessMessage('Item restored successfully.', $redirect_url);
?>
