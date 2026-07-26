<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../suppliers');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../suppliers');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$supplier_id = intval($_POST['supplier_id'] ?? 0);
// Accept both string and int for is_active, and force to int 0 or 1
$is_active = isset($_POST['is_active']) && ($_POST['is_active'] == 1 || $_POST['is_active'] === '1') ? 1 : 0;
if ($supplier_id <= 0) {
    setErrorMessage('Invalid supplier.');
}
$stmt = $conn->prepare('UPDATE suppliers SET is_active = ? WHERE supplier_id = ?');
$stmt->bind_param('ii', $is_active, $supplier_id);
if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to update supplier status. Please try again.');
}
$stmt->close();
setSuccessMessage('Supplier status updated.'); 