<?php
// Simple debug script to test email service
echo "Testing email service...\n";

// Test database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'tacos';

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

echo "Database connected successfully\n";

// Check if email_logs table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'email_logs'");
if (mysqli_num_rows($table_check) == 0) {
    echo "ERROR: email_logs table does not exist!\n";
    
    // Create the table
    $create_table = "
    CREATE TABLE IF NOT EXISTS `email_logs` (
      `log_id` int(11) NOT NULL AUTO_INCREMENT,
      `purchase_order_id` int(11) NOT NULL,
      `recipient_email` varchar(255) NOT NULL,
      `subject` varchar(500) NOT NULL,
      `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `status` enum('sent','failed','pending') NOT NULL DEFAULT 'pending',
      `error_message` text DEFAULT NULL,
      `email_content` longtext DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`log_id`),
      KEY `idx_purchase_order_id` (`purchase_order_id`),
      KEY `idx_status` (`status`),
      KEY `idx_sent_at` (`sent_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if (mysqli_query($conn, $create_table)) {
        echo "email_logs table created successfully\n";
    } else {
        echo "Failed to create email_logs table: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "email_logs table exists\n";
}

// Check PHPMailer
if (file_exists('vendor/autoload.php')) {
    echo "PHPMailer autoload exists\n";
    require_once 'vendor/autoload.php';
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        echo "PHPMailer class loaded successfully\n";
    } catch (Exception $e) {
        echo "PHPMailer error: " . $e->getMessage() . "\n";
    }
} else {
    echo "ERROR: PHPMailer autoload not found\n";
}

// Test a simple email log insert
$test_log = mysqli_prepare($conn, "
    INSERT INTO email_logs (purchase_order_id, recipient_email, subject, sent_at, status, email_content)
    VALUES (?, ?, ?, NOW(), 'sent', ?)
");

if ($test_log) {
    $test_po_id = 1;
    $test_email = 'test@example.com';
    $test_subject = 'Test Subject';
    $test_content = 'Test content';
    
    $test_log->bind_param('isss', $test_po_id, $test_email, $test_subject, $test_content);
    
    if ($test_log->execute()) {
        echo "Test log insert successful\n";
        // Clean up test data
        mysqli_query($conn, "DELETE FROM email_logs WHERE recipient_email = 'test@example.com'");
    } else {
        echo "Test log insert failed: " . $test_log->error . "\n";
    }
    $test_log->close();
} else {
    echo "Failed to prepare test log statement: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
echo "Debug test completed\n";
?>
