<?php
/**
 * Test Email Script
 * Use this script to test your email configuration
 * 
 * Usage: Run this file in your browser or via command line
 * Make sure to update the recipient email address below
 */

// Include configuration
require_once 'config/mail_config.php';

// Check if PHPMailer is available
if (!file_exists('vendor/autoload.php')) {
    die('❌ PHPMailer not found. Please run "composer install" first.');
}

require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Configuration
$test_recipient = 'test@example.com'; // Change this to your test email
$test_recipient_name = 'Test User';

echo "<h1>📧 Email Configuration Test</h1>\n";
echo "<p>Testing email configuration for: <strong>" . COMPANY_NAME . "</strong></p>\n";
echo "<hr>\n";

// Display current configuration
echo "<h2>🔧 Current Configuration</h2>\n";
echo "<ul>\n";
echo "<li><strong>SMTP Host:</strong> " . MAIL_HOST . "</li>\n";
echo "<li><strong>SMTP Port:</strong> " . MAIL_PORT . "</li>\n";
echo "<li><strong>Username:</strong> " . MAIL_USERNAME . "</li>\n";
echo "<li><strong>From Name:</strong> " . MAIL_FROM_NAME . "</li>\n";
echo "<li><strong>From Email:</strong> " . MAIL_FROM_EMAIL . "</li>\n";
echo "<li><strong>Security:</strong> " . MAIL_SECURE . "</li>\n";
echo "<li><strong>Authentication:</strong> " . (MAIL_AUTH ? 'Enabled' : 'Disabled') . "</li>\n";
echo "</ul>\n";

echo "<hr>\n";

// Test email sending
echo "<h2>🚀 Testing Email Sending</h2>\n";
echo "<p>Sending test email to: <strong>$test_recipient</strong></p>\n";

try {
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = MAIL_AUTH;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_SECURE;
    $mail->Port = MAIL_PORT;
    $mail->SMTPDebug = 0; // Set to 2 for verbose debug output
    
    // Recipients
    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress($test_recipient, $test_recipient_name);
    
    // Add CC to company email if different
    if (defined('COMPANY_EMAIL') && COMPANY_EMAIL !== MAIL_FROM_EMAIL) {
        $mail->addCC(COMPANY_EMAIL, COMPANY_NAME);
    }
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email - ' . COMPANY_NAME . ' Email System';
    $mail->Body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h1 style="color: #2c3e50;">✅ Email Test Successful!</h1>
        <p>This is a test email from your <strong>' . COMPANY_NAME . '</strong> email system.</p>
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3>System Information:</h3>
            <ul>
                <li><strong>Company:</strong> ' . COMPANY_NAME . '</li>
                <li><strong>Address:</strong> ' . COMPANY_ADDRESS . '</li>
                <li><strong>Phone:</strong> ' . COMPANY_PHONE . '</li>
                <li><strong>Email:</strong> ' . COMPANY_EMAIL . '</li>
                <li><strong>Website:</strong> ' . COMPANY_WEBSITE . '</li>
            </ul>
        </div>
        <p>If you received this email, your email configuration is working correctly!</p>
        <p style="color: #6c757d; font-size: 14px;">
            Sent at: ' . date('Y-m-d H:i:s') . '<br>
            From: ' . MAIL_FROM_EMAIL . '
        </p>
    </div>';
    
    $mail->AltBody = "Test Email Successful!\n\n" .
                     "This is a test email from your " . COMPANY_NAME . " email system.\n" .
                     "If you received this email, your email configuration is working correctly!\n\n" .
                     "Sent at: " . date('Y-m-d H:i:s') . "\n" .
                     "From: " . MAIL_FROM_EMAIL;
    
    // Send email
    if (!$mail->send()) {
        throw new Exception('Email could not be sent. Mailer Error: ' . $mail->ErrorInfo);
    }
    
    echo "<div style='color: green; background-color: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>\n";
    echo "✅ <strong>SUCCESS!</strong> Test email sent successfully!\n";
    echo "</div>\n";
    
    echo "<p><strong>Email Details:</strong></p>\n";
    echo "<ul>\n";
    echo "<li><strong>To:</strong> $test_recipient</li>\n";
    echo "<li><strong>Subject:</strong> " . $mail->Subject . "</li>\n";
    echo "<li><strong>Sent At:</strong> " . date('Y-m-d H:i:s') . "</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<div style='color: #721c24; background-color: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>\n";
    echo "❌ <strong>ERROR!</strong> Email could not be sent.\n";
    echo "</div>\n";
    
    echo "<p><strong>Error Details:</strong></p>\n";
    echo "<p style='color: #721c24;'>" . $e->getMessage() . "</p>\n";
    
    echo "<h3>🔍 Troubleshooting Steps:</h3>\n";
    echo "<ol>\n";
    echo "<li>Check your SMTP credentials in <code>config/mail_config.php</code></li>\n";
    echo "<li>Verify your email provider settings (Gmail, Outlook, etc.)</li>\n";
    echo "<li>Ensure 2FA is enabled and App Password is generated (for Gmail)</li>\n";
    echo "<li>Check firewall and network settings</li>\n";
    echo "<li>Try enabling debug mode by setting <code>MAIL_DEBUG = 2</code></li>\n";
    echo "</ol>\n";
}

echo "<hr>\n";

// Display company information
echo "<h2>🏢 Company Information</h2>\n";
echo "<div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px;'>\n";
echo "<p><strong>Company Name:</strong> " . COMPANY_NAME . "</p>\n";
echo "<p><strong>Address:</strong> " . COMPANY_ADDRESS . "</p>\n";
echo "<p><strong>Phone:</strong> " . COMPANY_PHONE . "</p>\n";
echo "<p><strong>Email:</strong> " . COMPANY_EMAIL . "</p>\n";
echo "<p><strong>Website:</strong> " . COMPANY_WEBSITE . "</p>\n";
echo "</div>\n";

echo "<hr>\n";

// Next steps
echo "<h2>📋 Next Steps</h2>\n";
echo "<ol>\n";
echo "<li>✅ Update recipient email address in this script</li>\n";
echo "<li>✅ Test email sending</li>\n";
echo "<li>✅ Create the <code>email_logs</code> table in your database</li>\n";
echo "<li>✅ Test sending PO emails from the purchase orders page</li>\n";
echo "<li>✅ Monitor email logs for successful delivery</li>\n";
echo "</ol>\n";

echo "<p><strong>Note:</strong> Make sure to remove or secure this test file in production.</p>\n";
?>
