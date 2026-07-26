<?php
// Include database connection
include '../../../config/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log incoming data for debugging
error_log("Revenue Chart API Request: " . $json);

$start_date = $data['start_date'] ?? date('Y-m-d', strtotime('-6 days'));
$end_date = $data['end_date'] ?? date('Y-m-d');

try {
    // Get revenue data for the specified date range
    $revenue_query = mysqli_query($conn, "
        SELECT DATE(order_date) as date, COALESCE(SUM(total_amount), 0) as revenue 
        FROM orders 
        WHERE DATE(order_date) BETWEEN '$start_date' AND '$end_date' AND deleted_at IS NULL AND order_status = 'completed'
        GROUP BY DATE(order_date) 
        ORDER BY date ASC
    ");
    
    $revenue_data = ['labels' => [], 'values' => []];
    
    // Generate all dates in the range to ensure no gaps
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    
    while ($current_date <= $end_date_obj) {
        $date_str = $current_date->format('Y-m-d');
        $revenue = 0;
        
        // Check if we have data for this date
        mysqli_data_seek($revenue_query, 0);
        while ($row = mysqli_fetch_assoc($revenue_query)) {
            if ($row['date'] === $date_str) {
                $revenue = floatval($row['revenue']);
                break;
            }
        }
        
        // Format date based on range
        if ($current_date >= new DateTime($start_date) && $current_date <= new DateTime($end_date)) {
            $date_range_days = $current_date->diff(new DateTime($start_date))->days;
            $total_days = $end_date_obj->diff(new DateTime($start_date))->days;
            
            if ($total_days <= 31) { // Less than a month - show day names
                $revenue_data['labels'][] = $current_date->format('M j');
            } elseif ($total_days <= 365) { // Less than a year - show week/month
                if ($current_date->format('d') == '01') { // First of month
                    $revenue_data['labels'][] = $current_date->format('M');
                } else {
                    $revenue_data['labels'][] = $current_date->format('M j');
                }
            } else { // Year or more - show months
                if ($current_date->format('d') == '01') {
                    $revenue_data['labels'][] = $current_date->format('M');
                }
            }
            
            $revenue_data['values'][] = $revenue;
        }
        
        $current_date->add(new DateInterval('P1D'));
    }
    
    $response = [
        'success' => true,
        'labels' => $revenue_data['labels'],
        'values' => $revenue_data['values']
    ];
    
    // Log the response for debugging
    error_log("Revenue Chart API Response: " . json_encode($response));
    
    echo json_encode($response);

} catch (Exception $e) {
    // Log the error for debugging
    error_log("Revenue Chart API Error: " . $e->getMessage());
    
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
    echo json_encode($response);
}

// Ensure we always output something
if (!isset($response)) {
    echo json_encode([
        'success' => false,
        'message' => 'No response generated'
    ]);
}
?>
