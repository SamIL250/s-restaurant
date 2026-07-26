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
error_log("Stock export dates - Start: " . $start_date . ", End: " . $end_date);

try {
    // Get stock analysis data
    $data = getStockAnalysisData($start_date, $end_date);
    
    // Create PDF
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    
    $html = generateStockAnalysisPDFHTML($data, $start_date, $end_date);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Generate filename
    $filename = 'stock_analysis_report_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $dompdf->output();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getStockAnalysisData($start_date, $end_date) {
    global $conn;
    
    $data = [
        'stockLevels' => [],
        'abcAnalysis' => [],
        'turnoverRates' => [],
        'stockItems' => []
    ];
    
    // Stock Levels
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
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data['stockLevels'][] = [
                'item_name' => $row['item_name'],
                'current_stock' => (float)$row['current_stock'],
                'minimum_stock' => (float)$row['minimum_stock'],
                'maximum_stock' => (float)$row['maximum_stock'],
                'unit_of_measure' => $row['unit_of_measure'],
                'category_name' => $row['category_name'] ?: 'Uncategorized',
                'stock_status' => $row['stock_status']
            ];
        }
    }
    
    // ABC Analysis
    $query = "SELECT 
                i.item_name,
                i.current_stock * i.unit_cost as total_value,
                c.category_name
              FROM inventory_items i
              LEFT JOIN categories c ON i.category_id = c.category_id
              WHERE i.deleted_at IS NULL AND i.is_active = 1
              ORDER BY total_value DESC";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        $items = [];
        $totalValue = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
            $totalValue += (float)$row['total_value'];
        }
        
        // Calculate cumulative percentage and classify
        $cumulativeValue = 0;
        foreach ($items as $item) {
            $cumulativeValue += (float)$item['total_value'];
            $cumulativePercentage = ($cumulativeValue / $totalValue) * 100;
            
            if ($cumulativePercentage <= 80) {
                $class = 'A';
            } elseif ($cumulativePercentage <= 95) {
                $class = 'B';
            } else {
                $class = 'C';
            }
            
            $data['abcAnalysis'][] = [
                'item_name' => $item['item_name'],
                'category_name' => $item['category_name'] ?: 'Uncategorized',
                'total_value' => (float)$item['total_value'],
                'abc_class' => $class,
                'cumulative_percentage' => round($cumulativePercentage, 1)
            ];
        }
    }
    
    // Turnover Rates
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
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $currentStock = (float)$row['current_stock'];
            $totalOut = (float)$row['total_out'];
            
            // Calculate turnover rate (annualized)
            $daysInPeriod = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
            $annualizedOut = ($totalOut / $daysInPeriod) * 365;
            $turnoverRate = $currentStock > 0 ? $annualizedOut / $currentStock : 0;
            
            $data['turnoverRates'][] = [
                'item_name' => $row['item_name'],
                'category_name' => $row['category_name'] ?: 'Uncategorized',
                'current_stock' => $currentStock,
                'total_out' => $totalOut,
                'turnover_rate' => round($turnoverRate, 2)
            ];
        }
    }
    
    // Detailed Stock Items
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
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data['stockItems'][] = [
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
    }
    
    return $data;
}

function generateStockAnalysisPDFHTML($data, $start_date, $end_date) {
    $currency = 'RWF';
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock Analysis Report</title>
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
        .status {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-low {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-normal {
            background-color: #d4edda;
            color: #155724;
        }
        .status-high {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-expiring {
            background-color: #fff3cd;
            color: #856404;
        }
        .abc-class {
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .class-a {
            background-color: #ff6b6b;
            color: white;
        }
        .class-b {
            background-color: #ffd93d;
            color: #333;
        }
        .class-c {
            background-color: #6bcf7f;
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
        .summary-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        .summary-label {
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
        <h1>Stock Analysis Report</h1>
        <p>Period: ' . date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date)) . '</p>
        <p>Generated on: ' . date('F j, Y H:i:s') . '</p>
    </div>';

    // Summary Cards
    $totalItems = count($data['stockItems']);
    $lowStockCount = count(array_filter($data['stockItems'], function($item) { return $item['status'] === 'Low Stock'; }));
    $totalValue = array_sum(array_column($data['stockItems'], 'total_value'));
    
    $html .= '
    <div class="section">
        <div class="metrics-grid">
            <div class="metrics-row">
                <div class="summary-card">
                    <div class="summary-value">' . number_format($totalItems) . '</div>
                    <div class="summary-label">Total Items</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value">' . number_format($lowStockCount) . '</div>
                    <div class="summary-label">Low Stock Items</div>
                </div>
            </div>
            <div class="metrics-row">
                <div class="summary-card">
                    <div class="summary-value">' . $currency . ' ' . number_format($totalValue, 2) . '</div>
                    <div class="summary-label">Total Stock Value</div>
                </div>
            </div>
        </div>
    </div>';

    // Stock Levels
    if (!empty($data['stockLevels'])) {
        $html .= '
        <div class="section">
            <h2>Current Stock Levels</h2>
            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th class="text-right">Current Stock</th>
                        <th class="text-right">Min Stock</th>
                        <th class="text-right">Max Stock</th>
                        <th>Unit</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($data['stockLevels'] as $item) {
            $statusClass = 'status-normal';
            if ($item['stock_status'] === 'Low') $statusClass = 'status-low';
            elseif ($item['stock_status'] === 'High') $statusClass = 'status-high';
            
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($item['item_name']) . '</td>
                        <td>' . htmlspecialchars($item['category_name']) . '</td>
                        <td class="text-right">' . number_format($item['current_stock'], 2) . '</td>
                        <td class="text-right">' . number_format($item['minimum_stock'], 2) . '</td>
                        <td class="text-right">' . number_format($item['maximum_stock'], 2) . '</td>
                        <td>' . htmlspecialchars($item['unit_of_measure']) . '</td>
                        <td class="text-center"><span class="status ' . $statusClass . '">' . $item['stock_status'] . '</span></td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }

    // ABC Analysis
    if (!empty($data['abcAnalysis'])) {
        $html .= '
        <div class="section">
            <h2>ABC Analysis</h2>
            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th class="text-right">Total Value</th>
                        <th class="text-center">ABC Class</th>
                        <th class="text-right">Cumulative %</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($data['abcAnalysis'] as $item) {
            $classClass = 'class-' . strtolower($item['abc_class']);
            
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($item['item_name']) . '</td>
                        <td>' . htmlspecialchars($item['category_name']) . '</td>
                        <td class="text-right">' . $currency . ' ' . number_format($item['total_value'], 2) . '</td>
                        <td class="text-center"><span class="abc-class ' . $classClass . '">' . $item['abc_class'] . '</span></td>
                        <td class="text-right">' . $item['cumulative_percentage'] . '%</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }

    // Turnover Rates
    if (!empty($data['turnoverRates'])) {
        $html .= '
        <div class="section">
            <h2>Stock Turnover Rates</h2>
            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th class="text-right">Current Stock</th>
                        <th class="text-right">Total Out</th>
                        <th class="text-right">Turnover Rate</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($data['turnoverRates'] as $item) {
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($item['item_name']) . '</td>
                        <td>' . htmlspecialchars($item['category_name']) . '</td>
                        <td class="text-right">' . number_format($item['current_stock'], 2) . '</td>
                        <td class="text-right">' . number_format($item['total_out'], 2) . '</td>
                        <td class="text-right">' . number_format($item['turnover_rate'], 2) . '</td>
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
        <p>Page 1 of 1</p>
    </div>';

    $html .= '
</body>
</html>';

    return $html;
}
?>
