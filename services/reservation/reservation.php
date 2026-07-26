<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once '../../config/connection.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and sanitize input data
        $customer_name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $customer_email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
        $customer_phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
        $reservation_date = mysqli_real_escape_string($conn, $_POST['date'] ?? '');
        $reservation_time = mysqli_real_escape_string($conn, $_POST['time'] ?? '');
        $number_of_guests = intval($_POST['people'] ?? 0);
        $special_requests = mysqli_real_escape_string($conn, $_POST['message'] ?? '');

        // Validate required fields
        if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || 
            empty($reservation_date) || empty($reservation_time) || $number_of_guests <= 0) {
            throw new Exception('Please complete all required fields to continue.');
        }

        // Validate email format
        if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address (e.g., name@example.com).');
        }

        // Validate phone number (basic validation for Rwandan phone numbers)
        if (!preg_match('/^(\+250|07)[0-9]{8}$/', $customer_phone)) {
            throw new Exception('Please enter a valid Rwandan phone number (e.g., 0788123456 or +250788123456).');
        }

        // Validate date (not in the past)
        $reservation_datetime = new DateTime($reservation_date . ' ' . $reservation_time);
        $now = new DateTime();
        if ($reservation_datetime < $now) {
            throw new Exception('Reservation date and time cannot be in the past. Please select a future date and time.');
        }

        // Validate number of guests
        if ($number_of_guests < 1 || $number_of_guests > 50) {
            throw new Exception('Number of guests must be between 1 and 50 people.');
        }

        // Check if reservation time is within business hours (9:00 AM - 11:00 PM)
        $time_obj = new DateTime($reservation_time);
        $hour = intval($time_obj->format('H'));
        if ($hour < 9 || $hour >= 23) {
            throw new Exception('Reservation time must be between 9:00 AM and 11:00 PM. Our restaurant is open during these hours.');
        }

        // Insert reservation into database
        $query = "INSERT INTO reservations (customer_name, customer_phone, customer_email, reservation_date, 
                  reservation_time, number_of_guests, special_requests, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssis", 
                $customer_name, $customer_phone, $customer_email, 
                $reservation_date, $reservation_time, $number_of_guests, $special_requests
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Perfect! Your table reservation has been received. We\'ll send you a confirmation message shortly.';
                $response['reservation_id'] = mysqli_insert_id($conn);
            } else {
                throw new Exception('Oops! Something went wrong while saving your reservation. Please try again.');
            }
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception('Sorry! We\'re experiencing technical difficulties. Please try again in a few minutes.');
        }

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request. Please refresh the page and try again.';
}

// Close database connection
if ($conn) {
    mysqli_close($conn);
}

echo json_encode($response);
?>