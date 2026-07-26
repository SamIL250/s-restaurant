<?php
// Include database connection
include '../../../config/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log incoming data for debugging
error_log("Dashboard API Request: " . $json);

$start_date = $data['start_date'] ?? date('Y-m-d');
$end_date = $data['end_date'] ?? date('Y-m-d');
$user_role = $data['user_role'] ?? 'admin';

try {
    // Initialize response array
    $response = [
        'success' => true,
        'metrics' => [],
        'charts' => [],
        'tables' => []
    ];

    // Get metrics based on date range
    $metrics = [];

    if ($user_role === 'admin') {
        // Revenue metrics
        $revenue_query = mysqli_query($conn, "
            SELECT COALESCE(SUM(total_amount), 0) as total 
            FROM orders 
            WHERE DATE(order_date) BETWEEN '$start_date' AND '$end_date' AND deleted_at IS NULL
        ");
        $metrics['revenue_today'] = mysqli_fetch_assoc($revenue_query)['total'];

        // Monthly revenue (current month)
        $current_month = date('Y-m-01');
        $monthly_query = mysqli_query($conn, "
            SELECT COALESCE(SUM(total_amount), 0) as total 
            FROM orders 
            WHERE order_date >= '$current_month' AND deleted_at IS NULL
        ");
        $metrics['revenue_month'] = mysqli_fetch_assoc($monthly_query)['total'];

        // Total orders
        $orders_query = mysqli_query($conn, "
            SELECT COUNT(*) as count 
            FROM orders 
            WHERE DATE(order_date) BETWEEN '$start_date' AND '$end_date' AND deleted_at IS NULL
        ");
        $metrics['total_orders'] = mysqli_fetch_assoc($orders_query)['count'];

        // Total reservations
        $reservations_query = mysqli_query($conn, "
            SELECT COUNT(*) as count 
            FROM reservations 
            WHERE DATE(reservation_date) BETWEEN '$start_date' AND '$end_date' AND deleted_at IS NULL
        ");
        $metrics['total_reservations'] = mysqli_fetch_assoc($reservations_query)['count'];

        // Total customers
        $customers_query = mysqli_query($conn, "
            SELECT COUNT(*) as count 
            FROM customers 
            WHERE deleted_at IS NULL
        ");
        $metrics['total_customers'] = mysqli_fetch_assoc($customers_query)['count'];

        // Low stock items
        $low_stock_query = mysqli_query($conn, "
            SELECT COUNT(*) as count 
            FROM inventory_items 
            WHERE current_stock <= minimum_stock AND is_active = 1 AND deleted_at IS NULL
        ");
        $metrics['low_stock_count'] = mysqli_fetch_assoc($low_stock_query)['count'];

    } elseif ($user_role === 'cashier') {
        // Cashier metrics
        $orders_query = mysqli_query($conn, "
            SELECT COUNT(*) as count 
            FROM orders 
            WHERE DATE(order_date) BETWEEN '$start_date' AND '$end_date' AND deleted_at IS NULL
        ");
        $metrics['total_orders'] = mysqli_fetch_assoc($orders_query)['count'];

        $reservations_query = mysqli_query($conn, "
            SELECT COUNT(*) as count 
            FROM reservations 
            WHERE DATE(reservation_date) BETWEEN '$start_date' AND '$end_date' AND deleted_at IS NULL
        ");
        $metrics['total_reservations'] = mysqli_fetch_assoc($reservations_query)['count'];

        $customers_query = mysqli_query($conn, "
            SELECT COUNT(*) as count 
            FROM customers 
            WHERE deleted_at IS NULL
        ");
        $metrics['total_customers'] = mysqli_fetch_assoc($customers_query)['count'];

    } elseif ($user_role === 'stock_clerk') {
        // Stock clerk metrics
        $low_stock_query = mysqli_query($conn, "
            SELECT COUNT(*) as count 
            FROM inventory_items 
            WHERE current_stock <= minimum_stock AND is_active = 1 AND deleted_at IS NULL
        ");
        $metrics['low_stock_count'] = mysqli_fetch_assoc($low_stock_query)['count'];
    }

    $response['metrics'] = $metrics;

    // Get chart data
    $charts = [];

    if ($user_role === 'admin') {
        // Revenue chart data
        $revenue_chart_query = mysqli_query($conn, "
            SELECT DATE(order_date) as date, COALESCE(SUM(total_amount), 0) as revenue 
            FROM orders 
            WHERE DATE(order_date) BETWEEN '$start_date' AND '$end_date' AND deleted_at IS NULL 
            GROUP BY DATE(order_date) 
            ORDER BY date ASC
        ");
        
        $revenue_data = ['labels' => [], 'values' => []];
        while ($row = mysqli_fetch_assoc($revenue_chart_query)) {
            $revenue_data['labels'][] = date('M j', strtotime($row['date']));
            $revenue_data['values'][] = floatval($row['revenue']);
        }
        $charts['revenue_data'] = $revenue_data;

        // Order status chart
        $status_query = mysqli_query($conn, "
            SELECT order_status, COUNT(*) as count 
            FROM orders 
            WHERE DATE(order_date) BETWEEN '$start_date' AND '$end_date' AND deleted_at IS NULL AND order_status NOT IN ('cancelled')
            GROUP BY order_status
        ");
        
        $status_data = ['labels' => [], 'values' => []];
        while ($row = mysqli_fetch_assoc($status_query)) {
            $status_data['labels'][] = ucfirst($row['order_status']);
            $status_data['values'][] = intval($row['count']);
        }
        $charts['order_status'] = $status_data;
    }

    if (in_array($user_role, ['admin', 'cashier'])) {
        // Order status chart for cashier too
        if (!isset($charts['order_status'])) {
            $status_query = mysqli_query($conn, "
                SELECT order_status, COUNT(*) as count 
                FROM orders 
                WHERE DATE(order_date) BETWEEN '$start_date' AND '$end_date' AND deleted_at IS NULL AND order_status NOT IN ('cancelled')
                GROUP BY order_status
            ");
            
            $status_data = ['labels' => [], 'values' => []];
            while ($row = mysqli_fetch_assoc($status_query)) {
                $status_data['labels'][] = ucfirst($row['order_status']);
                $status_data['values'][] = intval($row['count']);
            }
            $charts['order_status'] = $status_data;
        }
    }

    if ($user_role === 'stock_clerk') {
        // Inventory status chart
        $normal_stock = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COUNT(*) as count 
            FROM inventory_items 
            WHERE current_stock > minimum_stock AND is_active = 1 AND deleted_at IS NULL
        "))['count'];
        
        $low_stock = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COUNT(*) as count 
            FROM inventory_items 
            WHERE current_stock <= minimum_stock AND current_stock > 0 AND is_active = 1 AND deleted_at IS NULL
        "))['count'];
        
        $out_of_stock = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COUNT(*) as count 
            FROM inventory_items 
            WHERE current_stock = 0 AND is_active = 1 AND deleted_at IS NULL
        "))['count'];

        $charts['inventory'] = [
            'labels' => ['Normal Stock', 'Low Stock', 'Out of Stock'],
            'values' => [$normal_stock, $low_stock, $out_of_stock]
        ];
    }

    $response['charts'] = $charts;

    // Get table data
    $tables = [];

    if (in_array($user_role, ['admin', 'cashier'])) {
        // Recent orders
        $recent_orders_query = mysqli_query($conn, "
            SELECT o.*, CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, '')) as customer_name
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.customer_id AND c.deleted_at IS NULL
            WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date' AND o.deleted_at IS NULL AND o.order_status NOT IN ('cancelled')
            ORDER BY o.order_date DESC
            LIMIT 5
        ");
        
        $recent_orders = [];
        while ($order = mysqli_fetch_assoc($recent_orders_query)) {
            $customer_name = trim($order['customer_name']);
            if (empty($customer_name)) {
                $customer_name = 'Walk-in Customer';
            }
            
            $recent_orders[] = [
                'order_number' => $order['order_number'],
                'customer_name' => $customer_name,
                'total_amount' => $order['total_amount'],
                'order_status' => $order['order_status']
            ];
        }
        $tables['recent_orders'] = $recent_orders;

        // Recent reservations
        $recent_reservations_query = mysqli_query($conn, "
            SELECT r.*, rt.table_number
            FROM reservations r
            LEFT JOIN restaurant_tables rt ON r.table_id = rt.table_id AND rt.deleted_at IS NULL
            WHERE DATE(r.reservation_date) BETWEEN '$start_date' AND '$end_date' AND r.deleted_at IS NULL AND r.status NOT IN ('cancelled')
            ORDER BY r.reservation_date DESC, r.reservation_time DESC
            LIMIT 5
        ");
        
        $recent_reservations = [];
        while ($reservation = mysqli_fetch_assoc($recent_reservations_query)) {
            $recent_reservations[] = [
                'reservation_id' => $reservation['reservation_id'],
                'customer_name' => $reservation['customer_name'],
                'reservation_date' => date('M j, Y', strtotime($reservation['reservation_date'])),
                'status' => $reservation['status']
            ];
        }
        $tables['recent_reservations'] = $recent_reservations;
    }

    if (in_array($user_role, ['admin', 'stock_clerk'])) {
        // Low stock items
        $low_stock_items_query = mysqli_query($conn, "
            SELECT * FROM inventory_items 
            WHERE current_stock <= minimum_stock AND is_active = 1 AND deleted_at IS NULL
            ORDER BY (current_stock / minimum_stock) ASC 
            LIMIT 5
        ");
        
        $low_stock_items = [];
        while ($item = mysqli_fetch_assoc($low_stock_items_query)) {
            $percentage = ($item['current_stock'] / $item['minimum_stock']) * 100;
            $low_stock_items[] = [
                'item_name' => $item['item_name'],
                'current_stock' => $item['current_stock'],
                'unit_of_measure' => $item['unit_of_measure'],
                'minimum_stock' => $item['minimum_stock'],
                'status' => $percentage <= 50 ? 'Critical' : 'Low'
            ];
        }
        $tables['low_stock_items'] = $low_stock_items;
    }

    if (in_array($user_role, ['admin', 'cashier'])) {
        // Popular menu items
        $popular_items_query = mysqli_query($conn, "
            SELECT mi.item_name, mi.price, c.category_name, COUNT(oi.order_item_id) as order_count
            FROM menu_items mi
            LEFT JOIN categories c ON mi.category_id = c.category_id AND c.deleted_at IS NULL
            LEFT JOIN order_items oi ON mi.menu_item_id = oi.menu_item_id AND oi.deleted_at IS NULL
            LEFT JOIN orders o ON oi.order_id = o.order_id AND o.deleted_at IS NULL AND o.order_status = 'completed'
            WHERE mi.is_available = 1 
            AND mi.deleted_at IS NULL 
            AND DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
            GROUP BY mi.menu_item_id
            ORDER BY order_count DESC, mi.price DESC
            LIMIT 5
        ");
        
        $popular_items = [];
        while ($item = mysqli_fetch_assoc($popular_items_query)) {
            $popular_items[] = [
                'item_name' => $item['item_name'],
                'price' => $item['price'],
                'order_count' => $item['order_count'],
                'category_name' => $item['category_name']
            ];
        }
        $tables['popular_items'] = $popular_items;
    }

    $response['tables'] = $tables;

    // Log the response for debugging
    error_log("Dashboard API Response: " . json_encode($response));

    echo json_encode($response);

} catch (Exception $e) {
    // Log the error for debugging
    error_log("Dashboard API Error: " . $e->getMessage());
    
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
    echo json_encode($response);
}

// Ensure we always output something
if (!isset($response)) {
    echo json_encode([
        'success' => false,
        'message' => 'No response generated'
    ]);
}
?>
