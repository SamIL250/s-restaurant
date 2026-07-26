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
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subscription_type_id = intval($_POST['subscription_type_id'] ?? 0);
$payment_method = trim($_POST['payment_method'] ?? '');
$auto_renewal = isset($_POST['auto_renewal']) ? 1 : 0;

if ($customer_id <= 0 || (empty($first_name) && empty($last_name))) {
    setErrorMessage('Customer ID and at least one name are required.');
}

if ($subscription_type_id <= 0) {
    setErrorMessage('Subscription type is required.');
}

if (empty($payment_method)) {
    setErrorMessage('Payment method is required.');
}

// Check for duplicate email if provided (excluding self)
if (!empty($email)) {
    $stmt = $conn->prepare('SELECT customer_id FROM customers WHERE email = ? AND customer_id != ?');
    $stmt->bind_param('si', $email, $customer_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        setErrorMessage('Email address already exists.');
    }
    $stmt->close();
}

// Update customer with subscription info
$stmt = $conn->prepare('UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, subscription_type_id = ?, payment_method = ?, auto_renewal = ? WHERE customer_id = ?');
$stmt->bind_param('sssssssi', $first_name, $last_name, $email, $phone, $subscription_type_id, $payment_method, $auto_renewal, $customer_id);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to update customer. Please try again.');
}
$stmt->close();
setSuccessMessage('Customer updated successfully.'); 