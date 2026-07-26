<?php
session_start();

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../auth/CustomerSession.php';

header('Content-Type: application/json');

$customer = CustomerSession::requireLogin();

$stmt = $conn->prepare("
    SELECT cf.menu_item_id, mi.item_name, mi.description, mi.price, mi.image_url, c.category_name
    FROM customer_favorites cf
    JOIN menu_items mi ON mi.menu_item_id = cf.menu_item_id
    LEFT JOIN categories c ON c.category_id = mi.category_id
    WHERE cf.customer_id = ?
      AND mi.deleted_at IS NULL
      AND mi.is_available = 1
    ORDER BY cf.created_at DESC
");
$stmt->bind_param('i', $customer['customer_id']);
$stmt->execute();
$result = $stmt->get_result();

$favorites = [];
while ($row = $result->fetch_assoc()) {
    $favorites[] = [
        'menu_item_id' => (int) $row['menu_item_id'],
        'item_name' => $row['item_name'],
        'description' => $row['description'] ?? '',
        'price' => (float) $row['price'],
        'image_url' => $row['image_url'] ?: 'assets/img/menu/default-menu-item.jpg',
        'category_name' => $row['category_name'] ?? 'Menu',
    ];
}
$stmt->close();

echo json_encode(['success' => true, 'favorites' => $favorites]);
