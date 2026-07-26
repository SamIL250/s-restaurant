<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../purchase-orders');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../purchase-orders');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$po_number = trim($_POST['po_number'] ?? '');
$supplier_id = intval($_POST['supplier_id'] ?? 0);
$expected_delivery_date = $_POST['expected_delivery_date'] ?? null;
$notes = trim($_POST['notes'] ?? '');
$user_id = $_SESSION['user_id'] ?? 0;
$items = $_POST['items'] ?? [];

// Validate required fields
if (empty($po_number) || $supplier_id <= 0) {
    setErrorMessage('PO number and supplier are required.');
}

// Validate PO number format (should start with 'PO' followed by numbers)
if (!preg_match('/^PO\d+$/', $po_number)) {
    setErrorMessage('Invalid PO number format. Must be in format PO{number}.');
}

// Check if user is logged in
if ($user_id <= 0) {
    setErrorMessage('User session not found. Please log in again.');
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert the purchase order
    $stmt = $conn->prepare('INSERT INTO purchase_orders (po_number, supplier_id, user_id, order_date, expected_delivery_date, notes) VALUES (?, ?, ?, CURDATE(), ?, ?)');
    $stmt->bind_param('siiss', $po_number, $supplier_id, $user_id, $expected_delivery_date, $notes);

    if (!$stmt->execute()) {
        throw new Exception('Failed to add purchase order.');
    }

    $purchase_order_id = $conn->insert_id;
    $stmt->close();

    $total_amount = 0;

    // Insert items if provided
    if (!empty($items) && is_array($items)) {
        $item_stmt = $conn->prepare('INSERT INTO purchase_order_items (purchase_order_id, item_id, quantity_ordered, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)');
        
        foreach ($items as $item) {
            if (!empty($item['item_id']) && !empty($item['quantity']) && !empty($item['unit_cost'])) {
                $item_id = intval($item['item_id']);
                $quantity = floatval($item['quantity']);
                $unit_cost = floatval($item['unit_cost']);
                $total_cost = $quantity * $unit_cost;
                
                $item_stmt->bind_param('iiddd', $purchase_order_id, $item_id, $quantity, $unit_cost, $total_cost);
                
                if (!$item_stmt->execute()) {
                    throw new Exception('Failed to add purchase order item.');
                }
                
                $total_amount += $total_cost;
            }
        }
        
        $item_stmt->close();
        
        // Update the total amount in the purchase order
        if ($total_amount > 0) {
            $update_stmt = $conn->prepare('UPDATE purchase_orders SET total_amount = ? WHERE purchase_order_id = ?');
            $update_stmt->bind_param('di', $total_amount, $purchase_order_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
    }

    // Commit transaction
    $conn->commit();
    
    setSuccessMessage('Purchase order ' . htmlspecialchars($po_number) . ' added successfully with ' . count($items) . ' items.');

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    setErrorMessage('Failed to add purchase order: ' . $e->getMessage());
}
?> 