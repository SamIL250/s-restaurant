<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../auth/CustomerSession.php';
require_once 'OrderService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $data = [];
    $loggedInCustomer = CustomerSession::user();

    if ($loggedInCustomer) {
        $data['customer_id'] = $loggedInCustomer['customer_id'];
        $data['name'] = $loggedInCustomer['name'];
        $data['email'] = $loggedInCustomer['email'];
        $data['phone'] = $loggedInCustomer['phone'];
    } else {
        foreach (['name', 'phone', 'email'] as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field '$field' is required");
            }
            $data[$field] = trim($_POST[$field]);
        }
    }

    if (empty($_POST['order_type'])) {
        throw new Exception('Order type is required');
    }

    $data['order_type'] = trim($_POST['order_type']);
    $validOrderTypes = ['dine_in', 'takeaway', 'delivery', 'online'];
    if (!in_array($data['order_type'], $validOrderTypes, true)) {
        throw new Exception('Invalid order type');
    }

    if ($data['order_type'] === 'delivery' && empty($_POST['delivery_address'])) {
        throw new Exception('Delivery address is required for delivery orders');
    }

    $data['delivery_address'] = trim($_POST['delivery_address'] ?? '');
    $data['special_instructions'] = trim($_POST['special_instructions'] ?? '');

    if (empty($_POST['menu_item_id']) || !is_array($_POST['menu_item_id'])) {
        throw new Exception('Please select at least one menu item');
    }

    $data['menu_item_id'] = $_POST['menu_item_id'];
    $data['quantity'] = $_POST['quantity'] ?? [];
    $data['unit_price'] = $_POST['unit_price'] ?? [];
    $data['item_name'] = $_POST['item_name'] ?? [];

    if (!$loggedInCustomer && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    $orderService = new OrderService();
    $result = $orderService->createOrder($data);

    if (!$result['success']) {
        throw new Exception($result['message']);
    }

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
