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
        'totalItems' => getTotalItems(),
        'lowStockItems' => getLowStockItems(),
        'totalValue' => getTotalStockValue(),
        'expiringItems' => getExpiringItems(),
        'stockValueByCategory' => getStockValueByCategory(),
        'movementTrends' => getMovementTrends($start_date, $end_date),
        'recentMovements' => getRecentMovements()
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getTotalItems() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM inventory_items WHERE deleted_at IS NULL AND is_active = 1";
    $result = mysqli_query($conn, $query);
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result) ?: ['total' => 0];
    return (int)($row['total'] ?? 0);
}

function getLowStockItems() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM inventory_items 
              WHERE current_stock <= minimum_stock 
              AND deleted_at IS NULL 
              AND is_active = 1";
    $result = mysqli_query($conn, $query);
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result) ?: ['total' => 0];
    return (int)($row['total'] ?? 0);
}

function getTotalStockValue() {
    global $conn;
    $query = "SELECT SUM(current_stock * unit_cost) as total_value 
              FROM inventory_items 
              WHERE deleted_at IS NULL AND is_active = 1";
    $result = mysqli_query($conn, $query);
    if (!$result) return 0.0;
    $row = mysqli_fetch_assoc($result) ?: ['total_value' => 0];
    return (float)($row['total_value'] ?? 0);
}

function getExpiringItems() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM inventory_items 
              WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
              AND deleted_at IS NULL 
              AND is_active = 1";
    $result = mysqli_query($conn, $query);
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result) ?: ['total' => 0];
    return (int)($row['total'] ?? 0);
}

function getStockValueByCategory() {
    global $conn;
    $query = "SELECT 
                c.category_name,
                SUM(i.current_stock * i.unit_cost) as total_value
              FROM inventory_items i
              LEFT JOIN categories c ON i.category_id = c.category_id
              WHERE i.deleted_at IS NULL AND i.is_active = 1
              GROUP BY c.category_id, c.category_name
              ORDER BY total_value DESC";
    
    $result = mysqli_query($conn, $query);
    if (!$result) return ['labels' => [], 'data' => []];
    $labels = [];
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['category_name'] ?: 'Uncategorized';
        $data[] = (float)$row['total_value'];
    }
    
    return [
        'labels' => $labels,
        'data' => $data
    ];
}

function getMovementTrends($start_date, $end_date) {
    global $conn;
    
    // Get daily movement data
    $query = "SELECT 
                DATE(movement_date) as date,
                movement_type,
                SUM(quantity) as total_quantity
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
    if (!$result) return ['labels' => [], 'stockIn' => [], 'stockOut' => []];
    
    $dates = [];
    $stockIn = [];
    $stockOut = [];
    
    // Initialize arrays with all dates in range
    $current = new DateTime($start_date);
    $end = new DateTime($end_date);
    while ($current <= $end) {
        $dates[] = $current->format('Y-m-d');
        $stockIn[] = 0;
        $stockOut[] = 0;
        $current->add(new DateInterval('P1D'));
    }
    
    // Fill in actual data
    while ($row = mysqli_fetch_assoc($result)) {
        $date = $row['date'];
        $type = $row['movement_type'];
        $quantity = (float)$row['total_quantity'];
        
        $index = array_search($date, $dates);
        if ($index !== false) {
            if ($type === 'in') {
                $stockIn[$index] = $quantity;
            } elseif ($type === 'out') {
                $stockOut[$index] = $quantity;
            }
        }
    }
    
    return [
        'labels' => $dates,
        'stockIn' => $stockIn,
        'stockOut' => $stockOut
    ];
}

function getRecentMovements() {
    global $conn;
    $query = "SELECT 
                sm.movement_date,
                sm.movement_type,
                sm.quantity,
                sm.total_cost,
                sm.reason,
                i.item_name,
                u.first_name,
                u.last_name
              FROM stock_movements sm
              JOIN inventory_items i ON sm.item_id = i.item_id
              LEFT JOIN users u ON sm.user_id = u.user_id
              WHERE i.deleted_at IS NULL
              ORDER BY sm.movement_date DESC
              LIMIT 10";
    
    $result = mysqli_query($conn, $query);
    if (!$result) return [];
    $movements = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $movements[] = [
            'movement_date' => $row['movement_date'],
            'movement_type' => $row['movement_type'],
            'quantity' => (float)$row['quantity'],
            'total_cost' => (float)$row['total_cost'],
            'reason' => $row['reason'],
            'item_name' => $row['item_name'],
            'user_name' => $row['first_name'] . ' ' . $row['last_name']
        ];
    }
    
    return $movements;
}
?>
