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
error_log("Loss export dates - Start: " . $start_date . ", End: " . $end_date);

try {
    // Get loss data
    $data = getLossReportData($start_date, $end_date);
    
    // Create PDF
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    
    $html = generateLossPDFHTML($data, $start_date, $end_date);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Generate filename
    $filename = 'loss_analysis_report_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $dompdf->output();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getLossReportData($start_date, $end_date) {
    global $conn;
    
    $data = [
        'lossSummary' => [],
        'lossByCategory' => [],
        'losses' => []
    ];
    
    // Loss Summary
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
    
    $data['lossSummary'] = [
        'totalLossValue' => $totalValueLost,
        'totalQuantityLost' => $totalQuantityLost,
        'totalIncidents' => $totalIncidents,
        'lossPercentage' => round($lossPercentage, 2),
        'avgLossPerItem' => round($avgLossPerItem, 2)
    ];
    
    // Loss by Category
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
    
    $categoryData = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $category = $row['category_name'];
        $type = $row['movement_type'];
        $quantity = (float)$row['total_quantity'];
        $value = (float)$row['total_value'];
        
        if (!isset($categoryData[$category])) {
            $categoryData[$category] = [
                'waste' => ['quantity' => 0, 'value' => 0, 'incidents' => 0],
                'adjustment' => ['quantity' => 0, 'value' => 0, 'incidents' => 0]
            ];
        }
        
        $categoryData[$category][$type] = [
            'quantity' => $quantity,
            'value' => $value,
            'incidents' => (int)$row['incident_count']
        ];
    }
    
    $data['lossByCategory'] = $categoryData;
    
    // Detailed Losses
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
            'quantity' => (float)$row['quantity'],
            'unit_cost' => (float)$row['unit_cost'],
            'value_lost' => (float)$row['total_cost'],
            'reason' => $row['reason'],
            'item_name' => $row['item_name'],
            'unit_of_measure' => $row['unit_of_measure'],
            'category_name' => $row['category_name'] ?: 'Uncategorized',
            'user_name' => $row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : 'System'
        ];
    }
    $data['losses'] = $losses;
    
    return $data;
}

function generateLossPDFHTML($data, $start_date, $end_date) {
    $currency = 'RWF';
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Loss Analysis Report</title>
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
        .loss-type {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .loss-waste {
            background-color: #f8d7da;
            color: #721c24;
        }
        .loss-adjustment {
            background-color: #fff3cd;
            color: #856404;
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
            color: #dc3545;
        }
        .summary-label {
            color: #666;
            font-size: 11px;
            margin-top: 5px;
        }
        .category-section {
            margin-bottom: 25px;
        }
        .category-section h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .alert-box {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-box h3 {
            color: #721c24;
            margin: 0 0 10px 0;
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
        <h1>Loss Analysis Report</h1>
        <p>Period: ' . date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date)) . '</p>
        <p>Generated on: ' . date('F j, Y H:i:s') . '</p>
    </div>';

    // Loss Summary
    $summary = $data['lossSummary'];
    $html .= '
    <div class="section">
        <h2>Loss Summary</h2>';
    
    if ($summary['totalLossValue'] > 0) {
        $html .= '
        <div class="alert-box">
            <h3>⚠️ Loss Alert</h3>
            <p>Total loss of ' . $currency . ' ' . number_format($summary['totalLossValue'], 2) . ' represents ' . $summary['lossPercentage'] . '% of total stock value</p>
        </div>';
    }
    
    $html .= '
        <div class="metrics-grid">
            <div class="metrics-row">
                <div class="summary-card">
                    <div class="summary-value">' . $currency . ' ' . number_format($summary['totalLossValue'], 2) . '</div>
                    <div class="summary-label">Total Loss Value</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value">' . number_format($summary['totalQuantityLost'], 2) . '</div>
                    <div class="summary-label">Total Quantity Lost</div>
                </div>
            </div>
            <div class="metrics-row">
                <div class="summary-card">
                    <div class="summary-value">' . $summary['totalIncidents'] . '</div>
                    <div class="summary-label">Total Incidents</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value">' . $summary['lossPercentage'] . '%</div>
                    <div class="summary-label">Loss Percentage</div>
                </div>
            </div>
            <div class="metrics-row">
                <div class="summary-card">
                    <div class="summary-value">' . $currency . ' ' . number_format($summary['avgLossPerItem'], 2) . '</div>
                    <div class="summary-label">Average Loss per Item</div>
                </div>
            </div>
        </div>
    </div>';

    // Loss by Category
    if (!empty($data['lossByCategory'])) {
        $html .= '
        <div class="section">
            <h2>Loss by Category</h2>';
        
        foreach ($data['lossByCategory'] as $category => $losses) {
            $totalCategoryLoss = $losses['waste']['value'] + $losses['adjustment']['value'];
            if ($totalCategoryLoss > 0) {
                $html .= '
            <div class="category-section">
                <h3>' . htmlspecialchars($category) . ' (Total: ' . $currency . ' ' . number_format($totalCategoryLoss, 2) . ')</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Loss Type</th>
                            <th class="text-right">Quantity</th>
                            <th class="text-right">Value</th>
                            <th class="text-right">Incidents</th>
                        </tr>
                    </thead>
                    <tbody>';
                
                foreach ($losses as $type => $data) {
                    if ($data['value'] > 0) {
                        $typeClass = 'loss-' . $type;
                        $html .= '
                        <tr>
                            <td><span class="loss-type ' . $typeClass . '">' . ucfirst($type) . '</span></td>
                            <td class="text-right">' . number_format($data['quantity'], 2) . '</td>
                            <td class="text-right">' . $currency . ' ' . number_format($data['value'], 2) . '</td>
                            <td class="text-right">' . $data['incidents'] . '</td>
                        </tr>';
                    }
                }
                
                $html .= '
                    </tbody>
                </table>
            </div>';
            }
        }
        
        $html .= '
        </div>';
    }

    // Detailed Losses
    if (!empty($data['losses'])) {
        $html .= '
        <div class="section">
            <h2>Detailed Loss Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Loss Type</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Unit Cost</th>
                        <th class="text-right">Value Lost</th>
                        <th>Reason</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($data['losses'] as $loss) {
            $typeClass = 'loss-' . $loss['loss_type'];
            $html .= '
                    <tr>
                        <td>' . date('M j, Y H:i', strtotime($loss['movement_date'])) . '</td>
                        <td>' . htmlspecialchars($loss['item_name']) . '</td>
                        <td>' . htmlspecialchars($loss['category_name']) . '</td>
                        <td><span class="loss-type ' . $typeClass . '">' . ucfirst($loss['loss_type']) . '</span></td>
                        <td class="text-right">' . number_format($loss['quantity'], 2) . ' ' . htmlspecialchars($loss['unit_of_measure']) . '</td>
                        <td class="text-right">' . $currency . ' ' . number_format($loss['unit_cost'], 2) . '</td>
                        <td class="text-right">' . $currency . ' ' . number_format($loss['value_lost'], 2) . '</td>
                        <td>' . htmlspecialchars($loss['reason'] ?: 'N/A') . '</td>
                        <td>' . htmlspecialchars($loss['user_name']) . '</td>
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
