<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../customers-subscriptions-types');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../customers-subscriptions-types');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$subscription_type_id = intval($_POST['subscription_type_id'] ?? 0);

if ($subscription_type_id <= 0) {
    setErrorMessage('Invalid subscription type ID.');
}

// Check if subscription type exists and is not already deleted
$check_stmt = $conn->prepare('SELECT subscription_type_id, type_name FROM subscription_types WHERE subscription_type_id = ? AND deleted_at IS NULL');
$check_stmt->bind_param('i', $subscription_type_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    setErrorMessage('Subscription type not found or already deleted.');
}
$check_stmt->close();

// Check if subscription type is being used by any customers
$customer_check = $conn->prepare('SELECT COUNT(*) as count FROM customers WHERE subscription_type_id = ? AND deleted_at IS NULL');
$customer_check->bind_param('i', $subscription_type_id);
$customer_check->execute();
$customer_result = $customer_check->get_result();
$customer_count = $customer_result->fetch_assoc()['count'];
$customer_check->close();

if ($customer_count > 0) {
    setErrorMessage('Cannot delete subscription type. It is being used by ' . $customer_count . ' customer(s).');
}

// Soft delete the subscription type
$delete_stmt = $conn->prepare('UPDATE subscription_types SET deleted_at = NOW() WHERE subscription_type_id = ?');
$delete_stmt->bind_param('i', $subscription_type_id);

if (!$delete_stmt->execute()) {
    $delete_stmt->close();
    setErrorMessage('Failed to delete subscription type. Please try again.');
}

$delete_stmt->close();
setSuccessMessage('Subscription type deleted successfully.');
?>
