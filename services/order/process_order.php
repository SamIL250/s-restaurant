<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

error_log("process_order.php started");

try {
    error_log("Trying to include OrderService");
    require_once 'OrderService.php';
    error_log("OrderService included successfully");
} catch (Exception $e) {
    error_log("Service initialization failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Service initialization failed: ' . $e->getMessage()]);
    exit;
} catch (Error $e) {
    error_log("Service initialization error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Service initialization error: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    error_log("Starting validation process");
    
    // Validate required fields
    $requiredFields = ['name', 'phone', 'email', 'order_type'];
    $data = [];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
        $data[$field] = trim($_POST[$field]);
    }

    error_log("Basic validation passed");

    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Validate order type
    $validOrderTypes = ['dine_in', 'takeaway', 'delivery', 'online'];
    if (!in_array($data['order_type'], $validOrderTypes)) {
        throw new Exception("Invalid order type");
    }

    // Validate delivery address if order type is delivery
    if ($data['order_type'] === 'delivery') {
        if (empty($_POST['delivery_address'])) {
            throw new Exception("Delivery address is required for delivery orders");
        }
        $data['delivery_address'] = trim($_POST['delivery_address']);
    }

    // Validate menu items
    if (empty($_POST['menu_item_id']) || !is_array($_POST['menu_item_id'])) {
        throw new Exception("Please select at least one menu item");
    }

    $data['menu_item_id'] = $_POST['menu_item_id'];
    $data['quantity'] = isset($_POST['quantity']) ? $_POST['quantity'] : [];
    $data['unit_price'] = isset($_POST['unit_price']) ? $_POST['unit_price'] : [];
    $data['special_instructions'] = isset($_POST['special_instructions']) ? trim($_POST['special_instructions']) : '';

    error_log("All validation passed");

    error_log("Creating OrderService instance");
    
    // Create order
    $orderService = new OrderService();
    error_log("OrderService created successfully");
    
    error_log("Calling createOrder");
    $result = $orderService->createOrder($data);
    error_log("createOrder completed");

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'order_id' => $result['order_id'],
            'order_number' => $result['order_number']
        ]);
    } else {
        throw new Exception($result['message']);
    }

} catch (Exception $e) {
    error_log("Exception in process_order: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    error_log("Error in process_order: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . $e->getMessage()
    ]);
}

error_log("process_order.php completed");
?>

