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

if ($supplier_id <= 0) {
    setErrorMessage('Invalid supplier ID.');
}

// Check if supplier exists and is not already deleted
$check_stmt = $conn->prepare('SELECT supplier_id, supplier_name FROM suppliers WHERE supplier_id = ? AND deleted_at IS NULL');
$check_stmt->bind_param('i', $supplier_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    setErrorMessage('Supplier not found or already deleted.');
}
$check_stmt->close();

// Check if supplier is being used by any inventory items or purchase orders
$inventory_check = $conn->prepare('SELECT COUNT(*) as count FROM inventory_items WHERE supplier_id = ? AND deleted_at IS NULL');
$inventory_check->bind_param('i', $supplier_id);
$inventory_check->execute();
$inventory_result = $inventory_check->get_result();
$inventory_count = $inventory_result->fetch_assoc()['count'];
$inventory_check->close();

$po_check = $conn->prepare('SELECT COUNT(*) as count FROM purchase_orders WHERE supplier_id = ? AND deleted_at IS NULL');
$po_check->bind_param('i', $supplier_id);
$po_check->execute();
$po_result = $po_check->get_result();
$po_count = $po_result->fetch_assoc()['count'];
$po_check->close();

if ($inventory_count > 0 || $po_count > 0) {
    setErrorMessage('Cannot delete supplier. It is being used by ' . ($inventory_count + $po_count) . ' item(s).');
}

// Soft delete the supplier
$delete_stmt = $conn->prepare('UPDATE suppliers SET deleted_at = NOW() WHERE supplier_id = ?');
$delete_stmt->bind_param('i', $supplier_id);

if (!$delete_stmt->execute()) {
    $delete_stmt->close();
    setErrorMessage('Failed to delete supplier. Please try again.');
}

$delete_stmt->close();
setSuccessMessage('Supplier deleted successfully.');
?> 