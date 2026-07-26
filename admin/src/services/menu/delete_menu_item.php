<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../menu');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../menu');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$menu_item_id = intval($_POST['menu_item_id'] ?? 0);

if ($menu_item_id <= 0) {
    setErrorMessage('Invalid menu item ID.');
}

// Check if menu item exists and is not already deleted
$check_stmt = $conn->prepare('SELECT menu_item_id, item_name FROM menu_items WHERE menu_item_id = ? AND deleted_at IS NULL');
$check_stmt->bind_param('i', $menu_item_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    setErrorMessage('Menu item not found or already deleted.');
}
$check_stmt->close();

// Check if menu item is being used by any reservations, orders, or recipes
$reservation_check = $conn->prepare('SELECT COUNT(*) as count FROM reservation_items WHERE menu_item_id = ? AND deleted_at IS NULL');
$reservation_check->bind_param('i', $menu_item_id);
$reservation_check->execute();
$reservation_result = $reservation_check->get_result();
$reservation_count = $reservation_result->fetch_assoc()['count'];
$reservation_check->close();

$order_check = $conn->prepare('SELECT COUNT(*) as count FROM order_items WHERE menu_item_id = ? AND deleted_at IS NULL');
$order_check->bind_param('i', $menu_item_id);
$order_check->execute();
$order_result = $order_check->get_result();
$order_count = $order_result->fetch_assoc()['count'];
$order_check->close();

$recipe_check = $conn->prepare('SELECT COUNT(*) as count FROM recipe_ingredients WHERE menu_item_id = ? AND deleted_at IS NULL');
$recipe_check->bind_param('i', $menu_item_id);
$recipe_check->execute();
$recipe_result = $recipe_check->get_result();
$recipe_count = $recipe_result->fetch_assoc()['count'];
$recipe_check->close();

if ($reservation_count > 0 || $order_count > 0 || $recipe_count > 0) {
    setErrorMessage('Cannot delete menu item. It is being used by ' . ($reservation_count + $order_count + $recipe_count) . ' record(s).');
}

// Soft delete the menu item
$delete_stmt = $conn->prepare('UPDATE menu_items SET deleted_at = NOW() WHERE menu_item_id = ?');
$delete_stmt->bind_param('i', $menu_item_id);

if (!$delete_stmt->execute()) {
    $delete_stmt->close();
    setErrorMessage('Failed to delete menu item. Please try again.');
}

$delete_stmt->close();
setSuccessMessage('Menu item deleted successfully.');
?> 