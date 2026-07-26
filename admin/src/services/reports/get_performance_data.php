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
    // Test database connection first
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    $response = [
        'performanceMetrics' => getPerformanceMetrics($start_date, $end_date),
        'turnoverTrends' => getTurnoverTrends($start_date, $end_date),
        'performanceByCategory' => getPerformanceByCategory($start_date, $end_date),
        'performance' => getPerformanceSummary($start_date, $end_date)
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Performance service error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
}

function getPerformanceMetrics($start_date, $end_date) {
    global $conn;
    
    // Calculate average turnover rate (simplified)
    $turnoverQuery = "SELECT 
                        COUNT(*) as total_items,
                        SUM(CASE WHEN i.current_stock > 0 THEN 1 ELSE 0 END) as items_with_stock,
                        SUM(CASE WHEN sm.movement_type = 'out' THEN sm.quantity ELSE 0 END) as total_out
                      FROM inventory_items i
                      LEFT JOIN stock_movements sm ON i.item_id = sm.item_id 
                        AND sm.movement_date BETWEEN ? AND ?
                      WHERE i.deleted_at IS NULL AND i.is_active = 1";
    
    $stmt = mysqli_prepare($conn, $turnoverQuery);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    $totalItems = (int)$row['total_items'];
    $itemsWithStock = (int)$row['items_with_stock'];
    $totalOut = (float)$row['total_out'];
    
    // Simple turnover calculation
    $avgTurnoverRate = $itemsWithStock > 0 ? ($totalOut / $itemsWithStock) : 0;
    
    // Calculate stock accuracy (simplified)
    $accuracyQuery = "SELECT 
                        COUNT(*) as total_items,
                        SUM(CASE WHEN i.current_stock > 0 THEN 1 ELSE 0 END) as items_with_stock
                      FROM inventory_items i
                      WHERE i.deleted_at IS NULL AND i.is_active = 1";
    
    $result = mysqli_query($conn, $accuracyQuery);
    $row = mysqli_fetch_assoc($result);
    
    $totalItemsForAccuracy = (int)$row['total_items'];
    $itemsWithStockForAccuracy = (int)$row['items_with_stock'];
    $stockAccuracy = $totalItemsForAccuracy > 0 ? ($itemsWithStockForAccuracy / $totalItemsForAccuracy) * 100 : 0;
    
    // Calculate carrying cost (estimated at 20% of total stock value)
    $carryingCostQuery = "SELECT SUM(current_stock * unit_cost) as total_stock_value 
                         FROM inventory_items 
                         WHERE deleted_at IS NULL AND is_active = 1";
    $result = mysqli_query($conn, $carryingCostQuery);
    $row = mysqli_fetch_assoc($result);
    $totalStockValue = (float)$row['total_stock_value'];
    $carryingCost = $totalStockValue * 0.20; // 20% carrying cost
    
    // Calculate stockout rate
    $stockoutQuery = "SELECT 
                        COUNT(*) as total_items,
                        SUM(CASE WHEN current_stock <= minimum_stock THEN 1 ELSE 0 END) as stockout_items
                      FROM inventory_items 
                      WHERE deleted_at IS NULL AND is_active = 1";
    $result = mysqli_query($conn, $stockoutQuery);
    $row = mysqli_fetch_assoc($result);
    
    $totalItemsForStockout = (int)$row['total_items'];
    $stockoutItems = (int)$row['stockout_items'];
    $stockoutRate = $totalItemsForStockout > 0 ? ($stockoutItems / $totalItemsForStockout) * 100 : 0;
    
    return [
        'avgTurnoverRate' => round($avgTurnoverRate, 2),
        'stockAccuracy' => round($stockAccuracy, 2),
        'carryingCost' => round($carryingCost, 2),
        'stockoutRate' => round($stockoutRate, 2)
    ];
}

function getTurnoverTrends($start_date, $end_date) {
    global $conn;
    
    // Calculate monthly turnover rates
    $query = "SELECT 
                DATE_FORMAT(sm.movement_date, '%Y-%m') as month,
                SUM(CASE WHEN sm.movement_type = 'out' THEN sm.quantity ELSE 0 END) as total_out,
                AVG(i.current_stock) as avg_stock
              FROM stock_movements sm
              JOIN inventory_items i ON sm.item_id = i.item_id
              WHERE sm.movement_date BETWEEN ? AND ?
              AND i.deleted_at IS NULL
              GROUP BY DATE_FORMAT(sm.movement_date, '%Y-%m')
              ORDER BY month";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $months = [];
    $turnoverRates = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $month = $row['month'];
        $totalOut = (float)$row['total_out'];
        $avgStock = (float)$row['avg_stock'];
        
        $months[] = $month;
        $turnoverRate = $avgStock > 0 ? ($totalOut / $avgStock) * 12 : 0; // Annualized
        $turnoverRates[] = $turnoverRate;
    }
    
    return [
        'labels' => $months,
        'turnoverRates' => $turnoverRates
    ];
}

function getPerformanceByCategory($start_date, $end_date) {
    global $conn;
    
    // Simplified query to avoid complex subqueries
    $query = "SELECT 
                COALESCE(c.category_name, 'Uncategorized') as category_name,
                COUNT(i.item_id) as total_items,
                SUM(i.current_stock * i.unit_cost) as total_carrying_cost,
                SUM(CASE WHEN i.current_stock <= i.minimum_stock THEN 1 ELSE 0 END) as stockout_risk,
                SUM(CASE WHEN sm.movement_type = 'out' THEN sm.quantity ELSE 0 END) as total_out
              FROM inventory_items i
              LEFT JOIN categories c ON i.category_id = c.category_id
              LEFT JOIN stock_movements sm ON i.item_id = sm.item_id 
                AND sm.movement_date BETWEEN ? AND ?
              WHERE i.deleted_at IS NULL AND i.is_active = 1
              GROUP BY c.category_id, c.category_name
              ORDER BY total_carrying_cost DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $categories = [];
    $turnoverRates = [];
    $accuracyRates = [];
    $carryingCosts = [];
    $stockoutRisks = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row['category_name'];
        $totalItems = (int)$row['total_items'];
        $totalOut = (float)$row['total_out'];
        $carryingCost = (float)$row['total_carrying_cost'];
        $stockoutRisk = (int)$row['stockout_risk'];
        
        // Simple calculations
        $turnoverRate = $totalItems > 0 ? $totalOut / $totalItems : 0;
        $accuracyRate = $totalItems > 0 ? (($totalItems - $stockoutRisk) / $totalItems) * 100 : 100;
        
        $turnoverRates[] = round($turnoverRate, 2);
        $accuracyRates[] = round($accuracyRate, 2);
        $carryingCosts[] = round($carryingCost, 2);
        $stockoutRisks[] = round($stockoutRisk, 2);
    }
    
    return [
        'categories' => $categories,
        'turnoverRates' => $turnoverRates,
        'accuracyRates' => $accuracyRates,
        'carryingCosts' => $carryingCosts,
        'stockoutRisks' => $stockoutRisks
    ];
}

function getPerformanceSummary($start_date, $end_date) {
    global $conn;
    
    // Simplified query
    $query = "SELECT 
                COALESCE(c.category_name, 'Uncategorized') as category_name,
                COUNT(i.item_id) as total_items,
                SUM(i.current_stock * i.unit_cost) as carrying_cost,
                SUM(CASE WHEN i.current_stock <= i.minimum_stock THEN 1 ELSE 0 END) as stockout_risk,
                SUM(CASE WHEN sm.movement_type = 'out' THEN sm.quantity ELSE 0 END) as total_out
              FROM inventory_items i
              LEFT JOIN categories c ON i.category_id = c.category_id
              LEFT JOIN stock_movements sm ON i.item_id = sm.item_id 
                AND sm.movement_date BETWEEN ? AND ?
              WHERE i.deleted_at IS NULL AND i.is_active = 1
              GROUP BY c.category_id, c.category_name
              ORDER BY carrying_cost DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $performance = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $totalItems = (int)$row['total_items'];
        $carryingCost = (float)$row['carrying_cost'];
        $stockoutRisk = (int)$row['stockout_risk'];
        $totalOut = (float)$row['total_out'];
        
        // Simple calculations
        $turnoverRate = $totalItems > 0 ? $totalOut / $totalItems : 0;
        $stockAccuracy = $totalItems > 0 ? (($totalItems - $stockoutRisk) / $totalItems) * 100 : 100;
        $stockoutRate = $totalItems > 0 ? ($stockoutRisk / $totalItems) * 100 : 0;
        $performanceScore = ($turnoverRate * 0.3 + $stockAccuracy * 0.3 + (100 - $stockoutRate) * 0.4);
        
        $performance[] = [
            'category_name' => $row['category_name'],
            'total_items' => $totalItems,
            'turnover_rate' => round($turnoverRate, 2),
            'stock_accuracy' => round($stockAccuracy, 2),
            'carrying_cost' => round($carryingCost, 2),
            'stockout_rate' => round($stockoutRate, 2),
            'performance_score' => round($performanceScore, 2)
        ];
    }
    
    return $performance;
}
?>
