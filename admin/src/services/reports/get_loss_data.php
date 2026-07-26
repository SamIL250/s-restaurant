<?php
require_once '../../../config/config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$start_date = $input['start'] ?? date('Y-m-01');
$end_date = $input['end'] ?? date('Y-m-t');

try {
    $response = [
        'lossSummary' => getLossSummary($start_date, $end_date),
        'lossByCategory' => getLossByCategory($start_date, $end_date),
        'lossTrends' => getLossTrends($start_date, $end_date),
        'losses' => getLosses($start_date, $end_date)
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getLossSummary($start_date, $end_date) {
    global $conn;
    
    // Get total loss data
    $query = "SELECT 
                SUM(quantity) as total_quantity_lost,
                SUM(total_cost) as total_value_lost,
                COUNT(*) as total_loss_incidents
              FROM stock_movements sm
              JOIN inventory_items i ON sm.item_id = i.item_id
              WHERE sm.movement_type IN ('waste', 'adjustment')
              AND sm.movement_date BETWEEN ? AND ?
              AND i.deleted_at IS NULL";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    $totalQuantityLost = (float)$row['total_quantity_lost'];
    $totalValueLost = (float)$row['total_value_lost'];
    $totalIncidents = (int)$row['total_loss_incidents'];
    
    // Get total stock value for percentage calculation
    $totalStockQuery = "SELECT SUM(current_stock * unit_cost) as total_stock_value 
                       FROM inventory_items 
                       WHERE deleted_at IS NULL AND is_active = 1";
    $totalStockResult = mysqli_query($conn, $totalStockQuery);
    $totalStockRow = mysqli_fetch_assoc($totalStockResult);
    $totalStockValue = (float)$totalStockRow['total_stock_value'];
    
    // Calculate percentages
    $lossPercentage = $totalStockValue > 0 ? ($totalValueLost / $totalStockValue) * 100 : 0;
    $avgLossPerItem = $totalIncidents > 0 ? $totalValueLost / $totalIncidents : 0;
    
    return [
        'totalLossValue' => $totalValueLost,
        'totalQuantityLost' => $totalQuantityLost,
        'totalIncidents' => $totalIncidents,
        'lossPercentage' => round($lossPercentage, 2),
        'avgLossPerItem' => round($avgLossPerItem, 2)
    ];
}

function getLossByCategory($start_date, $end_date) {
    global $conn;
    
    $query = "SELECT 
                COALESCE(c.category_name, 'Uncategorized') as category_name,
                sm.movement_type,
                SUM(sm.quantity) as total_quantity,
                SUM(sm.total_cost) as total_value,
                COUNT(*) as incident_count
              FROM stock_movements sm
              JOIN inventory_items i ON sm.item_id = i.item_id
              LEFT JOIN categories c ON i.category_id = c.category_id
              WHERE sm.movement_type IN ('waste', 'adjustment')
              AND sm.movement_date BETWEEN ? AND ?
              AND i.deleted_at IS NULL
              GROUP BY c.category_id, c.category_name, sm.movement_type
              ORDER BY total_value DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $categories = [];
    $wasteData = [];
    $adjustmentData = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $category = $row['category_name'];
        $type = $row['movement_type'];
        $value = (float)$row['total_value'];
        
        if (!in_array($category, $categories)) {
            $categories[] = $category;
        }
        
        if ($type === 'waste') {
            $wasteData[$category] = $value;
        } elseif ($type === 'adjustment') {
            $adjustmentData[$category] = $value;
        }
    }
    
    // Fill missing values with 0
    foreach ($categories as $category) {
        if (!isset($wasteData[$category])) {
            $wasteData[$category] = 0;
        }
        if (!isset($adjustmentData[$category])) {
            $adjustmentData[$category] = 0;
        }
    }
    
    return [
        'categories' => $categories,
        'waste' => array_values($wasteData),
        'adjustments' => array_values($adjustmentData)
    ];
}

function getLossTrends($start_date, $end_date) {
    global $conn;
    
    $query = "SELECT 
                DATE(movement_date) as date,
                movement_type,
                SUM(quantity) as total_quantity,
                SUM(total_cost) as total_value
              FROM stock_movements sm
              JOIN inventory_items i ON sm.item_id = i.item_id
              WHERE sm.movement_type IN ('waste', 'adjustment')
              AND sm.movement_date BETWEEN ? AND ?
              AND i.deleted_at IS NULL
              GROUP BY DATE(movement_date), movement_type
              ORDER BY date";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $dates = [];
    $waste = [];
    $adjustments = [];
    
    // Initialize arrays with all dates in range
    $current = new DateTime($start_date);
    $end = new DateTime($end_date);
    while ($current <= $end) {
        $dates[] = $current->format('Y-m-d');
        $waste[] = 0;
        $adjustments[] = 0;
        $current->add(new DateInterval('P1D'));
    }
    
    // Fill in actual data
    while ($row = mysqli_fetch_assoc($result)) {
        $date = $row['date'];
        $type = $row['movement_type'];
        $value = (float)$row['total_value'];
        
        $index = array_search($date, $dates);
        if ($index !== false) {
            if ($type === 'waste') {
                $waste[$index] = $value;
            } elseif ($type === 'adjustment') {
                $adjustments[$index] = $value;
            }
        }
    }
    
    return [
        'labels' => $dates,
        'waste' => $waste,
        'adjustments' => $adjustments
    ];
}

function getLosses($start_date, $end_date) {
    global $conn;
    
    $query = "SELECT 
                sm.movement_date,
                sm.movement_type,
                sm.quantity,
                sm.unit_cost,
                sm.total_cost,
                sm.reason,
                i.item_name,
                i.unit_of_measure,
                c.category_name,
                u.first_name,
                u.last_name
              FROM stock_movements sm
              JOIN inventory_items i ON sm.item_id = i.item_id
              LEFT JOIN categories c ON i.category_id = c.category_id
              LEFT JOIN users u ON sm.user_id = u.user_id
              WHERE sm.movement_type IN ('waste', 'adjustment')
              AND sm.movement_date BETWEEN ? AND ?
              AND i.deleted_at IS NULL
              ORDER BY sm.movement_date DESC
              LIMIT 100";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $losses = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $losses[] = [
            'movement_date' => $row['movement_date'],
            'loss_type' => $row['movement_type'],
            'quantity_lost' => (float)$row['quantity'],
            'unit_cost' => (float)$row['unit_cost'],
            'value_lost' => (float)$row['total_cost'],
            'reason' => $row['reason'],
            'item_name' => $row['item_name'],
            'unit_of_measure' => $row['unit_of_measure'],
            'category_name' => $row['category_name'] ?: 'Uncategorized',
            'user_name' => $row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : 'System'
        ];
    }
    
    return $losses;
}
?>
