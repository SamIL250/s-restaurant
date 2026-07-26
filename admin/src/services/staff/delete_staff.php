<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../staff');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../staff');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$user_id = intval($_POST['user_id'] ?? 0);

if ($user_id <= 0) {
    setErrorMessage('Invalid user ID.');
}

// Check if user exists and is not already deleted
$check_stmt = $conn->prepare('SELECT user_id, username, first_name, last_name FROM users WHERE user_id = ? AND deleted_at IS NULL');
$check_stmt->bind_param('i', $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    setErrorMessage('User not found or already deleted.');
}
$check_stmt->close();

// Check if user is being used by any orders, purchase orders, or stock movements
$order_check = $conn->prepare('SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND deleted_at IS NULL');
$order_check->bind_param('i', $user_id);
$order_check->execute();
$order_result = $order_check->get_result();
$order_count = $order_result->fetch_assoc()['count'];
$order_check->close();

$po_check = $conn->prepare('SELECT COUNT(*) as count FROM purchase_orders WHERE user_id = ? AND deleted_at IS NULL');
$po_check->bind_param('i', $user_id);
$po_check->execute();
$po_result = $po_check->get_result();
$po_count = $po_result->fetch_assoc()['count'];
$po_check->close();

$stock_check = $conn->prepare('SELECT COUNT(*) as count FROM stock_movements WHERE user_id = ? AND deleted_at IS NULL');
$stock_check->bind_param('i', $user_id);
$stock_check->execute();
$stock_result = $stock_check->get_result();
$stock_count = $stock_result->fetch_assoc()['count'];
$stock_check->close();

if ($order_count > 0 || $po_count > 0 || $stock_count > 0) {
    setErrorMessage('Cannot delete user. They have ' . ($order_count + $po_count + $stock_count) . ' associated record(s).');
}

// Soft delete the user
$delete_stmt = $conn->prepare('UPDATE users SET deleted_at = NOW() WHERE user_id = ?');
$delete_stmt->bind_param('i', $user_id);

if (!$delete_stmt->execute()) {
    $delete_stmt->close();
    setErrorMessage('Failed to delete user. Please try again.');
}

$delete_stmt->close();
setSuccessMessage('User deleted successfully.');
?> 