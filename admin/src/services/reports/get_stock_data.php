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
        'stockLevels' => getStockLevels(),
        // ABC can be heavy; make it optional via input flag
        'abcAnalysis' => !empty($input['include_abc']) ? getABCAnalysis() : null,
        'turnoverRates' => getTurnoverRates($start_date, $end_date),
        'stockItems' => getStockItems()
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getStockLevels() {
    global $conn;
    $query = "SELECT 
                i.item_name,
                i.current_stock,
                i.minimum_stock,
                i.maximum_stock,
                i.unit_of_measure,
                c.category_name,
                CASE 
                    WHEN i.current_stock <= i.minimum_stock THEN 'Low'
                    WHEN i.current_stock >= i.maximum_stock * 0.8 THEN 'High'
                    ELSE 'Normal'
                END as stock_status
              FROM inventory_items i
              LEFT JOIN categories c ON i.category_id = c.category_id
              WHERE i.deleted_at IS NULL AND i.is_active = 1
              ORDER BY i.current_stock DESC
              LIMIT 20";
    
    $result = mysqli_query($conn, $query);
    if (!$result) return ['items' => [], 'labels' => [], 'currentStock' => [], 'minimumStock' => []];
    $items = [];
    $labels = [];
    $currentStock = [];
    $minimumStock = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
        $labels[] = $row['item_name'];
        $currentStock[] = (float)$row['current_stock'];
        $minimumStock[] = (float)$row['minimum_stock'];
    }
    
    return [
        'items' => $items,
        'labels' => $labels,
        'currentStock' => $currentStock,
        'minimumStock' => $minimumStock
    ];
}

function getABCAnalysis() {
    global $conn;
    
    // Calculate total value and get items with their values
    $query = "SELECT 
                i.item_name,
                i.current_stock * i.unit_cost as total_value,
                c.category_name
              FROM inventory_items i
              LEFT JOIN categories c ON i.category_id = c.category_id
              WHERE i.deleted_at IS NULL AND i.is_active = 1
              ORDER BY total_value DESC";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return [
            'A' => ['labels' => [], 'data' => [], 'color' => '#FF6384'],
            'B' => ['labels' => [], 'data' => [], 'color' => '#36A2EB'],
            'C' => ['labels' => [], 'data' => [], 'color' => '#FFCE56']
        ];
    }
    $items = [];
    $totalValue = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
        $totalValue += (float)$row['total_value'];
    }
    
    // Calculate cumulative percentage and classify
    $cumulativeValue = 0;
    $abcData = [
        'A' => ['labels' => [], 'data' => [], 'color' => '#FF6384'],
        'B' => ['labels' => [], 'data' => [], 'color' => '#36A2EB'],
        'C' => ['labels' => [], 'data' => [], 'color' => '#FFCE56']
    ];
    
    foreach ($items as $item) {
        $cumulativeValue += (float)$item['total_value'];
        $cumulativePercentage = ($cumulativeValue / $totalValue) * 100;
        
        if ($cumulativePercentage <= 80) {
            $abcData['A']['labels'][] = $item['item_name'];
            $abcData['A']['data'][] = (float)$item['total_value'];
        } elseif ($cumulativePercentage <= 95) {
            $abcData['B']['labels'][] = $item['item_name'];
            $abcData['B']['data'][] = (float)$item['total_value'];
        } else {
            $abcData['C']['labels'][] = $item['item_name'];
            $abcData['C']['data'][] = (float)$item['total_value'];
        }
    }
    
    return $abcData;
}

function getTurnoverRates($start_date, $end_date) {
    global $conn;
    
    // Calculate turnover rate for each item
    $query = "SELECT 
                i.item_name,
                i.current_stock,
                i.unit_cost,
                COALESCE(SUM(CASE WHEN sm.movement_type = 'out' THEN sm.quantity ELSE 0 END), 0) as total_out,
                COALESCE(SUM(CASE WHEN sm.movement_type = 'in' THEN sm.quantity ELSE 0 END), 0) as total_in,
                c.category_name
              FROM inventory_items i
              LEFT JOIN categories c ON i.category_id = c.category_id
              LEFT JOIN stock_movements sm ON i.item_id = sm.item_id 
                AND sm.movement_date BETWEEN ? AND ?
              WHERE i.deleted_at IS NULL AND i.is_active = 1
              GROUP BY i.item_id, i.item_name, i.current_stock, i.unit_cost, c.category_name
              ORDER BY total_out DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) return ['items' => [], 'labels' => [], 'turnoverRates' => []];
    
    $items = [];
    $labels = [];
    $turnoverRates = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $currentStock = (float)$row['current_stock'];
        $totalOut = (float)$row['total_out'];
        
        // Calculate turnover rate (annualized)
        $daysInPeriod = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
        $annualizedOut = ($totalOut / $daysInPeriod) * 365;
        $turnoverRate = $currentStock > 0 ? $annualizedOut / $currentStock : 0;
        
        $items[] = [
            'item_name' => $row['item_name'],
            'category_name' => $row['category_name'],
            'current_stock' => $currentStock,
            'total_out' => $totalOut,
            'turnover_rate' => $turnoverRate
        ];
        
        $labels[] = $row['item_name'];
        $turnoverRates[] = $turnoverRate;
    }
    
    return [
        'items' => $items,
        'labels' => $labels,
        'turnoverRates' => $turnoverRates
    ];
}

function getStockItems() {
    global $conn;
    $query = "SELECT 
                i.item_name,
                c.category_name,
                i.current_stock,
                i.minimum_stock,
                i.unit_cost,
                i.current_stock * i.unit_cost as total_value,
                i.unit_of_measure,
                i.last_restocked,
                i.expiry_date,
                CASE 
                    WHEN i.current_stock <= i.minimum_stock THEN 'Low Stock'
                    WHEN i.current_stock >= i.maximum_stock * 0.8 THEN 'High Stock'
                    WHEN i.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Expiring Soon'
                    ELSE 'Normal'
                END as status,
                i.updated_at
              FROM inventory_items i
              LEFT JOIN categories c ON i.category_id = c.category_id
              WHERE i.deleted_at IS NULL AND i.is_active = 1
              ORDER BY i.current_stock ASC";
    
    $result = mysqli_query($conn, $query);
    if (!$result) return [];
    $items = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = [
            'item_name' => $row['item_name'],
            'category_name' => $row['category_name'] ?: 'Uncategorized',
            'current_stock' => (float)$row['current_stock'],
            'minimum_stock' => (float)$row['minimum_stock'],
            'unit_cost' => (float)$row['unit_cost'],
            'total_value' => (float)$row['total_value'],
            'unit_of_measure' => $row['unit_of_measure'],
            'last_restocked' => $row['last_restocked'],
            'expiry_date' => $row['expiry_date'],
            'status' => $row['status'],
            'updated_at' => $row['updated_at']
        ];
    }
    
    return $items;
}
?>
