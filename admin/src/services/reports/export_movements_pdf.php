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
error_log("Movements export dates - Start: " . $start_date . ", End: " . $end_date);

try {
    // Get movement data
    $data = getMovementReportData($start_date, $end_date);
    
    // Create PDF
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    
    $html = generateMovementPDFHTML($data, $start_date, $end_date);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Generate filename
    $filename = 'movement_report_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $dompdf->output();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getMovementReportData($start_date, $end_date) {
    global $conn;
    
    $data = [
        'movementSummary' => [],
        'movementByCategory' => [],
        'movements' => []
    ];
    
    // Movement Summary
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
        'totalIn' => ['quantity' => 0, 'value' => 0],
        'totalOut' => ['quantity' => 0, 'value' => 0],
        'totalAdjustments' => ['quantity' => 0, 'value' => 0],
        'totalWaste' => ['quantity' => 0, 'value' => 0]
    ];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $type = $row['movement_type'];
        $quantity = (float)$row['total_quantity'];
        $value = (float)$row['total_value'];
        
        switch ($type) {
            case 'in':
                $summary['totalIn'] = ['quantity' => $quantity, 'value' => $value];
                break;
            case 'out':
                $summary['totalOut'] = ['quantity' => $quantity, 'value' => $value];
                break;
            case 'adjustment':
                $summary['totalAdjustments'] = ['quantity' => $quantity, 'value' => $value];
                break;
            case 'waste':
                $summary['totalWaste'] = ['quantity' => $quantity, 'value' => $value];
                break;
        }
    }
    $data['movementSummary'] = $summary;
    
    // Movement by Category
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
                'in' => ['quantity' => 0, 'value' => 0],
                'out' => ['quantity' => 0, 'value' => 0],
                'adjustment' => ['quantity' => 0, 'value' => 0],
                'waste' => ['quantity' => 0, 'value' => 0]
            ];
        }
        
        $categoryData[$category][$type] = ['quantity' => $quantity, 'value' => $value];
    }
    
    $data['movementByCategory'] = $categoryData;
    
    // Detailed Movements
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
    $data['movements'] = $movements;
    
    return $data;
}

function generateMovementPDFHTML($data, $start_date, $end_date) {
    $currency = 'RWF';
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock Movement Report</title>
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
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
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
        <h1>Stock Movement Report</h1>
        <p>Period: ' . date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date)) . '</p>
        <p>Generated on: ' . date('F j, Y H:i:s') . '</p>
    </div>';

    // Movement Summary
    $summary = $data['movementSummary'];
    $html .= '
    <div class="section">
        <h2>Movement Summary</h2>
        <div class="metrics-grid">
            <div class="metrics-row">
                <div class="summary-card">
                    <div class="summary-value">' . number_format($summary['totalIn']['quantity'], 2) . '</div>
                    <div class="summary-label">Total Stock In</div>
                    <div class="summary-value">' . $currency . ' ' . number_format($summary['totalIn']['value'], 2) . '</div>
                    <div class="summary-label">Stock In Value</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value">' . number_format($summary['totalOut']['quantity'], 2) . '</div>
                    <div class="summary-label">Total Stock Out</div>
                    <div class="summary-value">' . $currency . ' ' . number_format($summary['totalOut']['value'], 2) . '</div>
                    <div class="summary-label">Stock Out Value</div>
                </div>
            </div>
            <div class="metrics-row">
                <div class="summary-card">
                    <div class="summary-value">' . number_format($summary['totalAdjustments']['quantity'], 2) . '</div>
                    <div class="summary-label">Total Adjustments</div>
                    <div class="summary-value">' . $currency . ' ' . number_format($summary['totalAdjustments']['value'], 2) . '</div>
                    <div class="summary-label">Adjustment Value</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value">' . number_format($summary['totalWaste']['quantity'], 2) . '</div>
                    <div class="summary-label">Total Waste</div>
                    <div class="summary-value">' . $currency . ' ' . number_format($summary['totalWaste']['value'], 2) . '</div>
                    <div class="summary-label">Waste Value</div>
                </div>
            </div>
        </div>
    </div>';

    // Movement by Category
    if (!empty($data['movementByCategory'])) {
        $html .= '
        <div class="section">
            <h2>Movement by Category</h2>';
        
        foreach ($data['movementByCategory'] as $category => $movements) {
            $html .= '
            <div class="category-section">
                <h3>' . htmlspecialchars($category) . '</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Movement Type</th>
                            <th class="text-right">Quantity</th>
                            <th class="text-right">Value</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($movements as $type => $data) {
                if ($data['quantity'] > 0) {
                    $typeClass = 'movement-' . $type;
                    $html .= '
                        <tr>
                            <td><span class="movement-type ' . $typeClass . '">' . strtoupper($type) . '</span></td>
                            <td class="text-right">' . number_format($data['quantity'], 2) . '</td>
                            <td class="text-right">' . $currency . ' ' . number_format($data['value'], 2) . '</td>
                        </tr>';
                }
            }
            
            $html .= '
                    </tbody>
                </table>
            </div>';
        }
        
        $html .= '
        </div>';
    }

    // Detailed Movements
    if (!empty($data['movements'])) {
        $html .= '
        <div class="section">
            <h2>Detailed Movement History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Unit Cost</th>
                        <th class="text-right">Total Cost</th>
                        <th>Reason</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($data['movements'] as $movement) {
            $typeClass = 'movement-' . $movement['movement_type'];
            $html .= '
                    <tr>
                        <td>' . date('M j, Y H:i', strtotime($movement['movement_date'])) . '</td>
                        <td>' . htmlspecialchars($movement['item_name']) . '</td>
                        <td>' . htmlspecialchars($movement['category_name']) . '</td>
                        <td><span class="movement-type ' . $typeClass . '">' . strtoupper($movement['movement_type']) . '</span></td>
                        <td class="text-right">' . number_format($movement['quantity'], 2) . ' ' . htmlspecialchars($movement['unit_of_measure']) . '</td>
                        <td class="text-right">' . $currency . ' ' . number_format($movement['unit_cost'], 2) . '</td>
                        <td class="text-right">' . $currency . ' ' . number_format($movement['total_cost'], 2) . '</td>
                        <td>' . htmlspecialchars($movement['reason'] ?: 'N/A') . '</td>
                        <td>' . htmlspecialchars($movement['user_name']) . '</td>
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
