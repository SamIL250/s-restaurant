<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

// Get alerts for dashboard
$type = $_GET['type'] ?? 'all';
$limit = intval($_GET['limit'] ?? 10);

try {
    switch ($type) {
        case 'expiring_soon':
            // Get subscriptions expiring within 7 days
            $stmt = $conn->prepare('
                SELECT 
                    c.customer_id,
                    c.first_name,
                    c.last_name,
                    c.email,
                    c.phone,
                    c.subscription_end_date,
                    DATEDIFF(c.subscription_end_date, CURDATE()) as days_until_expiry,
                    st.type_name,
                    st.price
                FROM customers c
                JOIN subscription_types st ON c.subscription_type_id = st.subscription_type_id
                WHERE c.subscription_status = "active" 
                AND c.subscription_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ORDER BY c.subscription_end_date ASC
                LIMIT ?
            ');
            $stmt->bind_param('i', $limit);
            break;
            
        case 'payment_due':
            // Get subscriptions with payment due within 7 days
            $stmt = $conn->prepare('
                SELECT 
                    c.customer_id,
                    c.first_name,
                    c.last_name,
                    c.email,
                    c.phone,
                    c.next_payment_date,
                    DATEDIFF(c.next_payment_date, CURDATE()) as days_until_payment,
                    st.type_name,
                    st.price
                FROM customers c
                JOIN subscription_types st ON c.subscription_type_id = st.subscription_type_id
                WHERE c.subscription_status = "active" 
                AND c.next_payment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ORDER BY c.next_payment_date ASC
                LIMIT ?
            ');
            $stmt->bind_param('i', $limit);
            break;
            
        case 'expired':
            // Get expired subscriptions
            $stmt = $conn->prepare('
                SELECT 
                    c.customer_id,
                    c.first_name,
                    c.last_name,
                    c.email,
                    c.phone,
                    c.subscription_end_date,
                    DATEDIFF(CURDATE(), c.subscription_end_date) as days_expired,
                    st.type_name,
                    st.price
                FROM customers c
                JOIN subscription_types st ON c.subscription_type_id = st.subscription_type_id
                WHERE c.subscription_status = "active" 
                AND c.subscription_end_date < CURDATE()
                ORDER BY c.subscription_end_date DESC
                LIMIT ?
            ');
            $stmt->bind_param('i', $limit);
            break;
            
        case 'recent_alerts':
            // Get recent system alerts
            $stmt = $conn->prepare('
                SELECT 
                    sa.alert_id,
                    sa.alert_type,
                    sa.message,
                    sa.alert_date,
                    sa.is_read,
                    c.customer_id,
                    c.first_name,
                    c.last_name,
                    c.email
                FROM subscription_alerts sa
                JOIN customers c ON sa.customer_id = c.customer_id
                WHERE sa.alert_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY sa.created_at DESC
                LIMIT ?
            ');
            $stmt->bind_param('i', $limit);
            break;
            
        default:
            // Get all active subscriptions summary
            $stmt = $conn->prepare('
                SELECT 
                    COUNT(*) as total_subscriptions,
                    SUM(CASE WHEN c.subscription_status = "active" THEN 1 ELSE 0 END) as active_subscriptions,
                    SUM(CASE WHEN c.subscription_status = "expired" THEN 1 ELSE 0 END) as expired_subscriptions,
                    SUM(CASE WHEN c.subscription_status = "cancelled" THEN 1 ELSE 0 END) as cancelled_subscriptions,
                    SUM(CASE WHEN c.auto_renewal = 1 THEN 1 ELSE 0 END) as auto_renewal_subscriptions,
                    SUM(CASE WHEN c.subscription_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as expiring_soon,
                    SUM(CASE WHEN c.next_payment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as payment_due_soon
                FROM customers c
                WHERE c.has_subscription = 1
            ');
            break;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($type === 'all') {
        // Summary data
        $data = $result->fetch_assoc();
    } else {
        // List data
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'type' => $type,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
