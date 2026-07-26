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

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit();
}

$stmt = $conn->prepare('SELECT customer_id, first_name, last_name, email, phone, password_hash, account_status FROM customers WHERE email = ? AND deleted_at IS NULL LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer || empty($customer['password_hash']) || $customer['account_status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'Incorrect email or password.']);
    exit();
}

if (!verifyUserPassword($password, $customer['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect email or password.']);
    exit();
}

CustomerSession::login($customer);

echo json_encode([
    'success' => true,
    'message' => 'Welcome back!',
    'redirect' => SITE_WEB_PATH . '/account',
]);
