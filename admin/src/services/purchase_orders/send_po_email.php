<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../../config/config.php';
require_once '../../../config/mail_config.php';

// Include PHPMailer classes
require_once '../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include email template
require_once '../../../src/templates/emails/purchase_order_template.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $po_id = intval($_POST['purchase_order_id'] ?? 0);
    if ($po_id <= 0) {
        throw new Exception('Invalid purchase order ID');
    }

    // Get purchase order details
    $po_query = mysqli_prepare($conn, "
        SELECT po.*, s.supplier_name, s.email, s.contact_person, s.phone
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
        WHERE po.purchase_order_id = ? AND po.deleted_at IS NULL
    ");
    
    if (!$po_query) {
        throw new Exception('Database error: ' . mysqli_error($conn));
    }
    
    $po_query->bind_param('i', $po_id);
    $po_query->execute();
    $po_result = $po_query->get_result();
    
    if ($po_result->num_rows === 0) {
        throw new Exception('Purchase order not found');
    }
    
    $po_data = $po_result->fetch_assoc();
    $po_query->close();
    
    // Check if supplier has email
    if (empty($po_data['email'])) {
        throw new Exception('Supplier does not have an email address configured');
    }
    
    // Get PO items if they exist
    $po_items = [];
    $items_query = mysqli_prepare($conn, "
        SELECT poi.*, i.item_name
        FROM po_items poi
        LEFT JOIN inventory_items i ON poi.inventory_item_id = i.inventory_item_id
        WHERE poi.purchase_order_id = ? AND poi.deleted_at IS NULL
    ");
    
    if ($items_query) {
        $items_query->bind_param('i', $po_id);
        $items_query->execute();
        $items_result = $items_query->get_result();
        
        while ($item = $items_result->fetch_assoc()) {
            $po_items[] = $item;
        }
        $items_query->close();
    }
    
    // Prepare supplier data
    $supplier_data = [
        'supplier_name' => $po_data['supplier_name'],
        'contact_person' => $po_data['contact_person'],
        'email' => $po_data['email'],
        'phone' => $po_data['phone']
    ];
    
    // Generate email content
    $html_content = generatePurchaseOrderEmail($po_data, $supplier_data, $po_items);
    $text_content = generatePurchaseOrderEmailText($po_data, $supplier_data, $po_items);
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = MAIL_AUTH;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_SECURE;
        $mail->Port = MAIL_PORT;
        $mail->SMTPDebug = MAIL_DEBUG;
        
        // Recipients
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($po_data['email'], $po_data['supplier_name']);
        
        // Add CC to restaurant email if needed
        if (defined('COMPANY_EMAIL') && COMPANY_EMAIL !== MAIL_FROM_EMAIL) {
            $mail->addCC(COMPANY_EMAIL, COMPANY_NAME);
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Purchase Order ' . $po_data['po_number'] . ' - ' . COMPANY_NAME;
        $mail->Body = $html_content;
        $mail->AltBody = $text_content;
        
        // Send email
        if (!$mail->send()) {
            throw new Exception('Email could not be sent. Mailer Error: ' . $mail->ErrorInfo);
        }
        
        // Update PO status to 'sent' if it was 'draft'
        if ($po_data['po_status'] === 'draft') {
            $update_query = mysqli_prepare($conn, "
                UPDATE purchase_orders 
                SET po_status = 'sent', updated_at = NOW() 
                WHERE purchase_order_id = ?
            ");
            
            if ($update_query) {
                $update_query->bind_param('i', $po_id);
                $update_query->execute();
                $update_query->close();
            }
        }
        
        // Log email sent
        $log_query = mysqli_prepare($conn, "
            INSERT INTO email_logs (purchase_order_id, recipient_email, subject, sent_at, status)
            VALUES (?, ?, ?, NOW(), 'sent')
        ");
        
        if ($log_query) {
            $subject = 'Purchase Order ' . $po_data['po_number'] . ' - ' . COMPANY_NAME;
            $log_query->bind_param('iss', $po_id, $po_data['email'], $subject);
            $log_query->execute();
            $log_query->close();
        }
        
        $response['success'] = true;
        $response['message'] = 'Purchase order email sent successfully to ' . htmlspecialchars($po_data['supplier_name']);
        $response['data'] = [
            'po_number' => $po_data['po_number'],
            'supplier_name' => $po_data['supplier_name'],
            'supplier_email' => $po_data['email'],
            'status_updated' => ($po_data['po_status'] === 'draft')
        ];
        
    } catch (Exception $e) {
        throw new Exception('PHPMailer error: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error sending purchase order email: ' . $e->getMessage();
    
    // Log error if possible
    if (isset($po_id) && $po_id > 0) {
        $error_log_query = mysqli_prepare($conn, "
            INSERT INTO email_logs (purchase_order_id, recipient_email, subject, sent_at, status, error_message)
            VALUES (?, ?, ?, NOW(), 'failed', ?)
        ");
        
        if ($error_log_query) {
            $subject = 'Purchase Order ' . ($po_data['po_number'] ?? 'Unknown') . ' - ' . COMPANY_NAME;
            $email = $po_data['email'] ?? 'unknown@example.com';
            $error_log_query->bind_param('isss', $po_id, $email, $subject, $e->getMessage());
            $error_log_query->execute();
            $error_log_query->close();
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
