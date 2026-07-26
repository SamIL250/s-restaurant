<?php
// Database diagnostic script
header('Content-Type: text/plain');

// Include database connection
require_once '../../config/connection.php';

echo "=== DATABASE DIAGNOSTIC ===\n\n";

if (!$conn) {
    echo "ERROR: No database connection\n";
    exit;
}

echo "Database: tacos\n";
echo "Connection: SUCCESS\n\n";

// Check orders table structure
echo "=== ORDERS TABLE STRUCTURE ===\n";
$result = mysqli_query($conn, "DESCRIBE orders");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Column: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']} | Default: {$row['Default']}\n";
    }
} else {
    echo "ERROR: " . mysqli_error($conn) . "\n";
}

echo "\n=== ORDER_ITEMS TABLE STRUCTURE ===\n";
$result = mysqli_query($conn, "DESCRIBE order_items");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Column: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']} | Default: {$row['Default']}\n";
    }
} else {
    echo "ERROR: " . mysqli_error($conn) . "\n";
}

echo "\n=== TEST ENUM VALUES ===\n";
echo "Testing payment_method ENUM values:\n";

$test_values = ['cash', 'card', 'online', 'mobile'];
foreach ($test_values as $value) {
    $test_sql = "SELECT '$value' as test_value";
    $result = mysqli_query($conn, $test_sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "Value: '$value' -> Length: " . strlen($value) . " -> Hex: " . bin2hex($value) . "\n";
    }
}

echo "\n=== SAMPLE ORDER INSERT TEST ===\n";
$test_order_sql = "INSERT INTO orders (order_number, order_type, order_status, payment_status, payment_method, subtotal, tax_amount, total_amount, special_instructions, order_date) VALUES (?, ?, 'pending', 'pending', ?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($conn, $test_order_sql);
if ($stmt) {
    echo "Statement preparation: SUCCESS\n";
    
    $test_number = 'TEST123';
    $test_type = 'takeaway';
    $test_payment = 'cash';
    $test_subtotal = 10.00;
    $test_tax = 1.80;
    $test_total = 11.80;
    $test_instructions = '';
    
    if (mysqli_stmt_bind_param($stmt, "ssdddds", $test_number, $test_type, $test_payment, $test_subtotal, $test_tax, $test_total, $test_instructions)) {
        echo "Parameter binding: SUCCESS\n";
        
        if (mysqli_stmt_execute($stmt)) {
            echo "Test execution: SUCCESS\n";
            mysqli_stmt_close($stmt);
            
            // Clean up test record
            mysqli_query($conn, "DELETE FROM orders WHERE order_number = 'TEST123'");
            echo "Test cleanup: SUCCESS\n";
        } else {
            echo "Test execution: FAILED - " . mysqli_stmt_error($stmt) . "\n";
        }
    } else {
        echo "Parameter binding: FAILED - " . mysqli_stmt_error($stmt) . "\n";
    }
} else {
    echo "Statement preparation: FAILED - " . mysqli_error($conn) . "\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";

// Close connection
if ($conn) {
    mysqli_close($conn);
}
?>
