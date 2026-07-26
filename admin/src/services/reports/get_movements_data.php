<?php
require_once '../../../config/config.php';
require_once '../utils/report_helpers.php';
session_start();

header('Content-Type: application/json');

$input = require_post_json();
$range = normalize_date_range($input);
$start_date = $range['start'];
$end_date = $range['end'];

try {
    $response = [
        'movementSummary' => getMovementSummary($start_date, $end_date),
        'movementTrends' => getMovementTrendsDetailed($start_date, $end_date),
        'movementByCategory' => getMovementByCategory($start_date, $end_date),
        'movements' => getMovements($start_date, $end_date)
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getMovementSummary($start_date, $end_date) {
    global $conn;
    
    $query = "SELECT 
                movement_type,
                SUM(quantity) as total_quantity,
                SUM(total_cost) as total_value
              FROM stock_movements sm
              JOIN inventory_items i ON sm.item_id = i.item_id
              WHERE sm.movement_date BETWEEN ? AND ?
              AND i.deleted_at IS NULL
              GROUP BY movement_type";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $summary = [
        'totalIn' => 0,
        'totalOut' => 0,
        'totalAdjustments' => 0,
        'totalWaste' => 0,
        'totalInValue' => 0,
        'totalOutValue' => 0,
        'totalAdjustmentsValue' => 0,
        'totalWasteValue' => 0
    ];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $type = $row['movement_type'];
        $quantity = (float)$row['total_quantity'];
        $value = (float)$row['total_value'];
        
        switch ($type) {
            case 'in':
                $summary['totalIn'] = $quantity;
                $summary['totalInValue'] = $value;
                break;
            case 'out':
                $summary['totalOut'] = $quantity;
                $summary['totalOutValue'] = $value;
                break;
            case 'adjustment':
                $summary['totalAdjustments'] = $quantity;
                $summary['totalAdjustmentsValue'] = $value;
                break;
            case 'waste':
                $summary['totalWaste'] = $quantity;
                $summary['totalWasteValue'] = $value;
                break;
        }
    }
    
    return $summary;
}

function getMovementTrendsDetailed($start_date, $end_date) {
    global $conn;
    
    // Get daily movement data with more detail
    $query = "SELECT 
                DATE(movement_date) as date,
                movement_type,
                SUM(quantity) as total_quantity,
                SUM(total_cost) as total_value
              FROM stock_movements sm
              JOIN inventory_items i ON sm.item_id = i.item_id
              WHERE sm.movement_date BETWEEN ? AND ?
              AND i.deleted_at IS NULL
              GROUP BY DATE(movement_date), movement_type
              ORDER BY date";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $dates = [];
    $stockIn = [];
    $stockOut = [];
    $adjustments = [];
    $waste = [];
    $stockInValue = [];
    $stockOutValue = [];
    $adjustmentsValue = [];
    $wasteValue = [];
    
    // Initialize arrays with all dates in range
    $current = new DateTime($start_date);
    $end = new DateTime($end_date);
    while ($current <= $end) {
        $dates[] = $current->format('Y-m-d');
        $stockIn[] = 0;
        $stockOut[] = 0;
        $adjustments[] = 0;
        $waste[] = 0;
        $stockInValue[] = 0;
        $stockOutValue[] = 0;
        $adjustmentsValue[] = 0;
        $wasteValue[] = 0;
        $current->add(new DateInterval('P1D'));
    }
    
    // Fill in actual data
    while ($row = mysqli_fetch_assoc($result)) {
        $date = $row['date'];
        $type = $row['movement_type'];
        $quantity = (float)$row['total_quantity'];
        $value = (float)$row['total_value'];
        
        $index = array_search($date, $dates);
        if ($index !== false) {
            switch ($type) {
                case 'in':
                    $stockIn[$index] = $quantity;
                    $stockInValue[$index] = $value;
                    break;
                case 'out':
                    $stockOut[$index] = $quantity;
                    $stockOutValue[$index] = $value;
                    break;
                case 'adjustment':
                    $adjustments[$index] = $quantity;
                    $adjustmentsValue[$index] = $value;
                    break;
                case 'waste':
                    $waste[$index] = $quantity;
                    $wasteValue[$index] = $value;
                    break;
            }
        }
    }
    
    return [
        'labels' => $dates,
        'stockIn' => $stockIn,
        'stockOut' => $stockOut,
        'adjustments' => $adjustments,
        'waste' => $waste,
        'stockInValue' => $stockInValue,
        'stockOutValue' => $stockOutValue,
        'adjustmentsValue' => $adjustmentsValue,
        'wasteValue' => $wasteValue
    ];
}

function getMovementByCategory($start_date, $end_date) {
    global $conn;
    
    $query = "SELECT 
                COALESCE(c.category_name, 'Uncategorized') as category_name,
                sm.movement_type,
                SUM(sm.quantity) as total_quantity,
                SUM(sm.total_cost) as total_value
              FROM stock_movements sm
              JOIN inventory_items i ON sm.item_id = i.item_id
              LEFT JOIN categories c ON i.category_id = c.category_id
              WHERE sm.movement_date BETWEEN ? AND ?
              AND i.deleted_at IS NULL
              GROUP BY c.category_id, c.category_name, sm.movement_type
              ORDER BY total_quantity DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $categories = [];
    $movementTypes = ['in', 'out', 'adjustment', 'waste'];
    $data = [];
    $valueData = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $category = $row['category_name'];
        $type = $row['movement_type'];
        $quantity = (float)$row['total_quantity'];
        $value = (float)$row['total_value'];
        
        if (!in_array($category, $categories)) {
            $categories[] = $category;
        }
        
        if (!isset($data[$type])) {
            $data[$type] = [];
        }
        if (!isset($valueData[$type])) {
            $valueData[$type] = [];
        }
        
        $data[$type][$category] = $quantity;
        $valueData[$type][$category] = $value;
    }
    
    // Fill missing values with 0
    foreach ($movementTypes as $type) {
        if (!isset($data[$type])) {
            $data[$type] = [];
        }
        if (!isset($valueData[$type])) {
            $valueData[$type] = [];
        }
        foreach ($categories as $category) {
            if (!isset($data[$type][$category])) {
                $data[$type][$category] = 0;
            }
            if (!isset($valueData[$type][$category])) {
                $valueData[$type][$category] = 0;
            }
        }
    }
    
    return [
        'categories' => $categories,
        'data' => $data,
        'valueData' => $valueData
    ];
}

function getMovements($start_date, $end_date) {
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
              WHERE sm.movement_date BETWEEN ? AND ?
              AND i.deleted_at IS NULL
              ORDER BY sm.movement_date DESC
              LIMIT 100";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $movements = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $movements[] = [
            'movement_date' => $row['movement_date'],
            'movement_type' => $row['movement_type'],
            'quantity' => (float)$row['quantity'],
            'unit_cost' => (float)$row['unit_cost'],
            'total_cost' => (float)$row['total_cost'],
            'reason' => $row['reason'],
            'item_name' => $row['item_name'],
            'unit_of_measure' => $row['unit_of_measure'],
            'category_name' => $row['category_name'] ?: 'Uncategorized',
            'user_name' => $row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : 'System'
        ];
    }
    
    return $movements;
}
?>
