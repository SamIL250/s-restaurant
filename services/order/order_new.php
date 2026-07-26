<?php
// Clean output buffer
ob_clean();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once '../../config/connection.php';

$response = ['success' => false, 'message' => ''];

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed.');
    }
    
    // Get basic required fields first
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    
    // Validate basic required fields
    if (empty($customer_name) || empty($customer_phone) || empty($customer_email)) {
        throw new Exception('Please fill in all required fields.');
    }
    
    // Get order items
    $order_items_json = $_POST['order_items'] ?? '';
    if (empty($order_items_json)) {
        throw new Exception('No order items provided.');
    }
    
    $order_items = json_decode($order_items_json, true);
    if (empty($order_items) || !is_array($order_items)) {
        throw new Exception('Invalid order items format.');
    }
    
    // Validate email
    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format.');
    }
    
    // Validate phone
    if (!preg_match('/^(\+250|07)[0-9]{8}$/', $customer_phone)) {
        throw new Exception('Invalid phone number format.');
    }
    
    // Get optional fields with defaults
    $order_type = 'takeaway'; // Default
    $payment_method = 'cash'; // Default
    $special_instructions = ''; // Default
    
    // Try to get optional fields safely
    if (isset($_POST['order_type'])) {
        $order_type = trim($_POST['order_type']);
        $valid_types = ['dine_in', 'takeaway', 'delivery', 'online'];
        if (!in_array($order_type, $valid_types)) {
            $order_type = 'takeaway';
        }
    }
    
    if (isset($_POST['payment_method'])) {
        $payment_method = trim($_POST['payment_method']);
        $valid_methods = ['cash', 'card', 'online', 'mobile'];
        if (!in_array($payment_method, $valid_methods)) {
            $payment_method = 'cash';
        }
    }
    
    if (isset($_POST['special_instructions'])) {
        $special_instructions = trim($_POST['special_instructions']);
    }
    
    // Calculate order totals
    $subtotal = 0;
    $valid_items = [];
    
    foreach ($order_items as $item) {
        // Validate item structure
        if (!isset($item['menu_item_id']) || !isset($item['quantity']) || $item['quantity'] <= 0) {
            continue; // Skip invalid items
        }
        
        // Get price from database (most reliable)
        $menu_item_id = (int)$item['menu_item_id'];
        $quantity = (int)$item['quantity'];
        
        $price_query = "SELECT price FROM menu_items WHERE menu_item_id = ? AND is_available = 1 AND deleted_at IS NULL LIMIT 1";
        $stmt = mysqli_prepare($conn, $price_query);
        mysqli_stmt_bind_param($stmt, "i", $menu_item_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $price = (float)$row['price'];
            $item_total = $price * $quantity;
            $subtotal += $item_total;
            
            $valid_items[] = [
                'menu_item_id' => $menu_item_id,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $item_total
            ];
        }
        
        mysqli_stmt_close($stmt);
    }
    
    if (empty($valid_items)) {
        throw new Exception('No valid items found in order.');
    }
    
    // Calculate totals
    $tax = $subtotal * 0.18;
    $total = $subtotal + $tax;
    
    // Generate order number
    $order_number = 'ORD' . date('Ymd') . rand(1000, 9999);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert main order - use only essential columns
        $order_sql = "INSERT INTO orders (order_number, order_type, order_status, payment_status, 
                      payment_method, subtotal, tax_amount, total_amount, special_instructions, order_date) 
                      VALUES (?, ?, 'pending', 'pending', ?, ?, ?, ?, ?, NOW())";
        
        $stmt = mysqli_prepare($conn, $order_sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "ssdddds", 
            $order_number, $order_type, $payment_method, 
            $subtotal, $tax, $total, $special_instructions
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Order insert failed: ' . mysqli_error($conn));
        }
        
        $order_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        // Insert order items
        foreach ($valid_items as $item) {
            $item_sql = "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, total_price) 
                        VALUES (?, ?, ?, ?, ?)";
            
            $item_stmt = mysqli_prepare($conn, $item_sql);
            if (!$item_stmt) {
                throw new Exception('Item prepare failed: ' . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($item_stmt, "iiddd", 
                $order_id, $item['menu_item_id'], $item['quantity'], 
                $item['price'], $item['total']
            );
            
            if (!mysqli_stmt_execute($item_stmt)) {
                throw new Exception('Item insert failed: ' . mysqli_error($conn));
            }
            
            mysqli_stmt_close($item_stmt);
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        $response['success'] = true;
        $response['message'] = 'Order placed successfully!';
        $response['order_id'] = $order_id;
        $response['order_number'] = $order_number;
        $response['total'] = number_format($total, 2);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
    
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
