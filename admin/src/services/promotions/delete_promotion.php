<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../promotions');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../promotions');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$promotion_id = intval($_POST['promotion_id'] ?? 0);

if ($promotion_id <= 0) {
    setErrorMessage('Invalid promotion ID.');
}

// Check if promotion exists and is not already deleted
$check_stmt = $conn->prepare('SELECT promotion_id, promotion_name FROM promotions WHERE promotion_id = ? AND deleted_at IS NULL');
$check_stmt->bind_param('i', $promotion_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    setErrorMessage('Promotion not found or already deleted.');
}
$check_stmt->close();

// Check if promotion is currently active and being used
$active_check = $conn->prepare('SELECT is_active, start_date, end_date FROM promotions WHERE promotion_id = ? AND deleted_at IS NULL');
$active_check->bind_param('i', $promotion_id);
$active_check->execute();
$active_result = $active_check->get_result();
$promotion_data = $active_result->fetch_assoc();
$active_check->close();

$current_date = date('Y-m-d');
if ($promotion_data['is_active'] && 
    $promotion_data['start_date'] <= $current_date && 
    $promotion_data['end_date'] >= $current_date) {
    setErrorMessage('Cannot delete active promotion. Please deactivate it first.');
}

// Soft delete the promotion
$delete_stmt = $conn->prepare('UPDATE promotions SET deleted_at = NOW() WHERE promotion_id = ?');
$delete_stmt->bind_param('i', $promotion_id);

if (!$delete_stmt->execute()) {
    $delete_stmt->close();
    setErrorMessage('Failed to delete promotion. Please try again.');
}

$delete_stmt->close();
setSuccessMessage('Promotion deleted successfully.');
?>
