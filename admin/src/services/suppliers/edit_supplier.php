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

$supplier_id = intval($_POST['supplier_id'] ?? 0);
$supplier_name = trim($_POST['supplier_name'] ?? '');
$contact_person = trim($_POST['contact_person'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 0;

if ($supplier_id <= 0 || $supplier_name === '') {
    setErrorMessage('Supplier name is required.');
}

// Check for duplicate supplier name (excluding self)
$stmt = $conn->prepare('SELECT supplier_id FROM suppliers WHERE supplier_name = ? AND supplier_id != ?');
$stmt->bind_param('si', $supplier_name, $supplier_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('Another supplier with this name already exists.');
}
$stmt->close();

$stmt = $conn->prepare('UPDATE suppliers SET supplier_name = ?, contact_person = ?, email = ?, phone = ?, is_active = ? WHERE supplier_id = ?');
$stmt->bind_param('ssssii', $supplier_name, $contact_person, $email, $phone, $is_active, $supplier_id);
if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to update supplier. Please try again.');
}
$stmt->close();
setSuccessMessage('Supplier updated successfully.'); 