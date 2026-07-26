<?php
require_once '../../../config/config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$reservation_id = $input['reservation_id'] ?? null;

if (empty($reservation_id)) {
    echo json_encode(['success' => false, 'message' => 'Reservation ID is required']);
    exit();
}

try {
    // Get reservation details
    $query = "
        SELECT r.*, rt.table_number, rt.capacity, rt.location
        FROM reservations r
        LEFT JOIN restaurant_tables rt ON r.table_id = rt.table_id
        WHERE r.reservation_id = ? AND r.deleted_at IS NULL
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($reservation = $result->fetch_assoc()) {
        // Get reservation items
        $items_query = "
            SELECT ri.*, mi.item_name, mi.description, mi.price
            FROM reservation_items ri
            JOIN menu_items mi ON ri.menu_item_id = mi.menu_item_id
            WHERE ri.reservation_id = ? AND ri.deleted_at IS NULL
            ORDER BY ri.reservation_item_id
        ";
        
        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bind_param('i', $reservation_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        $items = [];
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
        $items_stmt->close();
        
        // Get available menu items for the dropdown
        $menu_query = "
            SELECT menu_item_id, item_name, price, description 
            FROM menu_items 
            WHERE deleted_at IS NULL AND is_available = 1
            ORDER BY item_name
        ";
        
        $menu_result = mysqli_query($conn, $menu_query);
        $menu_items = [];
        while ($menu_item = mysqli_fetch_assoc($menu_result)) {
            $menu_items[] = $menu_item;
        }
        
        echo json_encode([
            'success' => true,
            'reservation' => $reservation,
            'items' => $items,
            'menu_items' => $menu_items
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Reservation not found or has been deleted'
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
