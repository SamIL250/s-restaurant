-- Email Logs Table for Purchase Order Emails
-- This table tracks all purchase order emails sent to suppliers

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

-- Add indexes for better performance
CREATE INDEX `idx_email_logs_composite` ON `email_logs` (`purchase_order_id`, `status`, `sent_at`);
CREATE INDEX `idx_email_logs_date_status` ON `email_logs` (`sent_at`, `status`);

-- Insert sample data (optional)
-- INSERT INTO email_logs (purchase_order_id, recipient_email, subject, status) VALUES 
-- (1, 'supplier@example.com', 'Purchase Order PO000001 - Tacos Restaurant', 'sent');
