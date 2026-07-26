<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../suppliers');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../suppliers');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$supplier_name = trim($_POST['supplier_name'] ?? '');
$contact_person = trim($_POST['contact_person'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 0;

if ($supplier_name === '') {
    setErrorMessage('Supplier name is required.');
}

// Check for duplicate supplier name
$stmt = $conn->prepare('SELECT supplier_id FROM suppliers WHERE supplier_name = ?');
$stmt->bind_param('s', $supplier_name);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('A supplier with this name already exists.');
}
$stmt->close();

// Insert new supplier
$stmt = $conn->prepare('INSERT INTO suppliers (supplier_name, contact_person, email, phone, is_active) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('ssssi', $supplier_name, $contact_person, $email, $phone, $is_active);
if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to add supplier. Please try again.');
}
$stmt->close();
setSuccessMessage('Supplier added successfully.'); 