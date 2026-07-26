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
    $response = [
        'customerSummary' => getCustomerSummary(),
        'customerDistribution' => getCustomerDistribution(),
        'subscriptionTrends' => getSubscriptionTrends($start_date, $end_date),
        'activeSubscriptions' => getActiveSubscriptions(),
        'recentReservations' => getRecentReservations()
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getCustomerSummary() {
    global $conn;
    
    // Total customers
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
    
    return [
        'totalCustomers' => $totalCustomers,
        'activeSubscriptions' => $activeSubscriptions,
        'expiringSubscriptions' => $expiringSubscriptions,
        'paymentDue' => $paymentDue
    ];
}

function getCustomerDistribution() {
    global $conn;
    
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
    $labels = [];
    $data = [];
    $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'];
    $colorIndex = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['customer_type'];
        $data[] = (int)$row['count'];
        $colorIndex++;
    }
    
    return [
        'labels' => $labels,
        'data' => $data,
        'colors' => array_slice($colors, 0, count($labels))
    ];
}

function getSubscriptionTrends($start_date, $end_date) {
    global $conn;
    
    // Get monthly subscription data
    $query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as new_subscriptions,
                SUM(CASE WHEN subscription_status = 'active' THEN 1 ELSE 0 END) as active_subscriptions
              FROM customers 
              WHERE created_at BETWEEN ? AND ?
              AND deleted_at IS NULL
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY month";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $months = [];
    $newSubscriptions = [];
    $activeSubscriptions = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $months[] = $row['month'];
        $newSubscriptions[] = (int)$row['new_subscriptions'];
        $activeSubscriptions[] = (int)$row['active_subscriptions'];
    }
    
    return [
        'labels' => $months,
        'newSubscriptions' => $newSubscriptions,
        'activeSubscriptions' => $activeSubscriptions
    ];
}

function getActiveSubscriptions() {
    global $conn;
    
    $query = "SELECT 
                c.first_name,
                c.last_name,
                c.email,
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
            'subscription_type' => $row['type_name'] ?: 'Unknown',
            'start_date' => $row['subscription_start_date'],
            'end_date' => $row['subscription_end_date'],
            'status' => $row['subscription_status'],
            'next_payment_date' => $row['next_payment_date'],
            'days_remaining' => (int)$row['days_remaining']
        ];
    }
    
    return $subscriptions;
}

function getRecentReservations() {
    global $conn;
    
    $query = "SELECT 
                r.customer_name,
                r.customer_email,
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
            'reservation_date' => $row['reservation_date'],
            'reservation_time' => $row['reservation_time'],
            'number_of_guests' => (int)$row['number_of_guests'],
            'status' => $row['status'],
            'total_amount' => (float)$row['total_amount'],
            'created_at' => $row['created_at']
        ];
    }
    
    return $reservations;
}
?>
