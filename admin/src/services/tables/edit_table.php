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
$table_number = trim($_POST['table_number'] ?? '');
$capacity = intval($_POST['capacity'] ?? 0);
$location = trim($_POST['location'] ?? '');

if ($table_id <= 0 || empty($table_number) || $capacity <= 0) {
    setErrorMessage('Table ID, number, and capacity are required.');
}

// Check for duplicate table number (excluding self)
$stmt = $conn->prepare('SELECT table_id FROM restaurant_tables WHERE table_number = ? AND table_id != ?');
$stmt->bind_param('si', $table_number, $table_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('Table number already exists.');
}
$stmt->close();

$stmt = $conn->prepare('UPDATE restaurant_tables SET table_number = ?, capacity = ?, location = ? WHERE table_id = ?');
$stmt->bind_param('sisi', $table_number, $capacity, $location, $table_id);
if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to update table. Please try again.');
}
$stmt->close();
setSuccessMessage('Table updated successfully.'); 