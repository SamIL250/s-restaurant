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
    // Restore the reservation by setting deleted_at to NULL
    $query = "UPDATE reservations SET deleted_at = NULL WHERE reservation_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $reservation_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Reservation restored successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Reservation not found or already restored']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error restoring reservation']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
