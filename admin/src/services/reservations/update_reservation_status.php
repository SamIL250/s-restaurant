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
$new_status = trim($_POST['status'] ?? '');

if ($reservation_id <= 0) {
    setErrorMessage('Invalid reservation ID.');
}

if (empty($new_status)) {
    setErrorMessage('Status is required.');
}

// Validate status
$valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
if (!in_array($new_status, $valid_statuses)) {
    setErrorMessage('Invalid status value.');
}

try {
    // Update reservation status
    $stmt = $conn->prepare('UPDATE reservations SET status = ? WHERE reservation_id = ?');
    $stmt->bind_param('si', $new_status, $reservation_id);
    
    if (!$stmt->execute()) {
        $stmt->close();
        setErrorMessage('Failed to update reservation status. Please try again.');
    }
    
    $stmt->close();
    
    // If status is cancelled or completed, make table available again
    if (in_array($new_status, ['cancelled', 'completed'])) {
        $table_stmt = $conn->prepare('
            UPDATE restaurant_tables rt 
            JOIN reservations r ON r.table_id = rt.table_id 
            SET rt.is_available = 1 
            WHERE r.reservation_id = ? AND r.table_id IS NOT NULL
        ');
        $table_stmt->bind_param('i', $reservation_id);
        $table_stmt->execute();
        $table_stmt->close();
    }
    
    setSuccessMessage('Reservation status updated successfully.');
    
} catch (Exception $e) {
    setErrorMessage('An error occurred while updating the reservation status.');
}
?>
