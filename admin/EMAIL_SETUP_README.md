# 📧 Purchase Order Email System Setup

This document provides comprehensive setup instructions for implementing the PHP Mailer email system to send purchase order emails to suppliers.

## 🚀 Quick Start

### 1. Install Dependencies

```bash
# Install PHPMailer via Composer
composer install

# Or if you don't have Composer, download PHPMailer manually
# Download from: https://github.com/PHPMailer/PHPMailer/releases
# Extract to vendor/phpmailer/phpmailer/
```

### 2. Configure Email Settings

Edit `config/mail_config.php` with your email provider settings:

```php
// For Gmail
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
define('MAIL_FROM_NAME', 'Your Restaurant Name');
define('MAIL_FROM_EMAIL', 'your-email@gmail.com');
```

### 3. Create Database Table

Run the SQL script to create the email logs table:

```sql
-- Execute the contents of database/email_logs_table.sql
```

## 📋 Prerequisites

### System Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (for dependency management)
- SMTP access to your email provider

### Email Provider Setup

#### Gmail Setup
1. **Enable 2-Factor Authentication**
   - Go to Google Account settings
   - Enable 2FA for your account

2. **Generate App Password**
   - Go to Security settings
   - Generate an App Password for "Mail"
   - Use this password in your config (not your regular password)

3. **Enable Less Secure App Access** (Alternative)
   - If not using App Password, enable "Less secure app access"
   - Note: This is less secure and may be disabled by Google

#### Other SMTP Providers
- **Outlook/Hotmail**: Use `smtp-mail.outlook.com` on port 587
- **Yahoo**: Use `smtp.mail.yahoo.com` on port 587
- **Custom SMTP**: Use your provider's SMTP server and port

## ⚙️ Configuration Options

### Mail Configuration File (`config/mail_config.php`)

```php
// SMTP Configuration
define('MAIL_HOST', 'smtp.gmail.com');     // Your SMTP server
define('MAIL_PORT', 587);                  // SMTP port
define('MAIL_USERNAME', 'your-email');     // Your email
define('MAIL_PASSWORD', 'your-password');  // Your password/app password
define('MAIL_FROM_NAME', 'Restaurant');    // Sender name
define('MAIL_FROM_EMAIL', 'your-email');   // Sender email

// Mail Settings
define('MAIL_SECURE', 'tls');              // 'tls' or 'ssl'
define('MAIL_AUTH', true);                 // Enable authentication
define('MAIL_DEBUG', 0);                   // Debug level (0-4)

// Company Information
define('COMPANY_NAME', 'Your Restaurant');
define('COMPANY_ADDRESS', 'Your Address');
define('COMPANY_PHONE', 'Your Phone');
define('COMPANY_EMAIL', 'info@restaurant.com');
define('COMPANY_WEBSITE', 'www.restaurant.com');
```

### Local Development (XAMPP)

For local development without external SMTP:

```php
// Use local mail server
define('MAIL_HOST', 'localhost');
define('MAIL_PORT', 25);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_SECURE', '');
define('MAIL_AUTH', false);
```

## 🔧 Installation Steps

### Step 1: Install PHPMailer

```bash
# Navigate to your project directory
cd /path/to/restaurant_stock

# Install dependencies
composer install

# Verify installation
composer show phpmailer/phpmailer
```

### Step 2: Database Setup

```sql
-- Create email_logs table
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
  KEY `idx_recipient_email` (`recipient_email`),
  KEY `idx_sent_at` (`sent_at`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_email_logs_purchase_order` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`purchase_order_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 3: File Permissions

Ensure the following directories are writable:

```bash
# For email logs and temporary files
chmod 755 src/templates/emails/
chmod 755 config/
chmod 644 config/mail_config.php
```

### Step 4: Test Configuration

Create a test script to verify your email setup:

```php
<?php
// test_email.php
require_once 'config/mail_config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = MAIL_AUTH;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_SECURE;
    $mail->Port = MAIL_PORT;
    $mail->SMTPDebug = 2; // Enable debug output
    
    // Recipients
    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress('test@example.com', 'Test User');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body = '<h1>Test Email</h1><p>If you receive this, your email configuration is working!</p>';
    $mail->AltBody = 'Test Email - If you receive this, your email configuration is working!';
    
    $mail->send();
    echo 'Test email sent successfully!';
} catch (Exception $e) {
    echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
```

## 📧 Email Template Features

### Professional Design
- **Minimalistic**: Clean, business-appropriate design
- **Responsive**: Works on all email clients
- **Branded**: Includes your company information
- **Professional**: Suitable for business communication

### Content Sections
1. **Header**: PO number and company branding
2. **Order Information**: PO details and dates
3. **Supplier Information**: Contact details
4. **Order Items**: Product list with pricing
5. **Special Instructions**: Notes and requirements
6. **Footer**: Company contact information

### Email Formats
- **HTML**: Rich, formatted email with styling
- **Plain Text**: Fallback for email clients that don't support HTML

## 🚨 Troubleshooting

### Common Issues

#### 1. Authentication Failed
```
Error: SMTP connect() failed
```
**Solution**: Check username/password and enable 2FA with App Password

#### 2. Connection Timeout
```
Error: Connection timed out
```
**Solution**: Check firewall settings and SMTP port configuration

#### 3. SSL/TLS Issues
```
Error: SSL certificate problem
```
**Solution**: Use correct port (587 for TLS, 465 for SSL)

#### 4. Gmail Blocking
```
Error: Username and Password not accepted
```
**Solution**: Enable "Less secure app access" or use App Password

### Debug Mode

Enable debug mode to see detailed SMTP communication:

```php
define('MAIL_DEBUG', 2); // Verbose debug output
```

### Testing Checklist

- [ ] Composer dependencies installed
- [ ] Database table created
- [ ] Email configuration updated
- [ ] SMTP credentials verified
- [ ] Test email sent successfully
- [ ] Email logs working
- [ ] PO status updates correctly

## 🔒 Security Considerations

### Email Security
- Use App Passwords instead of regular passwords
- Enable 2-Factor Authentication
- Use TLS encryption (port 587)
- Regularly rotate credentials

### Data Protection
- Validate all email addresses
- Sanitize email content
- Log email activities for audit
- Implement rate limiting if needed

## 📊 Monitoring & Logs

### Email Logs Table
The system automatically logs all email activities:

- **Successful emails**: Status 'sent'
- **Failed emails**: Status 'failed' with error details
- **Email content**: Stored for reference
- **Timestamps**: Track when emails were sent

### Log Queries

```sql
-- View all sent emails
SELECT * FROM email_logs WHERE status = 'sent';

-- View failed emails
SELECT * FROM email_logs WHERE status = 'failed';

-- Email statistics by date
SELECT DATE(sent_at) as date, status, COUNT(*) as count 
FROM email_logs 
GROUP BY DATE(sent_at), status;
```

## 🎯 Usage

### Sending PO Emails

1. **From PO Details Modal**: Click "Send Email" button
2. **Automatic Status Update**: PO status changes from 'draft' to 'sent'
3. **Email Logging**: All activities are logged for tracking
4. **Error Handling**: Failed emails are logged with error details

### Email Content

The system automatically generates:
- Professional email template
- PO details and supplier information
- Order items with pricing
- Company branding and contact info
- Both HTML and plain text versions

## 📞 Support

If you encounter issues:

1. Check the troubleshooting section above
2. Verify your email provider settings
3. Test with the provided test script
4. Check email logs for error details
5. Ensure all dependencies are installed

## 🔄 Updates & Maintenance

### Regular Maintenance
- Monitor email logs for failures
- Update email credentials as needed
- Check for PHPMailer updates
- Review email delivery rates

### Future Enhancements
- Email templates customization
- Bulk email sending
- Email scheduling
- Delivery confirmation tracking
- Advanced reporting and analytics

---

**Note**: This email system is designed for business use and follows email best practices. Ensure compliance with your email provider's terms of service and applicable regulations.
