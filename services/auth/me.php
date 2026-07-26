<?php
session_start();

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/CustomerSession.php';

header('Content-Type: application/json');

$user = CustomerSession::user();

if ($user === null) {
    echo json_encode([
        'success' => true,
        'logged_in' => false,
        'customer' => null,
    ]);
    exit();
}

$stmt = $conn->prepare('SELECT customer_id, first_name, last_name, email, phone FROM customers WHERE customer_id = ? AND deleted_at IS NULL LIMIT 1');
$stmt->bind_param('i', $user['customer_id']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profile) {
    CustomerSession::logout();
    echo json_encode([
        'success' => true,
        'logged_in' => false,
        'customer' => null,
    ]);
    exit();
}

echo json_encode([
    'success' => true,
    'logged_in' => true,
    'customer' => [
        'customer_id' => (int) $profile['customer_id'],
        'first_name' => $profile['first_name'] ?? '',
        'last_name' => $profile['last_name'] ?? '',
        'email' => $profile['email'] ?? '',
        'phone' => $profile['phone'] ?? '',
        'name' => trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')),
    ],
]);
