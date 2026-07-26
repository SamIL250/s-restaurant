<?php
require_once '../../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$reservation_id = intval($_GET['id'] ?? 0);

if ($reservation_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid reservation ID']);
    exit;
}

try {
    // Get reservation details
    $stmt = $conn->prepare('
        SELECT r.*, rt.table_number, rt.capacity, rt.location
        FROM reservations r
        LEFT JOIN restaurant_tables rt ON r.table_id = rt.table_id
        WHERE r.reservation_id = ?
    ');
    
    $stmt->bind_param('i', $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Reservation not found');
    }
    
    $reservation = $result->fetch_assoc();
    $stmt->close();
    
    // Get reservation items
    $items_stmt = $conn->prepare('
        SELECT ri.*, mi.item_name, mi.description, c.category_name
        FROM reservation_items ri
        JOIN menu_items mi ON ri.menu_item_id = mi.menu_item_id
        LEFT JOIN categories c ON mi.category_id = c.category_id
        WHERE ri.reservation_id = ?
    ');
    
    $items_stmt->bind_param('i', $reservation_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $items = [];
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
    }
    $items_stmt->close();
    
    $reservation['items'] = $items;
    
    echo json_encode([
        'success' => true,
        'data' => $reservation
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
