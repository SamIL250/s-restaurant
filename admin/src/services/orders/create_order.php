<?php
require_once '../../../config/config.php';
session_start();

// Catch any fatal errors and return JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

function handleFatalError() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Fatal error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
        exit();
    }
}

register_shutdown_function('handleFatalError');

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message]);
    exit();
}

function setErrorMessage($message)
{
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

// Debug: Log all POST data
error_log("=== POST DATA DEBUG ===");
error_log("Raw POST: " . file_get_contents('php://input'));
foreach ($_POST as $key => $value) {
    if (is_array($value)) {
        error_log("$key: " . json_encode($value));
    } else {
        error_log("$key: $value");
    }
}
error_log("=====================");

// Get and validate basic form data
$customer_type = trim($_POST['customer_type'] ?? '');
$order_type = trim($_POST['order_type'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');
$special_instructions = trim($_POST['special_instructions'] ?? '');

// Validation
if (empty($customer_type)) {
    setErrorMessage('Customer type is required.');
}

if (empty($order_type)) {
    setErrorMessage('Order type is required.');
}

if (empty($payment_method)) {
    setErrorMessage('Payment method is required.');
}

// Get customer information
$customer_id = null;
if ($customer_type === 'existing') {
    $customer_id = intval($_POST['customer_id'] ?? 0);
    if ($customer_id <= 0) {
        setErrorMessage('Please select a valid customer.');
    }
} elseif ($customer_type === 'new') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($first_name) || empty($last_name)) {
        setErrorMessage('First name and last name are required for new customers.');
    }
    
    if (empty($email)) {
        setErrorMessage('Email is required for new customers.');
    }
    
    // Check if customer already exists
    $check_stmt = $conn->prepare('SELECT customer_id FROM customers WHERE email = ? AND deleted_at IS NULL');
    $check_stmt->bind_param('s', $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $existing_customer = $check_result->fetch_assoc();
    $check_stmt->close();
    
    if ($existing_customer) {
        // Use existing customer
        $customer_id = $existing_customer['customer_id'];
    } else {
        // Create new customer
        $stmt = $conn->prepare('INSERT INTO customers (first_name, last_name, email, phone, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->bind_param('ssss', $first_name, $last_name, $email, $phone);
        
        if (!$stmt->execute()) {
            $stmt->close();
            setErrorMessage('Failed to create new customer. Please try again.');
        }
        
        $customer_id = $conn->insert_id;
        $stmt->close();
    }
}

// Get table information for dine-in orders
$table_id = null;
if ($order_type === 'dine_in') {
    $table_id = !empty($_POST['table_id']) ? intval($_POST['table_id']) : null;
    if (!$table_id) {
        setErrorMessage('Please select a table for dine-in orders.');
    }
    
    // Check if table is available
    $table_stmt = $conn->prepare('SELECT is_available FROM restaurant_tables WHERE table_id = ? AND is_available = 1 AND deleted_at IS NULL');
    $table_stmt->bind_param('i', $table_id);
    $table_stmt->execute();
    $table_stmt->store_result();
    if ($table_stmt->num_rows === 0) {
        $table_stmt->close();
        setErrorMessage('Selected table is not available.');
    }
    $table_stmt->close();
}

// Get delivery information
$delivery_address = null;
$delivery_fee = 0;
if ($order_type === 'delivery') {
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $delivery_fee = floatval($_POST['delivery_fee'] ?? 0);
    
    if (empty($delivery_address)) {
        setErrorMessage('Delivery address is required for delivery orders.');
    }
}

// Validate menu items
$menu_items = $_POST['menu_items'] ?? [];
$quantities = $_POST['quantities'] ?? [];

if (empty($menu_items) || !is_array($menu_items)) {
    setErrorMessage('At least one menu item is required.');
}

// Calculate order totals
$subtotal = 0;
$valid_items = [];

foreach ($menu_items as $index => $menu_item_id) {
    if (empty($menu_item_id) || empty($quantities[$index]) || $quantities[$index] <= 0) {
        continue;
    }
    
    $menu_item_id = intval($menu_item_id);
    $quantity = intval($quantities[$index]);
    
    // Get menu item details
    $item_stmt = $conn->prepare('SELECT item_name, price FROM menu_items WHERE menu_item_id = ? AND is_available = 1 AND deleted_at IS NULL');
    $item_stmt->bind_param('i', $menu_item_id);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();
    $item = $item_result->fetch_assoc();
    $item_stmt->close();
    
    if (!$item) {
        setErrorMessage('One or more selected menu items are not available.');
    }
    
    $unit_price = floatval($item['price']);
    $total_price = $unit_price * $quantity;
    
    $subtotal += $total_price;
    $valid_items[] = [
        'menu_item_id' => $menu_item_id,
        'quantity' => $quantity,
        'unit_price' => $unit_price,
        'total_price' => $total_price
    ];
}

if (empty($valid_items)) {
    setErrorMessage('Please select at least one valid menu item.');
}

$tax_amount = $subtotal * 0.1; // 10% tax
$discount_amount = 0.00; // Default discount
$total_amount = $subtotal + $tax_amount + $delivery_fee - $discount_amount;

// Generate order number
$order_number = 'ORD' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

// Create the order
$stmt = $conn->prepare('
    INSERT INTO orders (
        order_number, customer_id, table_id, user_id, order_type, 
        payment_method, subtotal, tax_amount, discount_amount, total_amount,
        delivery_address, delivery_fee, special_instructions, order_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
');

$user_id = $_SESSION['user_id'] ?? 1; // Fallback user_id

// Debug: Log all variables before bind
error_log("=== BIND VARIABLES DEBUG ===");
error_log("order_number: $order_number (string)");
error_log("customer_id: $customer_id (integer)");
error_log("table_id: " . ($table_id ?? 'NULL') . " (integer)");
error_log("user_id: $user_id (integer)");
error_log("order_type: $order_type (string)");
error_log("payment_method: $payment_method (string)");
error_log("subtotal: $subtotal (double)");
error_log("tax_amount: $tax_amount (double)");
error_log("discount_amount: $discount_amount (double)");
error_log("total_amount: $total_amount (double)");
error_log("delivery_address: " . ($delivery_address ?? 'NULL') . " (string)");
error_log("delivery_fee: $delivery_fee (double)");
error_log("special_instructions: $special_instructions (string)");
error_log("Type string: siiissdddddss (13 chars)");
error_log("==========================");

$stmt->bind_param(
    'siiissdddddss',
    $order_number, $customer_id, $table_id, $user_id, $order_type,
    $payment_method, $subtotal, $tax_amount, $discount_amount, $total_amount,
    $delivery_address, $delivery_fee, $special_instructions
);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to create order. Please try again.');
}

$order_id = $conn->insert_id;
$stmt->close();

// Create order items
foreach ($valid_items as $item) {
    $item_stmt = $conn->prepare('
        INSERT INTO order_items (
            order_id, menu_item_id, quantity, unit_price, total_price
        ) VALUES (?, ?, ?, ?, ?)
    ');
    
    $item_stmt->bind_param(
        'iiddd',
        $order_id,
        $item['menu_item_id'],
        $item['quantity'],
        $item['unit_price'],
        $item['total_price']
    );
    
    $item_stmt->execute();
    $item_stmt->close();
}

setSuccessMessage('Order created successfully!');
?>
