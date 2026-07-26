<?php
// Mail Configuration for PHP Mailer
// This file contains email settings for sending purchase order emails to suppliers

// SMTP Configuration
define('MAIL_HOST', 'smtp.gmail.com');  // Change to your SMTP server
define('MAIL_PORT', 587);               // SMTP port (587 for TLS, 465 for SSL)
define('MAIL_USERNAME', 'scamil350@gmail.com');  // Your email address
define('MAIL_PASSWORD', 'zwcz bzsi ilxa wggn');     // Your email password or app password
define('MAIL_FROM_NAME', 'Smart Resto Restaurant');     // Your restaurant name
define('MAIL_FROM_EMAIL', 'scamil350@gmail.com'); // Your email address

// Mail Settings
define('MAIL_SECURE', 'tls');           // 'tls' or 'ssl'
define('MAIL_AUTH', true);              // Enable SMTP authentication
define('MAIL_DEBUG', 0);                // Debug level (0 = no output, 2 = verbose)

// Alternative: Use local mail server (for XAMPP)
// define('MAIL_HOST', 'localhost');
// define('MAIL_PORT', 25);
// define('MAIL_USERNAME', '');
// define('MAIL_PASSWORD', '');
// define('MAIL_SECURE', '');
// define('MAIL_AUTH', false);

// Email Templates Path
define('MAIL_TEMPLATES_PATH','./src/templates/emails/');

// Company Information
define('COMPANY_NAME', 'Smart Resto Restaurant');
define('COMPANY_ADDRESS', '123 Restaurant Street, Kigali, Rwanda');
define('COMPANY_PHONE', '+1234567890');
define('COMPANY_EMAIL', 'info@Smart Restorestaurant.com');
define('COMPANY_WEBSITE', 'www.Smart Restorestaurant.com');

// Note: For Gmail, you need to:
// 1. Enable 2-factor authentication
// 2. Generate an App Password
// 3. Use the App Password instead of your regular password
// 4. Make sure "Less secure app access" is enabled (if not using App Password)
?>
