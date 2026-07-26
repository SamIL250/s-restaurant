<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../customers-subscriptions-types.php');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../customers-subscriptions-types.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$subscription_type_id = intval($_POST['subscription_type_id'] ?? 0);
$type_name = trim($_POST['type_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$duration_days = intval($_POST['duration_days'] ?? 0);
$price = floatval($_POST['price'] ?? 0);
$meals_per_day = intval($_POST['meals_per_day'] ?? 1);
$service_hours = trim($_POST['service_hours'] ?? '');
$max_meals_per_month = !empty($_POST['max_meals_per_month']) ? intval($_POST['max_meals_per_month']) : null;
$includes_beverages = isset($_POST['includes_beverages']) ? 1 : 0;
$is_active = isset($_POST['is_active']) ? 1 : 0;

if ($subscription_type_id <= 0) {
    setErrorMessage('Invalid subscription type ID.');
}

if (empty($type_name)) {
    setErrorMessage('Type name is required.');
}

if ($duration_days <= 0) {
    setErrorMessage('Duration must be greater than 0.');
}

if ($price <= 0) {
    setErrorMessage('Price must be greater than 0.');
}

if ($meals_per_day <= 0) {
    setErrorMessage('Meals per day must be greater than 0.');
}

// Check for duplicate type name (excluding self)
$stmt = $conn->prepare('SELECT subscription_type_id FROM subscription_types WHERE type_name = ? AND subscription_type_id != ?');
$stmt->bind_param('si', $type_name, $subscription_type_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('Subscription type name already exists.');
}
$stmt->close();

// Update subscription type
$stmt = $conn->prepare('UPDATE subscription_types SET type_name = ?, description = ?, duration_days = ?, price = ?, meals_per_day = ?, service_hours = ?, max_meals_per_month = ?, includes_beverages = ?, is_active = ? WHERE subscription_type_id = ?');
$stmt->bind_param('ssidissiii', $type_name, $description, $duration_days, $price, $meals_per_day, $service_hours, $max_meals_per_month, $includes_beverages, $is_active, $subscription_type_id);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to update subscription type. Please try again.');
}
$stmt->close();
setSuccessMessage('Subscription type updated successfully.');
