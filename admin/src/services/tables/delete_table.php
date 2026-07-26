<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../tables');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../tables');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$table_id = intval($_POST['table_id'] ?? 0);

if ($table_id <= 0) {
    setErrorMessage('Invalid table ID.');
}

// Check if table exists and is not already deleted
$check_stmt = $conn->prepare('SELECT table_id, table_number, capacity FROM restaurant_tables WHERE table_id = ? AND deleted_at IS NULL');
$check_stmt->bind_param('i', $table_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    setErrorMessage('Table not found or already deleted.');
}
$check_stmt->close();

// Check if table is being used by any active reservations or orders
$reservation_check = $conn->prepare('SELECT COUNT(*) as count FROM reservations WHERE table_id = ? AND deleted_at IS NULL AND status IN ("pending", "confirmed")');
$reservation_check->bind_param('i', $table_id);
$reservation_check->execute();
$reservation_result = $reservation_check->get_result();
$reservation_count = $reservation_result->fetch_assoc()['count'];
$reservation_check->close();

$order_check = $conn->prepare('SELECT COUNT(*) as count FROM orders WHERE table_id = ? AND deleted_at IS NULL AND order_status IN ("pending", "confirmed", "preparing")');
$order_check->bind_param('i', $table_id);
$order_check->execute();
$order_result = $order_check->get_result();
$order_count = $order_result->fetch_assoc()['count'];
$order_check->close();

if ($reservation_count > 0 || $order_count > 0) {
    setErrorMessage('Cannot delete table. It is being used by ' . ($reservation_count + $order_count) . ' active reservation(s)/order(s).');
}

// Soft delete the table
$delete_stmt = $conn->prepare('UPDATE restaurant_tables SET deleted_at = NOW() WHERE table_id = ?');
$delete_stmt->bind_param('i', $table_id);

if (!$delete_stmt->execute()) {
    $delete_stmt->close();
    setErrorMessage('Failed to delete table. Please try again.');
}

$delete_stmt->close();
setSuccessMessage('Table deleted successfully.');
?> 