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

if ($item_id <= 0) {
    setErrorMessage('Invalid item ID provided.');
}

// Check if item exists and is active
$check_stmt = $conn->prepare('SELECT item_name FROM inventory_items WHERE item_id = ? AND is_active = 1');
$check_stmt->bind_param('i', $item_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    setErrorMessage('Inventory item not found.');
}

$item = $result->fetch_assoc();
$item_name = $item['item_name'];
$check_stmt->close();

// Check if item is used in any recipes
$recipe_check_stmt = $conn->prepare('SELECT recipe_id FROM recipe_ingredients WHERE item_id = ?');
$recipe_check_stmt->bind_param('i', $item_id);
$recipe_check_stmt->execute();
$recipe_check_stmt->store_result();

if ($recipe_check_stmt->num_rows > 0) {
    $recipe_check_stmt->close();
    setErrorMessage('Cannot delete item as it is used in recipes. Please remove it from recipes first.');
}
$recipe_check_stmt->close();

// Soft delete the inventory item (set is_active = 0)
$stmt = $conn->prepare('UPDATE inventory_items SET is_active = 0, updated_at = NOW() WHERE item_id = ?');
$stmt->bind_param('i', $item_id);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to delete inventory item. Please try again.');
}

$stmt->close();
setSuccessMessage("Inventory item '$item_name' deleted successfully.");
?> 