<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../promotions');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../promotions');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$promotion_name = trim($_POST['promotion_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$discount_type = $_POST['discount_type'] ?? '';
$discount_value = floatval($_POST['discount_value'] ?? 0);
$minimum_order_amount = floatval($_POST['minimum_order_amount'] ?? 0);
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$max_uses = !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null;
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Validation
if (empty($promotion_name)) {
    setErrorMessage('Promotion name is required.');
}

if (empty($discount_type)) {
    setErrorMessage('Discount type is required.');
}

if ($discount_value <= 0) {
    setErrorMessage('Discount value must be greater than 0.');
}

if (empty($start_date) || empty($end_date)) {
    setErrorMessage('Start date and end date are required.');
}

if (strtotime($start_date) >= strtotime($end_date)) {
    setErrorMessage('End date must be after start date.');
}

if (strtotime($start_date) < strtotime(date('Y-m-d'))) {
    setErrorMessage('Start date cannot be in the past.');
}

// Check for duplicate promotion name
$stmt = $conn->prepare('SELECT promotion_id FROM promotions WHERE promotion_name = ?');
$stmt->bind_param('s', $promotion_name);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('Promotion name already exists.');
}
$stmt->close();

// Insert promotion
$stmt = $conn->prepare('INSERT INTO promotions (promotion_name, description, discount_type, discount_value, minimum_order_amount, start_date, end_date, is_active, max_uses, current_uses) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)');
$stmt->bind_param('sssddssii', $promotion_name, $description, $discount_type, $discount_value, $minimum_order_amount, $start_date, $end_date, $is_active, $max_uses);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to add promotion. Please try again.');
}
$stmt->close();
setSuccessMessage('Promotion added successfully.');
