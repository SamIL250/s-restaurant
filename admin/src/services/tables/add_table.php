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

$table_number = trim($_POST['table_number'] ?? '');
$capacity = intval($_POST['capacity'] ?? 0);
$location = trim($_POST['location'] ?? '');

if (empty($table_number) || $capacity <= 0) {
    setErrorMessage('Table number and capacity are required.');
}

// Check for duplicate table number
$stmt = $conn->prepare('SELECT table_id FROM restaurant_tables WHERE table_number = ?');
$stmt->bind_param('s', $table_number);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('Table number already exists.');
}
$stmt->close();

$stmt = $conn->prepare('INSERT INTO restaurant_tables (table_number, capacity, location) VALUES (?, ?, ?)');
$stmt->bind_param('sis', $table_number, $capacity, $location);
if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to add table. Please try again.');
}
$stmt->close();
setSuccessMessage('Table added successfully.'); 