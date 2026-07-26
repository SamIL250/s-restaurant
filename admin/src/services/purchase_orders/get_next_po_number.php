<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../../config/config.php';

// Initialize response
$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

try {
    // Get the last PO number from the database
    $last_po_query = mysqli_query($conn, "
        SELECT po_number 
        FROM purchase_orders 
        WHERE po_number LIKE 'PO%' 
        ORDER BY CAST(SUBSTRING(po_number, 3) AS UNSIGNED) DESC 
        LIMIT 1
    ");
    
    if (!$last_po_query) {
        throw new Exception('Error fetching last PO number: ' . mysqli_error($conn));
    }
    
    $next_number = 1; // Default starting number
    
    if (mysqli_num_rows($last_po_query) > 0) {
        $last_po = mysqli_fetch_assoc($last_po_query);
        $last_number = (int)substr($last_po['po_number'], 2); // Remove 'PO' prefix
        $next_number = $last_number + 1;
    }
    
    // Format the next PO number
    $next_po_number = 'PO' . str_pad($next_number, 6, '0', STR_PAD_LEFT);
    
    $response['data'] = [
        'next_po_number' => $next_po_number,
        'next_number' => $next_number
    ];
    
    $response['success'] = true;
    $response['message'] = 'Next PO number generated successfully';
    
} catch (Exception $e) {
    $response['message'] = 'Error generating next PO number: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
