<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../categories');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../categories');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$category_id = intval($_POST['category_id'] ?? 0);

if ($category_id <= 0) {
    setErrorMessage('Invalid category ID.');
}

// Check if category exists and is not already deleted
$check_stmt = $conn->prepare('SELECT category_id, category_name FROM categories WHERE category_id = ? AND deleted_at IS NULL');
$check_stmt->bind_param('i', $category_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    setErrorMessage('Category not found or already deleted.');
}
$check_stmt->close();

// Check if category is being used by any menu items or inventory items
$menu_check = $conn->prepare('SELECT COUNT(*) as count FROM menu_items WHERE category_id = ? AND deleted_at IS NULL');
$menu_check->bind_param('i', $category_id);
$menu_check->execute();
$menu_result = $menu_check->get_result();
$menu_count = $menu_result->fetch_assoc()['count'];
$menu_check->close();

$inventory_check = $conn->prepare('SELECT COUNT(*) as count FROM inventory_items WHERE category_id = ? AND deleted_at IS NULL');
$inventory_check->bind_param('i', $category_id);
$inventory_check->execute();
$inventory_result = $inventory_check->get_result();
$inventory_count = $inventory_result->fetch_assoc()['count'];
$inventory_check->close();

if ($menu_count > 0 || $inventory_count > 0) {
    setErrorMessage('Cannot delete category. It is being used by ' . ($menu_count + $inventory_count) . ' item(s).');
}

// Soft delete the category
$delete_stmt = $conn->prepare('UPDATE categories SET deleted_at = NOW() WHERE category_id = ?');
$delete_stmt->bind_param('i', $category_id);

if (!$delete_stmt->execute()) {
    $delete_stmt->close();
    setErrorMessage('Failed to delete category. Please try again.');
}

$delete_stmt->close();
setSuccessMessage('Category deleted successfully.');
?> 