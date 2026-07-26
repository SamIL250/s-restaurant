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

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subscription_type_id = intval($_POST['subscription_type_id'] ?? 0);
$payment_method = trim($_POST['payment_method'] ?? '');
$auto_renewal = isset($_POST['auto_renewal']) ? 1 : 0;
$start_date = $_POST['start_date'] ?? date('Y-m-d');

if (empty($first_name) && empty($last_name)) {
    setErrorMessage('At least first name or last name is required.');
}

if ($subscription_type_id <= 0) {
    setErrorMessage('Subscription type is required.');
}

if (empty($payment_method)) {
    setErrorMessage('Payment method is required.');
}

// Check for duplicate email if provided
if (!empty($email)) {
    $stmt = $conn->prepare('SELECT customer_id FROM customers WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        setErrorMessage('Email address already exists.');
    }
    $stmt->close();
}

// Calculate subscription end date based on subscription type
$stmt = $conn->prepare('SELECT duration_days FROM subscription_types WHERE subscription_type_id = ?');
$stmt->bind_param('i', $subscription_type_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    setErrorMessage('Invalid subscription type.');
}
$subscription_type = $result->fetch_assoc();
$stmt->close();

$end_date = date('Y-m-d', strtotime($start_date . ' + ' . $subscription_type['duration_days'] . ' days'));
$next_payment_date = $end_date;

// Insert customer with subscription
$stmt = $conn->prepare('INSERT INTO customers (first_name, last_name, email, phone, has_subscription, subscription_status, subscription_start_date, subscription_end_date, subscription_type_id, payment_method, auto_renewal, last_payment_date, next_payment_date) VALUES (?, ?, ?, ?, 1, "active", ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('sssssssssss', $first_name, $last_name, $email, $phone, $start_date, $end_date, $subscription_type_id, $payment_method, $auto_renewal, $start_date, $next_payment_date);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to add customer. Please try again.');
}
$stmt->close();
setSuccessMessage('Customer with subscription added successfully.'); 