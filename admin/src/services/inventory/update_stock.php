<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../inventory-items');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../inventory-items');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$item_id = (int)($_POST['item_id'] ?? 0);
$movement_type = trim($_POST['movement_type'] ?? '');
$quantity = (float)($_POST['quantity'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$new_unit_cost = !empty($_POST['new_unit_cost']) ? (float)($_POST['new_unit_cost']) : null;
$update_inventory_cost = (int)($_POST['update_inventory_cost'] ?? 0);
$cost_update_method = trim($_POST['cost_update_method'] ?? 'replace');
$protect_existing_value = (int)($_POST['protect_existing_value'] ?? 1); // Default to protected

if ($item_id <= 0 || $quantity <= 0 || !in_array($movement_type, ['in', 'out', 'adjustment', 'waste'])) {
    setErrorMessage('Invalid stock movement data provided.');
}

// Check if item exists and get current stock
$check_stmt = $conn->prepare('SELECT item_name, current_stock, unit_cost FROM inventory_items WHERE item_id = ? AND is_active = 1');
$check_stmt->bind_param('i', $item_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    setErrorMessage('Inventory item not found.');
}

$item = $result->fetch_assoc();
$item_name = $item['item_name'];
$current_stock = $item['current_stock'];
$current_unit_cost = $item['unit_cost'];
$check_stmt->close();

// Calculate new stock level
$new_stock = $current_stock;
switch ($movement_type) {
    case 'in':
        $new_stock += $quantity;
        break;
    case 'out':
    case 'waste':
        if ($quantity > $current_stock) {
            setErrorMessage('Insufficient stock available for this movement.');
        }
        $new_stock -= $quantity;
        break;
    case 'adjustment':
        $new_stock = $quantity; // Direct adjustment
        break;
}

if ($new_stock < 0) {
    setErrorMessage('Stock cannot be negative.');
}

// Determine unit cost for this movement
$movement_unit_cost = $current_unit_cost; // Default to current cost
if ($new_unit_cost !== null && $new_unit_cost > 0) {
    $movement_unit_cost = $new_unit_cost;
}

// Calculate new inventory unit cost if updating
$new_inventory_unit_cost = $current_unit_cost;
if ($update_inventory_cost && $new_unit_cost !== null && $new_unit_cost > 0) {
    if ($cost_update_method === 'replace') {
        // Check if we should protect existing value
        if ($protect_existing_value && $new_unit_cost < $current_unit_cost) {
            // Don't decrease the cost - keep current cost
            $new_inventory_unit_cost = $current_unit_cost;
        } else {
            $new_inventory_unit_cost = $new_unit_cost;
        }
    } elseif ($cost_update_method === 'weighted_average' && $movement_type === 'in') {
        // Calculate weighted average: (current_stock * current_cost + new_quantity * new_cost) / total_stock
        $total_value = ($current_stock * $current_unit_cost) + ($quantity * $new_unit_cost);
        $total_stock = $current_stock + $quantity;
        if ($total_stock > 0) {
            $weighted_average = $total_value / $total_stock;
            
            // Check if we should protect existing value
            if ($protect_existing_value && $weighted_average < $current_unit_cost) {
                // Don't decrease the cost - keep current cost
                $new_inventory_unit_cost = $current_unit_cost;
            } else {
                $new_inventory_unit_cost = $weighted_average;
            }
        }
    }
}

// Start transaction
$conn->begin_transaction();

try {
    // Update inventory stock and unit cost if needed
    if ($update_inventory_cost && $new_inventory_unit_cost != $current_unit_cost) {
        $update_stmt = $conn->prepare('UPDATE inventory_items SET current_stock = ?, unit_cost = ?, updated_at = NOW() WHERE item_id = ?');
        $update_stmt->bind_param('ddi', $new_stock, $new_inventory_unit_cost, $item_id);
    } else {
        $update_stmt = $conn->prepare('UPDATE inventory_items SET current_stock = ?, updated_at = NOW() WHERE item_id = ?');
        $update_stmt->bind_param('di', $new_stock, $item_id);
    }
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update inventory stock.');
    }
    $update_stmt->close();
    
    // Record stock movement with the actual unit cost used
    $total_cost = $quantity * $movement_unit_cost;
    $user_id = $_SESSION['user_id'];
    
    $movement_stmt = $conn->prepare('INSERT INTO stock_movements (item_id, movement_type, quantity, unit_cost, total_cost, reason, user_id, movement_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    $movement_stmt->bind_param('isddssi', $item_id, $movement_type, $quantity, $movement_unit_cost, $total_cost, $reason, $user_id);
    
    if (!$movement_stmt->execute()) {
        throw new Exception('Failed to record stock movement.');
    }
    $movement_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    $movement_text = ucfirst($movement_type);
    $cost_update_text = '';
    
    if ($update_inventory_cost && $new_inventory_unit_cost != $current_unit_cost) {
        $cost_update_text = " Cost updated from " . number_format($current_unit_cost, 2) . " to " . number_format($new_inventory_unit_cost, 2) . " Frw.";
    } elseif ($update_inventory_cost && $protect_existing_value && $new_unit_cost < $current_unit_cost) {
        $cost_update_text = " Cost protected at " . number_format($current_unit_cost, 2) . " Frw (new stock at " . number_format($new_unit_cost, 2) . " Frw).";
    }
    
    setSuccessMessage("Stock updated successfully. $movement_text: $quantity units. New stock: $new_stock units.$cost_update_text");
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    setErrorMessage('Error updating stock: ' . $e->getMessage());
}
?> 