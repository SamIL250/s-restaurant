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
    // Get reservation total from items
    $stmt = $conn->prepare('
        SELECT COALESCE(SUM(ri.total_price), 0) as total_amount
        FROM reservation_items ri
        WHERE ri.reservation_id = ?
    ');
    
    $stmt->bind_param('i', $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Reservation not found');
    }
    
    $total_data = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'reservation_id' => $reservation_id,
            'total_amount' => $total_data['total_amount']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
