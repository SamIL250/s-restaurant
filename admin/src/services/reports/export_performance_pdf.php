<?php
require_once '../../../config/config.php';
require_once '../utils/report_helpers.php';
require_once '../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get input from either JSON or POST data
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_POST;
}

$start_date = $input['start'] ?? date('Y-m-01');
$end_date = $input['end'] ?? date('Y-m-t');

// Debug: Log the received dates
error_log("Performance export dates - Start: " . $start_date . ", End: " . $end_date);

try {
    // Get performance data
    $data = getPerformanceReportData($start_date, $end_date);
    
    // Create PDF
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    
    $html = generatePerformancePDFHTML($data, $start_date, $end_date);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Generate filename
    $filename = 'performance_report_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $dompdf->output();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getPerformanceReportData($start_date, $end_date) {
    global $conn;
    
    $data = [
        'performanceMetrics' => [],
        'performanceByCategory' => [],
        'performance' => []
    ];
    
    // Performance Metrics
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
    
    // Calculate stock accuracy
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
    
    $data['performanceMetrics'] = [
        'avgTurnoverRate' => round($avgTurnoverRate, 2),
        'stockAccuracy' => round($stockAccuracy, 2),
        'carryingCost' => round($carryingCost, 2),
        'stockoutRate' => round($stockoutRate, 2)
    ];
    
    // Performance by Category
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
    
    $performanceByCategory = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $category = $row['category_name'];
        $totalItems = (int)$row['total_items'];
        $totalOut = (float)$row['total_out'];
        $carryingCost = (float)$row['total_carrying_cost'];
        $stockoutRisk = (int)$row['stockout_risk'];
        
        // Simple calculations
        $turnoverRate = $totalItems > 0 ? $totalOut / $totalItems : 0;
        $accuracyRate = $totalItems > 0 ? (($totalItems - $stockoutRisk) / $totalItems) * 100 : 100;
        $stockoutRate = $totalItems > 0 ? ($stockoutRisk / $totalItems) * 100 : 0;
        
        $performanceByCategory[] = [
            'category_name' => $category,
            'total_items' => $totalItems,
            'turnover_rate' => round($turnoverRate, 2),
            'stock_accuracy' => round($accuracyRate, 2),
            'carrying_cost' => round($carryingCost, 2),
            'stockout_rate' => round($stockoutRate, 2)
        ];
    }
    $data['performanceByCategory'] = $performanceByCategory;
    
    // Performance Summary
    $performance = [];
    foreach ($performanceByCategory as $category) {
        $performanceScore = ($category['turnover_rate'] * 0.3 + $category['stock_accuracy'] * 0.3 + (100 - $category['stockout_rate']) * 0.4);
        $performance[] = [
            'category_name' => $category['category_name'],
            'total_items' => $category['total_items'],
            'turnover_rate' => $category['turnover_rate'],
            'stock_accuracy' => $category['stock_accuracy'],
            'carrying_cost' => $category['carrying_cost'],
            'stockout_rate' => $category['stockout_rate'],
            'performance_score' => round($performanceScore, 2)
        ];
    }
    $data['performance'] = $performance;
    
    return $data;
}

function generatePerformancePDFHTML($data, $start_date, $end_date) {
    $currency = 'RWF';
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Performance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .performance-score {
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
        }
        .score-excellent {
            background-color: #28a745;
            color: white;
        }
        .score-good {
            background-color: #17a2b8;
            color: white;
        }
        .score-average {
            background-color: #ffc107;
            color: #333;
        }
        .score-poor {
            background-color: #dc3545;
            color: white;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .metrics-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        .metric-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .metric-value {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        .metric-label {
            color: #666;
            font-size: 11px;
            margin-top: 5px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>';

    // Header
    $html .= '
    <div class="header">
        <h1>Inventory Performance Report</h1>
        <p>Period: ' . date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date)) . '</p>
        <p>Generated on: ' . date('F j, Y H:i:s') . '</p>
    </div>';

    // Performance Metrics
    $metrics = $data['performanceMetrics'];
    $html .= '
    <div class="section">
        <h2>Key Performance Metrics</h2>
        <div class="metrics-grid">
            <div class="metrics-row">
                <div class="metric-card">
                    <div class="metric-value">' . number_format($metrics['avgTurnoverRate'], 2) . '</div>
                    <div class="metric-label">Average Turnover Rate</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">' . $metrics['stockAccuracy'] . '%</div>
                    <div class="metric-label">Stock Accuracy</div>
                </div>
            </div>
            <div class="metrics-row">
                <div class="metric-card">
                    <div class="metric-value">' . $currency . ' ' . number_format($metrics['carryingCost'], 2) . '</div>
                    <div class="metric-label">Annual Carrying Cost (20%)</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">' . $metrics['stockoutRate'] . '%</div>
                    <div class="metric-label">Stockout Rate</div>
                </div>
            </div>
        </div>
    </div>';

    // Performance by Category
    if (!empty($data['performanceByCategory'])) {
        $html .= '
        <div class="section">
            <h2>Performance by Category</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-right">Total Items</th>
                        <th class="text-right">Turnover Rate</th>
                        <th class="text-right">Stock Accuracy</th>
                        <th class="text-right">Carrying Cost</th>
                        <th class="text-right">Stockout Rate</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($data['performanceByCategory'] as $category) {
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($category['category_name']) . '</td>
                        <td class="text-right">' . number_format($category['total_items']) . '</td>
                        <td class="text-right">' . number_format($category['turnover_rate'], 2) . '</td>
                        <td class="text-right">' . number_format($category['stock_accuracy'], 2) . '%</td>
                        <td class="text-right">' . $currency . ' ' . number_format($category['carrying_cost'], 2) . '</td>
                        <td class="text-right">' . number_format($category['stockout_rate'], 2) . '%</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }

    // Performance Summary with Scores
    if (!empty($data['performance'])) {
        $html .= '
        <div class="section">
            <h2>Performance Summary</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-right">Total Items</th>
                        <th class="text-right">Turnover Rate</th>
                        <th class="text-right">Stock Accuracy</th>
                        <th class="text-right">Carrying Cost</th>
                        <th class="text-right">Stockout Rate</th>
                        <th class="text-center">Performance Score</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($data['performance'] as $item) {
            $scoreClass = 'score-poor';
            if ($item['performance_score'] >= 80) {
                $scoreClass = 'score-excellent';
            } elseif ($item['performance_score'] >= 60) {
                $scoreClass = 'score-good';
            } elseif ($item['performance_score'] >= 40) {
                $scoreClass = 'score-average';
            }
            
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($item['category_name']) . '</td>
                        <td class="text-right">' . number_format($item['total_items']) . '</td>
                        <td class="text-right">' . number_format($item['turnover_rate'], 2) . '</td>
                        <td class="text-right">' . number_format($item['stock_accuracy'], 2) . '%</td>
                        <td class="text-right">' . $currency . ' ' . number_format($item['carrying_cost'], 2) . '</td>
                        <td class="text-right">' . number_format($item['stockout_rate'], 2) . '%</td>
                        <td class="text-center"><span class="performance-score ' . $scoreClass . '">' . number_format($item['performance_score'], 1) . '</span></td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }

    // Footer
    $html .= '
    <div class="footer">
        <p>This report was generated automatically by the Restaurant Stock Management System</p>
        <p>Performance scores are calculated based on turnover rate (30%), stock accuracy (30%), and stockout rate (40%)</p>
        <p>Page 1 of 1</p>
    </div>';

    $html .= '
</body>
</html>';

    return $html;
}
?>
