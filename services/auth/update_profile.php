<?php
session_start();

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/password_helper.php';
require_once __DIR__ . '/CustomerSession.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$customer = CustomerSession::requireLogin();

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($firstName === '' || $email === '' || $phone === '') {
    echo json_encode(['success' => false, 'message' => 'First name, email, and phone are required.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

$stmt = $conn->prepare('SELECT customer_id, first_name, last_name, email, phone, password_hash FROM customers WHERE customer_id = ? AND deleted_at IS NULL LIMIT 1');
$stmt->bind_param('i', $customer['customer_id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Account not found.']);
    exit();
}

if (strcasecmp($email, $row['email']) !== 0) {
    $check = $conn->prepare('SELECT customer_id FROM customers WHERE email = ? AND customer_id != ? AND deleted_at IS NULL LIMIT 1');
    $check->bind_param('si', $email, $customer['customer_id']);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Another account already uses this email address.']);
        exit();
    }
}

$passwordHash = $row['password_hash'];
$passwordChanged = false;

if ($newPassword !== '' || $confirmPassword !== '' || $currentPassword !== '') {
    if ($currentPassword === '') {
        echo json_encode(['success' => false, 'message' => 'Enter your current password to set a new one.']);
        exit();
    }

    if (!verifyUserPassword($currentPassword, $row['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit();
    }

    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
        exit();
    }

    $passwordHash = hashUserPassword($newPassword);
    $passwordChanged = true;
}

if ($passwordChanged) {
    $update = $conn->prepare('UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE customer_id = ?');
    $update->bind_param('sssssi', $firstName, $lastName, $email, $phone, $passwordHash, $customer['customer_id']);
} else {
    $update = $conn->prepare('UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE customer_id = ?');
    $update->bind_param('ssssi', $firstName, $lastName, $email, $phone, $customer['customer_id']);
}

if (!$update->execute()) {
    $update->close();
    echo json_encode(['success' => false, 'message' => 'Unable to update profile. Please try again.']);
    exit();
}
$update->close();

CustomerSession::login([
    'customer_id' => $customer['customer_id'],
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email,
    'phone' => $phone,
]);

echo json_encode([
    'success' => true,
    'message' => $passwordChanged ? 'Profile and password updated successfully.' : 'Profile updated successfully.',
    'customer' => [
        'customer_id' => $customer['customer_id'],
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'name' => trim($firstName . ' ' . $lastName),
    ],
]);
