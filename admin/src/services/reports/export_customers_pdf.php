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
error_log("Customer export dates - Start: " . $start_date . ", End: " . $end_date);

try {
    // Get customer data
    $data = getCustomerReportData($start_date, $end_date);
    
    // Create PDF
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    
    $html = generateCustomerPDFHTML($data, $start_date, $end_date);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Generate filename
    $filename = 'customer_insights_report_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $dompdf->output();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getCustomerReportData($start_date, $end_date) {
    global $conn;
    
    $data = [
        'customerSummary' => [],
        'customerDistribution' => [],
        'activeSubscriptions' => [],
        'recentReservations' => []
    ];
    
    // Customer Summary
    $totalCustomersQuery = "SELECT COUNT(*) as total FROM customers WHERE deleted_at IS NULL";
    $result = mysqli_query($conn, $totalCustomersQuery);
    $row = mysqli_fetch_assoc($result);
    $totalCustomers = (int)$row['total'];
    
    // Active subscriptions
    $activeSubscriptionsQuery = "SELECT COUNT(*) as total FROM customers 
                               WHERE subscription_status = 'active' 
                               AND subscription_end_date >= CURDATE() 
                               AND deleted_at IS NULL";
    $result = mysqli_query($conn, $activeSubscriptionsQuery);
    $row = mysqli_fetch_assoc($result);
    $activeSubscriptions = (int)$row['total'];
    
    // Expiring subscriptions (next 7 days)
    $expiringSubscriptionsQuery = "SELECT COUNT(*) as total FROM customers 
                                  WHERE subscription_status = 'active' 
                                  AND subscription_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                                  AND deleted_at IS NULL";
    $result = mysqli_query($conn, $expiringSubscriptionsQuery);
    $row = mysqli_fetch_assoc($result);
    $expiringSubscriptions = (int)$row['total'];
    
    // Payment due (next 7 days)
    $paymentDueQuery = "SELECT COUNT(*) as total FROM customers 
                       WHERE subscription_status = 'active' 
                       AND next_payment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                       AND deleted_at IS NULL";
    $result = mysqli_query($conn, $paymentDueQuery);
    $row = mysqli_fetch_assoc($result);
    $paymentDue = (int)$row['total'];
    
    $data['customerSummary'] = [
        'totalCustomers' => $totalCustomers,
        'activeSubscriptions' => $activeSubscriptions,
        'expiringSubscriptions' => $expiringSubscriptions,
        'paymentDue' => $paymentDue
    ];
    
    // Customer Distribution
    $query = "SELECT 
                CASE 
                    WHEN subscription_status = 'active' THEN 'Active Subscribers'
                    WHEN subscription_status = 'expired' THEN 'Expired Subscribers'
                    WHEN subscription_status = 'cancelled' THEN 'Cancelled Subscribers'
                    WHEN subscription_status = 'pending' THEN 'Pending Subscribers'
                    WHEN subscription_status IS NULL THEN 'Non-Subscribers'
                END as customer_type,
                COUNT(*) as count
              FROM customers 
              WHERE deleted_at IS NULL
              GROUP BY subscription_status
              ORDER BY count DESC";
    
    $result = mysqli_query($conn, $query);
    $distribution = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $distribution[] = [
            'type' => $row['customer_type'],
            'count' => (int)$row['count']
        ];
    }
    $data['customerDistribution'] = $distribution;
    
    // Active Subscriptions
    $query = "SELECT 
                c.first_name,
                c.last_name,
                c.email,
                c.phone,
                st.type_name,
                c.subscription_start_date,
                c.subscription_end_date,
                c.subscription_status,
                c.next_payment_date,
                DATEDIFF(c.subscription_end_date, CURDATE()) as days_remaining
              FROM customers c
              LEFT JOIN subscription_types st ON c.subscription_type_id = st.subscription_type_id
              WHERE c.subscription_status = 'active'
              AND c.subscription_end_date >= CURDATE()
              AND c.deleted_at IS NULL
              ORDER BY c.subscription_end_date ASC
              LIMIT 20";
    
    $result = mysqli_query($conn, $query);
    $subscriptions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $subscriptions[] = [
            'customer_name' => $row['first_name'] . ' ' . $row['last_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'subscription_type' => $row['type_name'] ?: 'Unknown',
            'start_date' => $row['subscription_start_date'],
            'end_date' => $row['subscription_end_date'],
            'status' => $row['subscription_status'],
            'next_payment_date' => $row['next_payment_date'],
            'days_remaining' => (int)$row['days_remaining']
        ];
    }
    $data['activeSubscriptions'] = $subscriptions;
    
    // Recent Reservations
    $query = "SELECT 
                r.customer_name,
                r.customer_email,
                r.customer_phone,
                r.reservation_date,
                r.reservation_time,
                r.number_of_guests,
                r.status,
                r.total_amount,
                r.created_at
              FROM reservations r
              WHERE r.deleted_at IS NULL
              ORDER BY r.created_at DESC
              LIMIT 20";
    
    $result = mysqli_query($conn, $query);
    $reservations = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $reservations[] = [
            'customer_name' => $row['customer_name'],
            'email' => $row['customer_email'],
            'phone' => $row['customer_phone'],
            'reservation_date' => $row['reservation_date'],
            'reservation_time' => $row['reservation_time'],
            'number_of_guests' => (int)$row['number_of_guests'],
            'status' => $row['status'],
            'total_amount' => (float)$row['total_amount'],
            'created_at' => $row['created_at']
        ];
    }
    $data['recentReservations'] = $reservations;
    
    return $data;
}

function generateCustomerPDFHTML($data, $start_date, $end_date) {
    $currency = 'RWF';
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Insights Report</title>
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
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-expiring {
            background-color: #ffeaa7;
            color: #6c5ce7;
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
        .alert-box {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .alert-box h3 {
            color: #0c5460;
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
        <h1>Customer Insights Report</h1>
        <p>Period: ' . date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date)) . '</p>
        <p>Generated on: ' . date('F j, Y H:i:s') . '</p>
    </div>';

    // Customer Summary
    $summary = $data['customerSummary'];
    $html .= '
    <div class="section">
        <h2>Customer Summary</h2>';
    
    if ($summary['expiringSubscriptions'] > 0) {
        $html .= '
        <div class="alert-box">
            <h3>📅 Subscription Renewals Due</h3>
            <p>' . $summary['expiringSubscriptions'] . ' subscriptions expiring in the next 7 days</p>
        </div>';
    }
    
    if ($summary['paymentDue'] > 0) {
        $html .= '
        <div class="alert-box">
            <h3>💳 Payments Due</h3>
            <p>' . $summary['paymentDue'] . ' customers have payments due in the next 7 days</p>
        </div>';
    }
    
    $html .= '
        <div class="metrics-grid">
            <div class="metrics-row">
                <div class="metric-card">
                    <div class="metric-value">' . number_format($summary['totalCustomers']) . '</div>
                    <div class="metric-label">Total Customers</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">' . number_format($summary['activeSubscriptions']) . '</div>
                    <div class="metric-label">Active Subscriptions</div>
                </div>
            </div>
            <div class="metrics-row">
                <div class="metric-card">
                    <div class="metric-value">' . number_format($summary['expiringSubscriptions']) . '</div>
                    <div class="metric-label">Expiring Soon (7 days)</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">' . number_format($summary['paymentDue']) . '</div>
                    <div class="metric-label">Payments Due (7 days)</div>
                </div>
            </div>
        </div>
    </div>';

    // Customer Distribution
    if (!empty($data['customerDistribution'])) {
        $html .= '
        <div class="section">
            <h2>Customer Distribution</h2>
            <table>
                <thead>
                    <tr>
                        <th>Customer Type</th>
                        <th class="text-right">Count</th>
                        <th class="text-right">Percentage</th>
                    </tr>
                </thead>
                <tbody>';
        
        $totalCustomers = $summary['totalCustomers'];
        foreach ($data['customerDistribution'] as $dist) {
            $percentage = $totalCustomers > 0 ? ($dist['count'] / $totalCustomers) * 100 : 0;
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($dist['type']) . '</td>
                        <td class="text-right">' . number_format($dist['count']) . '</td>
                        <td class="text-right">' . number_format($percentage, 1) . '%</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }

    // Active Subscriptions
    if (!empty($data['activeSubscriptions'])) {
        $html .= '
        <div class="section">
            <h2>Active Subscriptions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Subscription Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th class="text-center">Days Remaining</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($data['activeSubscriptions'] as $sub) {
            $statusClass = 'status-active';
            if ($sub['days_remaining'] <= 7) {
                $statusClass = 'status-expiring';
            }
            
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($sub['customer_name']) . '</td>
                        <td>' . htmlspecialchars($sub['email']) . '</td>
                        <td>' . htmlspecialchars($sub['phone']) . '</td>
                        <td>' . htmlspecialchars($sub['subscription_type']) . '</td>
                        <td>' . date('M j, Y', strtotime($sub['start_date'])) . '</td>
                        <td>' . date('M j, Y', strtotime($sub['end_date'])) . '</td>
                        <td class="text-center">' . $sub['days_remaining'] . '</td>
                        <td class="text-center"><span class="status ' . $statusClass . '">' . ucfirst($sub['status']) . '</span></td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }

    // Recent Reservations
    if (!empty($data['recentReservations'])) {
        $html .= '
        <div class="section">
            <h2>Recent Reservations</h2>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Reservation Date</th>
                        <th>Time</th>
                        <th class="text-right">Guests</th>
                        <th class="text-right">Amount</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($data['recentReservations'] as $res) {
            $statusClass = 'status-' . $res['status'];
            
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($res['customer_name']) . '</td>
                        <td>' . htmlspecialchars($res['email']) . '</td>
                        <td>' . htmlspecialchars($res['phone']) . '</td>
                        <td>' . date('M j, Y', strtotime($res['reservation_date'])) . '</td>
                        <td>' . date('H:i', strtotime($res['reservation_time'])) . '</td>
                        <td class="text-right">' . $res['number_of_guests'] . '</td>
                        <td class="text-right">' . $currency . ' ' . number_format($res['total_amount'], 2) . '</td>
                        <td class="text-center"><span class="status ' . $statusClass . '">' . ucfirst($res['status']) . '</span></td>
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
