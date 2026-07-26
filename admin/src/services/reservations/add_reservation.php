<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../reservations');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../reservations');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$customer_name = trim($_POST['customer_name'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? '');
$customer_email = trim($_POST['customer_email'] ?? '');
$reservation_date = $_POST['reservation_date'] ?? '';
$reservation_time = $_POST['reservation_time'] ?? '';
$number_of_guests = intval($_POST['number_of_guests'] ?? 0);
$table_id = !empty($_POST['table_id']) ? intval($_POST['table_id']) : null;
$special_requests = trim($_POST['special_requests'] ?? '');
// Validation
if (empty($customer_name)) {
    setErrorMessage('Customer name is required.');
}

if (empty($customer_phone)) {
    setErrorMessage('Customer phone is required.');
}

if (empty($customer_email)) {
    setErrorMessage('Customer email is required.');
}

if (empty($reservation_date)) {
    setErrorMessage('Reservation date is required.');
}

if (empty($reservation_time)) {
    setErrorMessage('Reservation time is required.');
}

if ($number_of_guests <= 0) {
    setErrorMessage('Number of guests must be greater than 0.');
}

// Validate date and time
$reservation_datetime = strtotime($reservation_date . ' ' . $reservation_time);
if (!$reservation_datetime) {
    setErrorMessage('Invalid date or time format.');
}

if ($reservation_datetime < time()) {
    setErrorMessage('Reservation date and time cannot be in the past.');
}

// Check if table is available (if table_id is provided)
if (!empty($table_id)) {
    $stmt = $conn->prepare('SELECT is_available FROM restaurant_tables WHERE table_id = ? AND is_available = 1');
    $stmt->bind_param('i', $table_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        setErrorMessage('Selected table is not available.');
    }
    $stmt->close();
}

// Insert reservation
$stmt = $conn->prepare('
    INSERT INTO reservations (customer_name, customer_phone, customer_email, reservation_date, reservation_time, 
                            number_of_guests, table_id, special_requests) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
');

$stmt->bind_param('sssssiss', 
    $customer_name, 
    $customer_phone, 
    $customer_email, 
    $reservation_date, 
    $reservation_time, 
    $number_of_guests, 
    $table_id, 
    $special_requests
);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to create reservation. Please try again.');
}

    $reservation_id = $conn->insert_id;
    $stmt->close();

    // Make table unavailable if assigned
    if (!empty($table_id)) {
        $table_update_stmt = $conn->prepare('UPDATE restaurant_tables SET is_available = 0 WHERE table_id = ?');
        $table_update_stmt->bind_param('i', $table_id);
        $table_update_stmt->execute();
        $table_update_stmt->close();
    }

    // Insert reservation items if provided
if (!empty($_POST['menu_items']) && is_array($_POST['menu_items'])) {
    foreach ($_POST['menu_items'] as $item) {
        if (empty($item['menu_item_id']) || empty($item['quantity'])) {
            continue;
        }
        
        $menu_item_id = intval($item['menu_item_id']);
        $quantity = intval($item['quantity']);
        $unit_price = floatval($item['unit_price'] ?? 0.00);
        $total_price = $unit_price * $quantity;
        $special_instructions = trim($item['special_instructions'] ?? '');
        
        $item_stmt = $conn->prepare('
            INSERT INTO reservation_items (reservation_id, menu_item_id, quantity, unit_price, total_price, special_instructions) 
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        
        $item_stmt->bind_param('iiddss', 
            $reservation_id, 
            $menu_item_id, 
            $quantity, 
            $unit_price, 
            $total_price, 
            $special_instructions
        );
        
        $item_stmt->execute();
        $item_stmt->close();
    }
}

setSuccessMessage('Reservation created successfully.');
?>
