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

$reservation_id = intval($_POST['reservation_id'] ?? 0);
$customer_name = trim($_POST['customer_name'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? '');
$customer_email = trim($_POST['customer_email'] ?? '');
$reservation_date = $_POST['reservation_date'] ?? '';
$reservation_time = $_POST['reservation_time'] ?? '';
$number_of_guests = intval($_POST['number_of_guests'] ?? 0);
$table_id = !empty($_POST['table_id']) ? intval($_POST['table_id']) : null;
$special_requests = trim($_POST['special_requests'] ?? '');

if ($reservation_id <= 0) {
    setErrorMessage('Invalid reservation ID.');
}

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

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get current table assignment
    $current_table_stmt = $conn->prepare('SELECT table_id FROM reservations WHERE reservation_id = ?');
    $current_table_stmt->bind_param('i', $reservation_id);
    $current_table_stmt->execute();
    $current_table_result = $current_table_stmt->get_result();
    $current_table_data = $current_table_result->fetch_assoc();
    $current_table_stmt->close();
    
    // Check if new table is available (if table_id is provided)
    if (!empty($table_id)) {
        $table_check = $conn->prepare('SELECT is_available FROM restaurant_tables WHERE table_id = ? AND is_available = 1');
        $table_check->bind_param('i', $table_id);
        $table_check->execute();
        $table_result = $table_check->get_result();
        
        if ($table_result->num_rows === 0) {
            $table_check->close();
            setErrorMessage('Selected table is not available.');
        }
        $table_check->close();
    }
    
    // Update reservation
    $update_stmt = $conn->prepare('
        UPDATE reservations 
        SET customer_name = ?, customer_phone = ?, customer_email = ?, reservation_date = ?, 
            reservation_time = ?, number_of_guests = ?, table_id = ?, special_requests = ?
        WHERE reservation_id = ?
    ');
    
    $update_stmt->bind_param('sssssissi', 
        $customer_name, 
        $customer_phone, 
        $customer_email, 
        $reservation_date, 
        $reservation_time, 
        $number_of_guests, 
        $table_id, 
        $special_requests,
        $reservation_id
    );
    
    if (!$update_stmt->execute()) {
        $conn->rollback();
        $update_stmt->close();
        setErrorMessage('Failed to update reservation. Please try again.');
    }
    $update_stmt->close();
    
    // Handle table availability
    if ($current_table_data && $current_table_data['table_id'] != $table_id) {
        // Make old table available
        if ($current_table_data['table_id']) {
            $old_table_stmt = $conn->prepare('UPDATE restaurant_tables SET is_available = 1 WHERE table_id = ?');
            $old_table_stmt->bind_param('i', $current_table_data['table_id']);
            $old_table_stmt->execute();
            $old_table_stmt->close();
        }
        
        // Make new table unavailable
        if ($table_id) {
            $new_table_stmt = $conn->prepare('UPDATE restaurant_tables SET is_available = 0 WHERE table_id = ?');
            $new_table_stmt->bind_param('i', $table_id);
            $new_table_stmt->execute();
            $new_table_stmt->close();
        }
    }
    
    // Update reservation items if provided
    if (!empty($_POST['menu_items']) && is_array($_POST['menu_items'])) {
        // Delete existing items
                            $delete_items_stmt = $conn->prepare('UPDATE reservation_items SET deleted_at = NOW() WHERE reservation_id = ?');
        $delete_items_stmt->bind_param('i', $reservation_id);
        $delete_items_stmt->execute();
        $delete_items_stmt->close();
        
        // Insert new items
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
    
    // Commit transaction
    $conn->commit();
    
    setSuccessMessage('Reservation updated successfully.');
    
} catch (Exception $e) {
    $conn->rollback();
    setErrorMessage('An error occurred while updating the reservation.');
}
?>
