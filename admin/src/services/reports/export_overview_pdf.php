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
error_log("Overview export dates - Start: " . $start_date . ", End: " . $end_date);

try {
    // Get overview data
    $data = getOverviewReportData($start_date, $end_date);
    
    // Create PDF
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    
    $html = generateOverviewPDFHTML($data, $start_date, $end_date);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Generate filename
    $filename = 'overview_report_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $dompdf->output();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getOverviewReportData($start_date, $end_date) {
    global $conn;
    
    $data = [
        'totalItems' => 0,
        'lowStockItems' => 0,
        'totalValue' => 0,
        'expiringItems' => 0,
        'stockValueByCategory' => [],
        'movementTrends' => [],
        'recentMovements' => []
    ];
    
    // Total Items
    $query = "SELECT COUNT(*) as total FROM inventory_items WHERE deleted_at IS NULL AND is_active = 1";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $data['totalItems'] = (int)$row['total'];
    }
    
    // Low Stock Items
    $query = "SELECT COUNT(*) as total FROM inventory_items 
              WHERE current_stock <= minimum_stock 
              AND deleted_at IS NULL 
              AND is_active = 1";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $data['lowStockItems'] = (int)$row['total'];
    }
    
    // Total Stock Value
    $query = "SELECT SUM(current_stock * unit_cost) as total_value 
              FROM inventory_items 
              WHERE deleted_at IS NULL AND is_active = 1";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $data['totalValue'] = (float)$row['total_value'];
    }
    
    // Expiring Items
    $query = "SELECT COUNT(*) as total FROM inventory_items 
              WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
              AND deleted_at IS NULL 
              AND is_active = 1";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $data['expiringItems'] = (int)$row['total'];
    }
    
    // Stock Value by Category
    $query = "SELECT 
                c.category_name,
                SUM(i.current_stock * i.unit_cost) as total_value
              FROM inventory_items i
              LEFT JOIN categories c ON i.category_id = c.category_id
              WHERE i.deleted_at IS NULL AND i.is_active = 1
              GROUP BY c.category_id, c.category_name
              ORDER BY total_value DESC";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data['stockValueByCategory'][] = [
                'category' => $row['category_name'] ?: 'Uncategorized',
                'value' => (float)$row['total_value']
            ];
        }
    }
    
    // Recent Movements
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
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data['recentMovements'][] = [
                'date' => $row['movement_date'],
                'type' => $row['movement_type'],
                'quantity' => (float)$row['quantity'],
                'cost' => (float)$row['total_cost'],
                'reason' => $row['reason'],
                'item' => $row['item_name'],
                'user' => $row['first_name'] . ' ' . $row['last_name']
            ];
        }
    }
    
    return $data;
}

function generateOverviewPDFHTML($data, $start_date, $end_date) {
    $currency = 'RWF'; // Default currency
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock Overview Report</title>
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
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .metric-label {
            color: #666;
            font-size: 12px;
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
        .movement-type {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .movement-in {
            background-color: #d4edda;
            color: #155724;
        }
        .movement-out {
            background-color: #f8d7da;
            color: #721c24;
        }
        .movement-adjustment {
            background-color: #fff3cd;
            color: #856404;
        }
        .movement-waste {
            background-color: #f5c6cb;
            color: #721c24;
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
        <h1>Stock Management Overview Report</h1>
        <p>Period: ' . date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date)) . '</p>
        <p>Generated on: ' . date('F j, Y H:i:s') . '</p>
    </div>';

    // Key Metrics
    $html .= '
    <div class="section">
        <h2>Key Performance Indicators</h2>
        <div class="metrics-grid">
            <div class="metrics-row">
                <div class="metric-card">
                    <div class="metric-value">' . number_format($data['totalItems']) . '</div>
                    <div class="metric-label">Total Items</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">' . number_format($data['lowStockItems']) . '</div>
                    <div class="metric-label">Low Stock Items</div>
                </div>
            </div>
            <div class="metrics-row">
                <div class="metric-card">
                    <div class="metric-value">' . $currency . ' ' . number_format($data['totalValue'], 2) . '</div>
                    <div class="metric-label">Total Stock Value</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">' . number_format($data['expiringItems']) . '</div>
                    <div class="metric-label">Expiring Soon (30 days)</div>
                </div>
            </div>
        </div>
    </div>';

    // Stock Value by Category
    if (!empty($data['stockValueByCategory'])) {
        $html .= '
        <div class="section">
            <h2>Stock Value by Category</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-right">Value</th>
                        <th class="text-right">Percentage</th>
                    </tr>
                </thead>
                <tbody>';
        
        $totalValue = $data['totalValue'];
        foreach ($data['stockValueByCategory'] as $category) {
            $percentage = $totalValue > 0 ? ($category['value'] / $totalValue) * 100 : 0;
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($category['category']) . '</td>
                        <td class="text-right">' . $currency . ' ' . number_format($category['value'], 2) . '</td>
                        <td class="text-right">' . number_format($percentage, 1) . '%</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }

    // Recent Movements
    if (!empty($data['recentMovements'])) {
        $html .= '
        <div class="section">
            <h2>Recent Stock Movements</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Value</th>
                        <th>Reason</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($data['recentMovements'] as $movement) {
            $typeClass = 'movement-' . $movement['type'];
            $html .= '
                    <tr>
                        <td>' . date('M j, Y H:i', strtotime($movement['date'])) . '</td>
                        <td>' . htmlspecialchars($movement['item']) . '</td>
                        <td><span class="movement-type ' . $typeClass . '">' . strtoupper($movement['type']) . '</span></td>
                        <td class="text-right">' . number_format($movement['quantity'], 2) . '</td>
                        <td class="text-right">' . $currency . ' ' . number_format($movement['cost'], 2) . '</td>
                        <td>' . htmlspecialchars($movement['reason'] ?: 'N/A') . '</td>
                        <td>' . htmlspecialchars($movement['user']) . '</td>
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
