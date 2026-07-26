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

$promotion_id = intval($_POST['promotion_id'] ?? 0);
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
if ($promotion_id <= 0) {
    setErrorMessage('Invalid promotion ID.');
}

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

// Check for duplicate promotion name (excluding self)
$stmt = $conn->prepare('SELECT promotion_id FROM promotions WHERE promotion_name = ? AND promotion_id != ?');
$stmt->bind_param('si', $promotion_name, $promotion_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('Promotion name already exists.');
}
$stmt->close();

// Update promotion
$stmt = $conn->prepare('UPDATE promotions SET promotion_name = ?, description = ?, discount_type = ?, discount_value = ?, minimum_order_amount = ?, start_date = ?, end_date = ?, is_active = ?, max_uses = ? WHERE promotion_id = ?');
$stmt->bind_param('sssddssiii', $promotion_name, $description, $discount_type, $discount_value, $minimum_order_amount, $start_date, $end_date, $is_active, $max_uses, $promotion_id);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to update promotion. Please try again.');
}
$stmt->close();
setSuccessMessage('Promotion updated successfully.');
