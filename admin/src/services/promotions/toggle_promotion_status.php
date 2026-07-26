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
$new_status = intval($_POST['new_status'] ?? 0);

if ($promotion_id <= 0) {
    setErrorMessage('Invalid promotion ID.');
}

if (!in_array($new_status, [0, 1])) {
    setErrorMessage('Invalid status value.');
}

// Check if promotion exists
$stmt = $conn->prepare('SELECT promotion_name FROM promotions WHERE promotion_id = ?');
$stmt->bind_param('i', $promotion_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    setErrorMessage('Promotion not found.');
}
$promotion_name = $result->fetch_assoc()['promotion_name'];
$stmt->close();

// Update promotion status
$stmt = $conn->prepare('UPDATE promotions SET is_active = ? WHERE promotion_id = ?');
$stmt->bind_param('ii', $new_status, $promotion_id);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to update promotion status. Please try again.');
}
$stmt->close();

$status_text = $new_status ? 'activated' : 'deactivated';
setSuccessMessage("Promotion '$promotion_name' $status_text successfully.");
