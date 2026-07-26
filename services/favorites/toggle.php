<?php
session_start();

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../auth/CustomerSession.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$customer = CustomerSession::requireLogin();
$menuItemId = (int) ($_POST['menu_item_id'] ?? 0);

if ($menuItemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid menu item.']);
    exit();
}

$check = $conn->prepare('SELECT favorite_id FROM customer_favorites WHERE customer_id = ? AND menu_item_id = ?');
$check->bind_param('ii', $customer['customer_id'], $menuItemId);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();

if ($existing) {
    $delete = $conn->prepare('DELETE FROM customer_favorites WHERE favorite_id = ?');
    $delete->bind_param('i', $existing['favorite_id']);
    $delete->execute();
    $delete->close();

    echo json_encode(['success' => true, 'favorited' => false, 'message' => 'Removed from favorites.']);
    exit();
}

$insert = $conn->prepare('INSERT INTO customer_favorites (customer_id, menu_item_id) VALUES (?, ?)');
$insert->bind_param('ii', $customer['customer_id'], $menuItemId);
$insert->execute();
$insert->close();

echo json_encode(['success' => true, 'favorited' => true, 'message' => 'Added to favorites.']);
