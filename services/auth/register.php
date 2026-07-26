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

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($firstName === '' || $email === '' || $phone === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit();
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit();
}

$stmt = $conn->prepare('SELECT customer_id, password_hash, account_status FROM customers WHERE email = ? AND deleted_at IS NULL LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing && !empty($existing['password_hash']) && $existing['account_status'] === 'active') {
    echo json_encode(['success' => false, 'message' => 'An account with this email already exists. Please sign in.']);
    exit();
}

$passwordHash = hashUserPassword($password);

if ($existing) {
    $stmt = $conn->prepare('UPDATE customers SET first_name = ?, last_name = ?, phone = ?, password_hash = ?, account_status = \'active\', updated_at = CURRENT_TIMESTAMP WHERE customer_id = ?');
    $stmt->bind_param('ssssi', $firstName, $lastName, $phone, $passwordHash, $existing['customer_id']);
    $stmt->execute();
    $customerId = (int) $existing['customer_id'];
    $stmt->close();
} else {
    $stmt = $conn->prepare('INSERT INTO customers (first_name, last_name, email, phone, password_hash, account_status) VALUES (?, ?, ?, ?, ?, \'active\')');
    $stmt->bind_param('sssss', $firstName, $lastName, $email, $phone, $passwordHash);
    $stmt->execute();
    $customerId = (int) $conn->insert_id;
    $stmt->close();
}

CustomerSession::login([
    'customer_id' => $customerId,
    'email' => $email,
    'first_name' => $firstName,
    'last_name' => $lastName,
    'phone' => $phone,
]);

echo json_encode([
    'success' => true,
    'message' => 'Account created successfully!',
    'redirect' => SITE_WEB_PATH . '/account',
]);
