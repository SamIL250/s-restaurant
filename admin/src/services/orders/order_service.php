<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../orders');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../orders');
    exit();
}

function getOrderDetails($order_id) {
    global $conn;
    
    // Get order details
    $stmt = $conn->prepare("
        SELECT o.*, c.first_name, c.last_name, c.email, c.phone, t.table_number
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.customer_id AND c.deleted_at IS NULL
        LEFT JOIN restaurant_tables t ON o.table_id = t.table_id AND t.deleted_at IS NULL
        WHERE o.order_id = ? AND o.deleted_at IS NULL
    ");
    
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        $stmt->close();
        return null;
    }
    
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        return null;
    }
    
    // Get order items
    $items_stmt = $conn->prepare("
        SELECT oi.*, mi.item_name, mi.description
        FROM order_items oi
        LEFT JOIN menu_items mi ON oi.menu_item_id = mi.menu_item_id
        WHERE oi.order_id = ? AND oi.deleted_at IS NULL
    ");
    
    if (!$items_stmt) {
        return null;
    }
    
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $items = [];
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
    }
    
    $items_stmt->close();
    $order['items'] = $items;
    return $order;
}

function updateOrderStatus($order_id, $new_status) {
    global $conn;
    
    $valid_statuses = ['pending', 'confirmed', 'preparing', 'ready', 'served', 'completed', 'cancelled'];
    
    if (!in_array($new_status, $valid_statuses)) {
        return ['success' => false, 'message' => 'Invalid order status'];
    }
    
    // Get current status
    $current_stmt = $conn->prepare("SELECT order_status FROM orders WHERE order_id = ? AND deleted_at IS NULL");
    if (!$current_stmt) {
        return ['success' => false, 'message' => 'Database error'];
    }
    
    $current_stmt->bind_param('i', $order_id);
    $current_stmt->execute();
    $current_result = $current_stmt->get_result();
    $current = $current_result->fetch_assoc();
    $current_stmt->close();
    
    if (!$current) {
        return ['success' => false, 'message' => 'Order not found'];
    }
    
    // Update status
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error'];
    }
    
    $stmt->bind_param('si', $new_status, $order_id);
    
    if ($stmt->execute()) {
        // Log the status change
        $user_id = $_SESSION['user_id'];
        $log_stmt = $conn->prepare("
            INSERT INTO order_status_logs (order_id, old_status, new_status, user_id, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        if ($log_stmt) {
            $log_stmt->bind_param('issi', $order_id, $current['order_status'], $new_status, $user_id);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        $stmt->close();
        return ['success' => true, 'message' => 'Order status updated successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to update order status'];
    }
}

function updatePaymentStatus($order_id, $payment_status, $payment_method = null) {
    global $conn;
    
    $valid_statuses = ['pending', 'paid', 'refunded'];
    $valid_methods = ['cash', 'card', 'online', 'mobile'];
    
    if (!in_array($payment_status, $valid_statuses)) {
        return ['success' => false, 'message' => 'Invalid payment status'];
    }
    
    if ($payment_method && !in_array($payment_method, $valid_methods)) {
        return ['success' => false, 'message' => 'Invalid payment method'];
    }
    
    $query = "UPDATE orders SET payment_status = ?";
    $params = [$payment_status];
    $types = 's';
    
    if ($payment_method) {
        $query .= ", payment_method = ?";
        $params[] = $payment_method;
        $types .= 's';
    }
    
    $query .= " WHERE order_id = ?";
    $params[] = $order_id;
    $types .= 'i';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Payment status updated successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to update payment status'];
    }
}

function cancelOrder($order_id, $reason = '') {
    global $conn;
    
    // Check if order can be cancelled (not completed or already cancelled)
    $check_stmt = $conn->prepare("
        SELECT order_status FROM orders 
        WHERE order_id = ? AND deleted_at IS NULL
    ");
    
    if (!$check_stmt) {
        return ['success' => false, 'message' => 'Database error'];
    }
    
    $check_stmt->bind_param('i', $order_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $order = $check_result->fetch_assoc();
    $check_stmt->close();
    
    if (!$order) {
        return ['success' => false, 'message' => 'Order not found'];
    }
    
    if (in_array($order['order_status'], ['completed', 'cancelled'])) {
        return ['success' => false, 'message' => 'Cannot cancel a completed or already cancelled order'];
    }
    
    // Update order status
    $stmt = $conn->prepare("
        UPDATE orders 
        SET order_status = 'cancelled', 
            special_instructions = CONCAT(IFNULL(special_instructions, ''), '\n\nCancellation reason: ', ?)
        WHERE order_id = ?
    ");
    
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error'];
    }
    
    $stmt->bind_param('si', $reason, $order_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Order cancelled successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to cancel order'];
    }
}
?>
