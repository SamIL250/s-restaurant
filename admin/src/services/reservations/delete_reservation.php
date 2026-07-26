<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../reservations');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../reservations');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$reservation_id = intval($_POST['reservation_id'] ?? 0);

if ($reservation_id <= 0) {
    setErrorMessage('Invalid reservation ID.');
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get table_id before soft deletion to make it available again
    $table_stmt = $conn->prepare('SELECT table_id FROM reservations WHERE reservation_id = ?');
    $table_stmt->bind_param('i', $reservation_id);
    $table_stmt->execute();
    $table_result = $table_stmt->get_result();
    $table_data = $table_result->fetch_assoc();
    $table_stmt->close();
    
    // Soft delete reservation (set deleted_at timestamp)
    $delete_stmt = $conn->prepare('UPDATE reservations SET deleted_at = NOW() WHERE reservation_id = ?');
    $delete_stmt->bind_param('i', $reservation_id);
    
    if (!$delete_stmt->execute()) {
        $conn->rollback();
        $delete_stmt->close();
        setErrorMessage('Failed to delete reservation. Please try again.');
    }
    $delete_stmt->close();
    
    // Soft delete reservation items
    $delete_items_stmt = $conn->prepare('UPDATE reservation_items SET deleted_at = NOW() WHERE reservation_id = ?');
    $delete_items_stmt->bind_param('i', $reservation_id);
    $delete_items_stmt->execute();
    $delete_items_stmt->close();
    
    // Make table available again if it was assigned
    if ($table_data && $table_data['table_id']) {
        $table_update_stmt = $conn->prepare('UPDATE restaurant_tables SET is_available = 1 WHERE table_id = ?');
        $table_update_stmt->bind_param('i', $table_data['table_id']);
        $table_update_stmt->execute();
        $table_update_stmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    setSuccessMessage('Reservation deleted successfully.');
    
} catch (Exception $e) {
    $conn->rollback();
    setErrorMessage('An error occurred while deleting the reservation.');
}
?>
