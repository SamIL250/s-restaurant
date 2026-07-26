<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../customers');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../customers');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$customer_id = intval($_POST['customer_id'] ?? 0);

if ($customer_id <= 0) {
    setErrorMessage('Invalid customer ID.');
}

// Check if customer exists and is not already deleted
$check_stmt = $conn->prepare('SELECT customer_id, first_name, last_name FROM customers WHERE customer_id = ? AND deleted_at IS NULL');
$check_stmt->bind_param('i', $customer_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    setErrorMessage('Customer not found or already deleted.');
}
$check_stmt->close();

// Check if customer has any active reservations, orders, or subscriptions
$reservation_check = $conn->prepare('SELECT COUNT(*) as count FROM reservations WHERE customer_id = ? AND deleted_at IS NULL AND status IN ("pending", "confirmed")');
$reservation_check->bind_param('i', $customer_id);
$reservation_check->execute();
$reservation_result = $reservation_check->get_result();
$reservation_count = $reservation_result->fetch_assoc()['count'];
$reservation_check->close();

$order_check = $conn->prepare('SELECT COUNT(*) as count FROM orders WHERE customer_id = ? AND deleted_at IS NULL AND order_status IN ("pending", "confirmed", "preparing")');
$order_check->bind_param('i', $customer_id);
$order_check->execute();
$order_result = $order_check->get_result();
$order_count = $order_result->fetch_assoc()['count'];
$order_check->close();

$subscription_check = $conn->prepare('SELECT COUNT(*) as count FROM customers WHERE customer_id = ? AND deleted_at IS NULL AND subscription_status = "active"');
$subscription_check->bind_param('i', $customer_id);
$subscription_check->execute();
$subscription_result = $subscription_check->get_result();
$subscription_count = $subscription_result->fetch_assoc()['count'];
$subscription_check->close();

if ($reservation_count > 0 || $order_count > 0 || $subscription_count > 0) {
    setErrorMessage('Cannot delete customer. They have ' . ($reservation_count + $order_count + $subscription_count) . ' active item(s).');
}

// Soft delete the customer
$delete_stmt = $conn->prepare('UPDATE customers SET deleted_at = NOW() WHERE customer_id = ?');
$delete_stmt->bind_param('i', $customer_id);

if (!$delete_stmt->execute()) {
    $delete_stmt->close();
    setErrorMessage('Failed to delete customer. Please try again.');
}

$delete_stmt->close();
setSuccessMessage('Customer deleted successfully.');
?> 