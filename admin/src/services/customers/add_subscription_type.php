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

$type_name = trim($_POST['type_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$duration_days = intval($_POST['duration_days'] ?? 0);
$price = floatval($_POST['price'] ?? 0);
$meals_per_day = intval($_POST['meals_per_day'] ?? 1);
$service_hours = trim($_POST['service_hours'] ?? '');
$max_meals_per_month = intval($_POST['max_meals_per_month'] ?? 0);
$includes_beverages = isset($_POST['includes_beverages']) ? 1 : 0;
$is_active = isset($_POST['is_active']) ? 1 : 0;

if ($type_name === '') {
    setErrorMessage('Type name is required.');
}

if ($duration_days <= 0) {
    setErrorMessage('Duration must be greater than 0 days.');
}

if ($price <= 0) {
    setErrorMessage('Price must be greater than 0.');
}

if ($meals_per_day <= 0) {
    setErrorMessage('Meals per day must be greater than 0.');
}

// Check for duplicate type name
$stmt = $conn->prepare('SELECT subscription_type_id FROM subscription_types WHERE type_name = ?');
$stmt->bind_param('s', $type_name);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('A subscription type with this name already exists.');
}
$stmt->close();

// Insert new subscription type
$stmt = $conn->prepare('INSERT INTO subscription_types (type_name, description, duration_days, price, meals_per_day, service_hours, includes_beverages, max_meals_per_month, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('ssidissii', $type_name, $description, $duration_days, $price, $meals_per_day, $service_hours, $includes_beverages, $max_meals_per_month, $is_active);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to add subscription type. Please try again.');
}

$stmt->close();
setSuccessMessage('Subscription type added successfully.');
