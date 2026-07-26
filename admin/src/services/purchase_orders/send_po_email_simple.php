<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Include PHPMailer
require_once '../../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'tacos';

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

// Email configuration (put your actual email settings here)
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_username = 'scamil350@gmail.com';  // CHANGE THIS
$smtp_password = 'zwcz bzsi ilxa wggn';     // CHANGE THIS
$from_name = 'Tacos Restaurant';
$from_email = 'scamil350@gmail.com';     // CHANGE THIS

// Company information
$company_name = 'Tacos Restaurant';
$company_address = '123 Restaurant Street, City, Country';
$company_phone = '+1234567890';
$company_email = 'info@tacosrestaurant.com';
$company_website = 'www.tacosrestaurant.com';

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
        FROM purchase_order_items poi
        LEFT JOIN inventory_items i ON poi.item_id = i.item_id
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
    
    // Generate email content
    $email_subject = 'Purchase Order ' . $po_data['po_number'] . ' - ' . $company_name;
    
    // HTML Email Content
    $html_content = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Purchase Order - ' . htmlspecialchars($po_data['po_number']) . '</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa; }
            .email-container { background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
            .header { background-color: #2c3e50; color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; font-weight: 300; }
            .header .po-number { font-size: 18px; margin-top: 10px; opacity: 0.9; }
            .content { padding: 30px; }
            .section { margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #e9ecef; }
            .section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
            .section h2 { color: #2c3e50; font-size: 18px; margin-bottom: 15px; font-weight: 600; }
            .info-grid { display: table; width: 100%; margin-bottom: 15px; }
            .info-row { display: table-row; }
            .info-label { display: table-cell; width: 120px; font-weight: 600; color: #6c757d; padding: 8px 0; }
            .info-value { display: table-cell; padding: 8px 0; }
            .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            .items-table th { background-color: #f8f9fa; padding: 12px 8px; text-align: left; font-weight: 600; color: #495057; border-bottom: 2px solid #dee2e6; }
            .items-table td { padding: 12px 8px; border-bottom: 1px solid #e9ecef; }
            .items-table .quantity { text-align: center; }
            .items-table .price { text-align: right; }
            .total-section { background-color: #f8f9fa; padding: 20px; border-radius: 6px; margin-top: 20px; }
            .total-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
            .total-row.total { font-size: 18px; font-weight: 600; color: #2c3e50; border-top: 2px solid #dee2e50; padding-top: 15px; margin-top: 15px; }
            .footer { background-color: #f8f9fa; padding: 20px 30px; text-align: center; color: #6c757d; font-size: 14px; }
            .notes { background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <h1>Purchase Order</h1>
                <div class="po-number">' . htmlspecialchars($po_data['po_number']) . '</div>
            </div>
            
            <div class="content">
                <div class="section">
                    <h2>Order Information</h2>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">PO Number:</div>
                            <div class="info-value">' . htmlspecialchars($po_data['po_number']) . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Order Date:</div>
                            <div class="info-value">' . date('F d, Y', strtotime($po_data['order_date'])) . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Expected Delivery:</div>
                            <div class="info-value">' . ($po_data['expected_delivery_date'] ? date('F d, Y', strtotime($po_data['expected_delivery_date'])) : 'Not specified') . '</div>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <h2>Supplier Information</h2>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Company:</div>
                            <div class="info-value">' . htmlspecialchars($po_data['supplier_name']) . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Contact:</div>
                            <div class="info-value">' . htmlspecialchars($po_data['contact_person'] ?? 'N/A') . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value">' . htmlspecialchars($po_data['email'] ?? 'N/A') . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phone:</div>
                            <div class="info-value">' . htmlspecialchars($po_data['phone'] ?? 'N/A') . '</div>
                        </div>
                    </div>
                </div>';

    // Add items table if items are provided
    if (!empty($po_items)) {
        $html_content .= '
                <div class="section">
                    <h2>Order Items</h2>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="quantity">Quantity</th>
                                <th class="price">Unit Price</th>
                                <th class="price">Total</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        $subtotal = 0;
        foreach ($po_items as $item) {
            $itemTotal = $item['quantity_ordered'] * $item['unit_cost'];
            $subtotal += $itemTotal;
            $html_content .= '
                            <tr>
                                <td>' . htmlspecialchars($item['item_name']) . '</td>
                                <td class="quantity">' . $item['quantity_ordered'] . '</td>
                                <td class="price">Frw ' . number_format($item['unit_cost'], 2) . '</td>
                                <td class="price">Frw ' . number_format($itemTotal, 2) . '</td>
                            </tr>';
        }
        
        $html_content .= '
                        </tbody>
                    </table>
                    
                    <div class="total-section">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span>Frw ' . number_format($subtotal, 2) . '</span>
                        </div>
                        <div class="total-row total">
                            <span>Total Amount:</span>
                            <span>Frw ' . number_format($subtotal, 2) . '</span>
                        </div>
                    </div>
                </div>';
    } else {
        // Show general order info when no items
        $html_content .= '
                <div class="section">
                    <h2>Order Details</h2>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Order Type:</div>
                            <div class="info-value">General Purchase Order</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Total Amount:</div>
                            <div class="info-value">' . ($po_data['total_amount'] ? 'Frw ' . number_format($po_data['total_amount'], 2) : 'To be determined') . '</div>
                        </div>
                    </div>
                </div>';
    }
    
    // Add notes if available
    if (!empty($po_data['notes'])) {
        $html_content .= '
                <div class="section">
                    <div class="notes">
                        <h3>Special Instructions</h3>
                        <p>' . nl2br(htmlspecialchars($po_data['notes'])) . '</p>
                    </div>
                </div>';
    }
    
    $html_content .= '
            </div>
            
            <div class="footer">
                <div class="company-info">
                    <strong>' . $company_name . '</strong><br>
                    ' . $company_address . '
                </div>
                <div class="contact-info">
                    Phone: ' . $company_phone . ' | Email: ' . $company_email . '<br>
                    Website: ' . $company_website . '
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    // Plain text version
    $text_content = "PURCHASE ORDER\n";
    $text_content .= str_repeat("=", 50) . "\n\n";
    $text_content .= "PO Number: " . $po_data['po_number'] . "\n";
    $text_content .= "Order Date: " . date('F d, Y', strtotime($po_data['order_date'])) . "\n";
    $text_content .= "Expected Delivery: " . ($po_data['expected_delivery_date'] ? date('F d, Y', strtotime($po_data['expected_delivery_date'])) : 'Not specified') . "\n\n";
    $text_content .= "SUPPLIER INFORMATION\n";
    $text_content .= str_repeat("-", 25) . "\n";
    $text_content .= "Company: " . $po_data['supplier_name'] . "\n";
    $text_content .= "Contact: " . ($po_data['contact_person'] ?? 'N/A') . "\n";
    $text_content .= "Email: " . ($po_data['email'] ?? 'N/A') . "\n";
    $text_content .= "Phone: " . ($po_data['phone'] ?? 'N/A') . "\n\n";
    
    if (!empty($po_items)) {
        $text_content .= "ORDER ITEMS\n";
        $text_content .= str_repeat("-", 15) . "\n";
        $subtotal = 0;
        foreach ($po_items as $item) {
            $itemTotal = $item['quantity_ordered'] * $item['unit_cost'];
            $subtotal += $itemTotal;
            $text_content .= $item['item_name'] . " - Qty: " . $item['quantity_ordered'] . " @ Frw " . number_format($item['unit_cost'], 2) . " = Frw " . number_format($itemTotal, 2) . "\n";
        }
        $text_content .= "\nTotal Amount: Frw " . number_format($subtotal, 2) . "\n\n";
    } else {
        $text_content .= "ORDER DETAILS\n";
        $text_content .= str_repeat("-", 15) . "\n";
        $text_content .= "Order Type: General Purchase Order\n";
        $text_content .= "Total Amount: " . ($po_data['total_amount'] ? 'Frw ' . number_format($po_data['total_amount'], 2) : 'To be determined') . "\n\n";
    }
    
    if (!empty($po_data['notes'])) {
        $text_content .= "SPECIAL INSTRUCTIONS\n";
        $text_content .= str_repeat("-", 25) . "\n";
        $text_content .= $po_data['notes'] . "\n\n";
    }
    
    $text_content .= str_repeat("=", 50) . "\n";
    $text_content .= $company_name . "\n";
    $text_content .= $company_address . "\n";
    $text_content .= "Phone: " . $company_phone . " | Email: " . $company_email . "\n";
    $text_content .= "Website: " . $company_website . "\n";

    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username;
        $mail->Password   = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp_port;
        
        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($po_data['email'], $po_data['supplier_name']);
        $mail->addReplyTo($from_email, $from_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $email_subject;
        $mail->Body    = $html_content;
        $mail->AltBody = $text_content;
        
        // Send email
        $mail->send();
        
        // Log successful email
        $log_query = mysqli_prepare($conn, "
            INSERT INTO email_logs (purchase_order_id, recipient_email, subject, sent_at, status, email_content)
            VALUES (?, ?, ?, NOW(), 'sent', ?)
        ");
        
        if ($log_query) {
            $log_query->bind_param('isss', $po_id, $po_data['email'], $email_subject, $html_content);
            $log_query->execute();
            $log_query->close();
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
        
        $response['success'] = true;
        $response['message'] = 'Purchase order email sent successfully!';
        $response['data'] = [
            'po_number' => $po_data['po_number'],
            'supplier_name' => $po_data['supplier_name'],
            'supplier_email' => $po_data['email'],
            'status_updated' => ($po_data['po_status'] === 'draft'),
            'email_sent' => true
        ];
        
    } catch (Exception $mailException) {
        // Log failed email
        $log_query = mysqli_prepare($conn, "
            INSERT INTO email_logs (purchase_order_id, recipient_email, subject, sent_at, status, email_content, error_message)
            VALUES (?, ?, ?, NOW(), 'failed', ?, ?)
        ");
        
        if ($log_query) {
            $log_query->bind_param('issss', $po_id, $po_data['email'], $email_subject, $html_content, $mailException->getMessage());
            $log_query->execute();
            $log_query->close();
        }
        
        throw new Exception('Failed to send email: ' . $mailException->getMessage());
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error processing purchase order email: ' . $e->getMessage();
    
    // Log error if possible
    if (isset($po_id) && $po_id > 0) {
        $error_log_query = mysqli_prepare($conn, "
            INSERT INTO email_logs (purchase_order_id, recipient_email, subject, sent_at, status, error_message)
            VALUES (?, ?, ?, NOW(), 'failed', ?)
        ");
        
        if ($error_log_query) {
            $subject = 'Purchase Order ' . ($po_data['po_number'] ?? 'Unknown') . ' - ' . $company_name;
            $email = $po_data['email'] ?? 'unknown@example.com';
            $error_log_query->bind_param('isss', $po_id, $email, $subject, $e->getMessage());
            $error_log_query->execute();
            $error_log_query->close();
        }
    }
}

// Close database connection
mysqli_close($conn);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
