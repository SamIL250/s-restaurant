<?php
// Clean output buffer to prevent any unwanted output
ob_clean();

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once '../../config/connection.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // Check connection
    if (!$conn) {
        throw new Exception('Database connection failed.');
    }
    
    // Get and validate input data
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $order_type = trim($_POST['order_type'] ?? 'takeaway');
    $special_instructions = trim($_POST['special_instructions'] ?? '');
    $payment_method_raw = $_POST['payment_method'] ?? 'cash';
    
    // Debug: Show detailed info about payment_method
    error_log("Raw payment_method: '$payment_method_raw'");
    error_log("Length: " . strlen($payment_method_raw));
    error_log("Hex dump: " . bin2hex($payment_method_raw));
    
    // Force payment_method to 'cash' to eliminate database issues
    $payment_method = 'cash'; // Temporarily force to cash for testing
    
    // Validate order_type against database ENUM values
    $valid_order_types = ['dine_in', 'takeaway', 'delivery', 'online'];
    if (!in_array($order_type, $valid_order_types)) {
        $order_type = 'takeaway'; // Default to takeaway if invalid
    }
    
    // Get order items
    $order_items_json = $_POST['order_items'] ?? '';
    $order_items = json_decode($order_items_json, true);
    
    // Basic validation
    if (empty($customer_name) || empty($customer_phone) || empty($customer_email)) {
        throw new Exception('Please fill in all required fields.');
    }
    
    if (empty($order_items) || !is_array($order_items)) {
        throw new Exception('Please select at least one item.');
    }
    
    // Validate email
    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format.');
    }
    
    // Validate phone
    if (!preg_match('/^(\+250|07)[0-9]{8}$/', $customer_phone)) {
        throw new Exception('Invalid phone number format.');
    }
    
    // Calculate totals
    $subtotal = 0;
    foreach ($order_items as $item) {
        if (!isset($item['menu_item_id']) || !isset($item['quantity']) || $item['quantity'] <= 0) {
            throw new Exception('Invalid item data.');
        }
        
        // Debug: Show what's in the item
        error_log("Item data: " . print_r($item, true));
        
        // Get price - try different possible keys
        $item_price = 0;
        if (isset($item['price'])) {
            $item_price = $item['price'];
        } elseif (isset($item['unit_price'])) {
            $item_price = $item['unit_price'];
        } else {
            // If no price in cart, get from database
            $price_query = "SELECT price FROM menu_items WHERE menu_item_id = ? AND is_available = 1 AND deleted_at IS NULL";
            $price_stmt = mysqli_prepare($conn, $price_query);
            mysqli_stmt_bind_param($price_stmt, "i", $item['menu_item_id']);
            mysqli_stmt_execute($price_stmt);
            $price_result = mysqli_stmt_get_result($price_stmt);
            
            if ($price_result && mysqli_num_rows($price_result) > 0) {
                $price_data = mysqli_fetch_assoc($price_result);
                $item_price = $price_data['price'];
            } else {
                throw new Exception('Item not available or price not found.');
            }
            mysqli_stmt_close($price_stmt);
        }
        
        $subtotal += $item_price * $item['quantity'];
        error_log("Item price: $item_price, Quantity: {$item['quantity']}, Subtotal: $subtotal");
    }
    
    $tax = $subtotal * 0.18;
    $total = $subtotal + $tax;
    
    // Generate order number
    $order_number = 'ORD' . date('Ymd') . rand(1000, 9999);
    
    // Insert order (simplified query)
    $query = "INSERT INTO orders (order_number, order_type, order_status, payment_status, payment_method, 
              subtotal, tax_amount, total_amount, special_instructions, order_date) 
              VALUES (?, ?, 'pending', 'pending', ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception('Database error: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ssdddds", 
        $order_number, $order_type, $payment_method, 
        $subtotal, $tax, $total, $special_instructions
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Order failed: ' . mysqli_error($conn));
    }
    
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    // Insert order items
    foreach ($order_items as $item) {
        // Get price again for consistency
        $item_price = 0;
        if (isset($item['price'])) {
            $item_price = $item['price'];
        } elseif (isset($item['unit_price'])) {
            $item_price = $item['unit_price'];
        } else {
            // Get from database
            $price_query = "SELECT price FROM menu_items WHERE menu_item_id = ? AND is_available = 1 AND deleted_at IS NULL";
            $price_stmt = mysqli_prepare($conn, $price_query);
            mysqli_stmt_bind_param($price_stmt, "i", $item['menu_item_id']);
            mysqli_stmt_execute($price_stmt);
            $price_result = mysqli_stmt_get_result($price_stmt);
            
            if ($price_result && mysqli_num_rows($price_result) > 0) {
                $price_data = mysqli_fetch_assoc($price_result);
                $item_price = $price_data['price'];
            } else {
                throw new Exception('Item not available for order items.');
            }
            mysqli_stmt_close($price_stmt);
        }
        
        $item_total = $item_price * $item['quantity'];
        
        $item_query = "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, total_price) 
                      VALUES (?, ?, ?, ?, ?)";
        
        $item_stmt = mysqli_prepare($conn, $item_query);
        mysqli_stmt_bind_param($item_stmt, "iiddd", 
            $order_id, $item['menu_item_id'], $item['quantity'], 
            $item_price, $item_total
        );
        
        if (!mysqli_stmt_execute($item_stmt)) {
            throw new Exception('Item failed: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($item_stmt);
    }
    
    $response['success'] = true;
    $response['message'] = 'Order placed successfully!';
    $response['order_id'] = $order_id;
    $response['order_number'] = $order_number;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Close connection
if ($conn) {
    mysqli_close($conn);
}

// Clean output and send JSON
ob_clean();
echo json_encode($response);
exit;
?>
