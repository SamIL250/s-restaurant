<?php
/**
 * Purchase Order Email Template
 * Professional and minimalistic design for sending to suppliers
 */

function generatePurchaseOrderEmail($poData, $supplierData, $poItems = []) {
    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Purchase Order - ' . htmlspecialchars($poData['po_number']) . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f8f9fa;
            }
            .email-container {
                background-color: #ffffff;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            .header {
                background-color: #2c3e50;
                color: white;
                padding: 30px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 300;
            }
            .header .po-number {
                font-size: 18px;
                margin-top: 10px;
                opacity: 0.9;
            }
            .content {
                padding: 30px;
            }
            .section {
                margin-bottom: 25px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e9ecef;
            }
            .section:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }
            .section h2 {
                color: #2c3e50;
                font-size: 18px;
                margin-bottom: 15px;
                font-weight: 600;
            }
            .info-grid {
                display: table;
                width: 100%;
                margin-bottom: 15px;
            }
            .info-row {
                display: table-row;
            }
            .info-label {
                display: table-cell;
                width: 120px;
                font-weight: 600;
                color: #6c757d;
                padding: 8px 0;
            }
            .info-value {
                display: table-cell;
                padding: 8px 0;
            }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }
            .items-table th {
                background-color: #f8f9fa;
                padding: 12px 8px;
                text-align: left;
                font-weight: 600;
                color: #495057;
                border-bottom: 2px solid #dee2e6;
            }
            .items-table td {
                padding: 12px 8px;
                border-bottom: 1px solid #e9ecef;
            }
            .items-table .quantity {
                text-align: center;
            }
            .items-table .price {
                text-align: right;
            }
            .total-section {
                background-color: #f8f9fa;
                padding: 20px;
                border-radius: 6px;
                margin-top: 20px;
            }
            .total-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
            }
            .total-row.total {
                font-size: 18px;
                font-weight: 600;
                color: #2c3e50;
                border-top: 2px solid #dee2e50;
                padding-top: 15px;
                margin-top: 15px;
            }
            .footer {
                background-color: #f8f9fa;
                padding: 20px 30px;
                text-align: center;
                color: #6c757d;
                font-size: 14px;
            }
            .footer .company-info {
                margin-bottom: 15px;
            }
            .footer .contact-info {
                font-size: 12px;
                opacity: 0.8;
            }
            .notes {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 6px;
                padding: 15px;
                margin-top: 20px;
            }
            .notes h3 {
                margin: 0 0 10px 0;
                color: #856404;
                font-size: 16px;
            }
            .notes p {
                margin: 0;
                color: #856404;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <h1>Purchase Order</h1>
                <div class="po-number">' . htmlspecialchars($poData['po_number']) . '</div>
            </div>
            
            <div class="content">
                <div class="section">
                    <h2>Order Information</h2>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">PO Number:</div>
                            <div class="info-value">' . htmlspecialchars($poData['po_number']) . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Order Date:</div>
                            <div class="info-value">' . date('F d, Y', strtotime($poData['order_date'])) . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Expected Delivery:</div>
                            <div class="info-value">' . ($poData['expected_delivery_date'] ? date('F d, Y', strtotime($poData['expected_delivery_date'])) : 'Not specified') . '</div>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <h2>Supplier Information</h2>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Company:</div>
                            <div class="info-value">' . htmlspecialchars($supplierData['supplier_name']) . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Contact:</div>
                            <div class="info-value">' . htmlspecialchars($supplierData['contact_person'] ?? 'N/A') . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value">' . htmlspecialchars($supplierData['email'] ?? 'N/A') . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phone:</div>
                            <div class="info-value">' . htmlspecialchars($supplierData['phone'] ?? 'N/A') . '</div>
                        </div>
                    </div>
                </div>';
    
    // Add items table if items are provided
    if (!empty($poItems)) {
        $html .= '
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
        foreach ($poItems as $item) {
            $itemTotal = $item['quantity'] * $item['unit_price'];
            $subtotal += $itemTotal;
            $html .= '
                            <tr>
                                <td>' . htmlspecialchars($item['item_name']) . '</td>
                                <td class="quantity">' . $item['quantity'] . '</td>
                                <td class="price">Frw ' . number_format($item['unit_price'], 2) . '</td>
                                <td class="price">Frw ' . number_format($itemTotal, 2) . '</td>
                            </tr>';
        }
        
        $html .= '
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
    }
    
    // Add notes if available
    if (!empty($poData['notes'])) {
        $html .= '
                <div class="section">
                    <div class="notes">
                        <h3>Special Instructions</h3>
                        <p>' . nl2br(htmlspecialchars($poData['notes'])) . '</p>
                    </div>
                </div>';
    }
    
    $html .= '
            </div>
            
            <div class="footer">
                <div class="company-info">
                    <strong>' . COMPANY_NAME . '</strong><br>
                    ' . COMPANY_ADDRESS . '
                </div>
                <div class="contact-info">
                    Phone: ' . COMPANY_PHONE . ' | Email: ' . COMPANY_EMAIL . '<br>
                    Website: ' . COMPANY_WEBSITE . '
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Generate plain text version of the email
 */
function generatePurchaseOrderEmailText($poData, $supplierData, $poItems = []) {
    $text = "PURCHASE ORDER\n";
    $text .= str_repeat("=", 50) . "\n\n";
    
    $text .= "PO Number: " . $poData['po_number'] . "\n";
    $text .= "Order Date: " . date('F d, Y', strtotime($poData['order_date'])) . "\n";
    $text .= "Expected Delivery: " . ($poData['expected_delivery_date'] ? date('F d, Y', strtotime($poData['expected_delivery_date'])) : 'Not specified') . "\n\n";
    
    $text .= "SUPPLIER INFORMATION\n";
    $text .= str_repeat("-", 25) . "\n";
    $text .= "Company: " . $supplierData['supplier_name'] . "\n";
    $text .= "Contact: " . ($supplierData['contact_person'] ?? 'N/A') . "\n";
    $text .= "Email: " . ($supplierData['email'] ?? 'N/A') . "\n";
    $text .= "Phone: " . ($supplierData['phone'] ?? 'N/A') . "\n\n";
    
    if (!empty($poItems)) {
        $text .= "ORDER ITEMS\n";
        $text .= str_repeat("-", 15) . "\n";
        
        $subtotal = 0;
        foreach ($poItems as $item) {
            $itemTotal = $item['quantity'] * $item['unit_price'];
            $subtotal += $itemTotal;
            $text .= $item['item_name'] . " - Qty: " . $item['quantity'] . " @ Frw " . number_format($item['unit_price'], 2) . " = Frw " . number_format($itemTotal, 2) . "\n";
        }
        
        $text .= "\nTotal Amount: Frw " . number_format($subtotal, 2) . "\n\n";
    }
    
    if (!empty($poData['notes'])) {
        $text .= "SPECIAL INSTRUCTIONS\n";
        $text .= str_repeat("-", 25) . "\n";
        $text .= $poData['notes'] . "\n\n";
    }
    
    $text .= str_repeat("=", 50) . "\n";
    $text .= COMPANY_NAME . "\n";
    $text .= COMPANY_ADDRESS . "\n";
    $text .= "Phone: " . COMPANY_PHONE . " | Email: " . COMPANY_EMAIL . "\n";
    $text .= "Website: " . COMPANY_WEBSITE . "\n";
    
    return $text;
}
?>
