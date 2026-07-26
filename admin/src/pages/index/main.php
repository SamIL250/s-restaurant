
<?php
// Get user role
$user_role = $_SESSION['user_role'] ?? 'admin';
$user_name = $_SESSION['user_name'] ?? 'User';

// Get dashboard data based on role
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$this_month = date('Y-m-01');
$last_month = date('Y-m-01', strtotime('-1 month'));

// Common metrics
$total_customers = 0;
$total_orders = 0;
$total_reservations = 0;
$low_stock_count = 0;

// Role-specific data
if ($user_role === 'admin') {
    // Admin sees everything
    $total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM customers WHERE deleted_at IS NULL"))['count'];
    $total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE deleted_at IS NULL"))['count'];
    $total_reservations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM reservations WHERE deleted_at IS NULL"))['count'];
    $low_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM inventory_items WHERE current_stock <= minimum_stock AND is_active = 1 AND deleted_at IS NULL"))['count'];
} elseif ($user_role === 'cashier') {
    // Cashier sees orders and reservations
    $total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE deleted_at IS NULL"))['count'];
    $total_reservations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM reservations WHERE deleted_at IS NULL"))['count'];
    $total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM customers WHERE deleted_at IS NULL"))['count'];
} elseif ($user_role === 'stock_clerk') {
    // Stock clerk sees inventory and low stock
    $low_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM inventory_items WHERE current_stock <= minimum_stock AND is_active = 1 AND deleted_at IS NULL"))['count'];
    $total_inventory_items = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM inventory_items WHERE is_active = 1 AND deleted_at IS NULL"))['count'];
}

// Revenue data (admin only)
$revenue_today = 0;
$revenue_this_month = 0;
if ($user_role === 'admin') {
    $revenue_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(order_date) = '$today' AND deleted_at IS NULL"))['total'];
    $revenue_this_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE order_date >= '$this_month' AND deleted_at IS NULL"))['total'];
}
?>

<div class="px-4 pt-6 pb-4">
    <!-- Dashboard Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-dark">Restaurant Dashboard</h4>
            <p class="mb-0 text-muted">Smart Resto Restaurant Management System</p>
        </div>
        <div class="text-end">
            <small class="text-muted">Current Date & Time</small><br>
            <strong class="text-dark"><?php echo date('M j, Y - g:i A'); ?></strong>
        </div>
    </div>

    <!-- Date Filter Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Date Range</label>
                            <select class="form-select" id="dateRange" onchange="updateDashboard()">
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="week" selected>This Week</option>
                                <option value="lastweek">Last Week</option>
                                <option value="month">This Month</option>
                                <option value="lastmonth">Last Month</option>
                                <option value="quarter">This Quarter</option>
                                <option value="year">This Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="customDateStart" style="display:none;">
                            <label class="form-label text-muted small">Start Date</label>
                            <input type="date" class="form-control" id="startDate" onchange="updateDashboard()">
                        </div>
                        <div class="col-md-4" id="customDateEnd" style="display:none;">
                            <label class="form-label text-muted small">End Date</label>
                            <input type="date" class="form-control" id="endDate" onchange="updateDashboard()">
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-primary" onclick="updateDashboard()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="row g-3 mb-4">
        <?php if ($user_role === 'admin'): ?>
            <!-- Today's Revenue Card -->
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                    <i class="fas fa-chart-line text-success fa-xl"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Today's Revenue</h6>
                                <h4 class="mb-0 text-dark fw-bold">Frw <?php echo number_format($revenue_today, 0); ?></h4>
                                <small class="text-muted">Total sales today</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Revenue Card -->
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info bg-opacity-10 rounded-3 p-3">
                                    <i class="fas fa-calendar-alt text-info fa-xl"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Monthly Revenue</h6>
                                <h4 class="mb-0 text-dark fw-bold">Frw <?php echo number_format($revenue_this_month, 0); ?></h4>
                                <small class="text-muted">Total this month</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Orders Card -->
        <?php if (in_array($user_role, ['admin', 'cashier'])): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                    <i class="fas fa-shopping-cart text-warning fa-xl"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Orders</h6>
                                <h4 class="mb-0 text-dark fw-bold"><?php echo $total_orders; ?></h4>
                                <small class="text-muted">All time orders</small>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="orders" class="btn btn-sm btn-outline-warning">View Orders</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Reservations Card -->
        <?php if (in_array($user_role, ['admin', 'cashier'])): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                    <i class="fas fa-calendar-check text-primary fa-xl"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Reservations</h6>
                                <h4 class="mb-0 text-dark fw-bold"><?php echo $total_reservations; ?></h4>
                                <small class="text-muted">Total bookings</small>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="reservations" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Customers Card -->
        <?php if (in_array($user_role, ['admin', 'cashier'])): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-secondary bg-opacity-10 rounded-3 p-3">
                                    <i class="fas fa-users text-secondary fa-xl"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Customers</h6>
                                <h4 class="mb-0 text-dark fw-bold"><?php echo $total_customers; ?></h4>
                                <small class="text-muted">Registered customers</small>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="customers" class="btn btn-sm btn-outline-secondary">Manage</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Low Stock Alert Card -->
        <?php if (in_array($user_role, ['admin', 'stock_clerk'])): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-danger bg-opacity-10 rounded-3 p-3">
                                    <i class="fas fa-exclamation-triangle text-danger fa-xl"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Low Stock Items</h6>
                                <h4 class="mb-0 text-dark fw-bold"><?php echo $low_stock_count; ?></h4>
                                <small class="text-muted">Items need restocking</small>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="inventory-items" class="btn btn-sm btn-outline-danger">Restock</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Charts and Tables Row -->
    <div class="row g-3 mb-4">
        <!-- Revenue Chart (Admin Only) -->
        <?php if ($user_role === 'admin'): ?>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Revenue Overview</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active" onclick="updateRevenueChart('week')">Week</button>
                            <button class="btn btn-outline-primary" onclick="updateRevenueChart('month')">Month</button>
                            <button class="btn btn-outline-primary" onclick="updateRevenueChart('year')">Year</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Order Status Chart -->
        <?php if (in_array($user_role, ['admin', 'cashier'])): ?>
            <div class="<?php echo $user_role === 'admin' ? 'col-lg-4' : 'col-lg-6'; ?>">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="orderStatusChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Inventory Status Chart (Stock Clerk) -->
        <?php if ($user_role === 'stock_clerk'): ?>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Inventory Status</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="inventoryChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Chart Data and Initialization -->
    <script>
    // Order Status Chart Data
    <?php if (in_array($user_role, ['admin', 'cashier'])): ?>
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        const orderStatusData = <?php
            $status_query = mysqli_query($conn, "SELECT order_status, COUNT(*) as count FROM orders WHERE deleted_at IS NULL AND order_status NOT IN ('cancelled') GROUP BY order_status");
            $status_data = [];
            while ($row = mysqli_fetch_assoc($status_query)) {
                $status_data[] = $row;
            }
            echo json_encode($status_data);
        ?>;
        
        window.orderStatusChart = new Chart(orderStatusCtx, {
            type: 'doughnut',
            data: {
                labels: orderStatusData.map(item => item.order_status.charAt(0).toUpperCase() + item.order_status.slice(1)),
                datasets: [{
                    data: orderStatusData.map(item => item.count),
                    backgroundColor: [
                        '#FFC107', // pending - warning
                        '#17A2B8', // confirmed - info  
                        '#007BFF', // preparing - primary
                        '#28A745', // ready - success
                        '#6C757D', // completed - secondary
                        '#DC3545'  // cancelled - danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    <?php endif; ?>

    // Inventory Status Chart (Stock Clerk)
    <?php if ($user_role === 'stock_clerk'): ?>
        const inventoryCtx = document.getElementById('inventoryChart').getContext('2d');
        const inventoryData = <?php
            $normal_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM inventory_items WHERE current_stock > minimum_stock AND is_active = 1 AND deleted_at IS NULL"))['count'];
            $low_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM inventory_items WHERE current_stock <= minimum_stock AND current_stock > 0 AND is_active = 1 AND deleted_at IS NULL"))['count'];
            $out_of_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM inventory_items WHERE current_stock = 0 AND is_active = 1 AND deleted_at IS NULL"))['count'];
            
            echo json_encode([
                ['Normal Stock', $normal_stock],
                ['Low Stock', $low_stock], 
                ['Out of Stock', $out_of_stock]
            ]);
        ?>;
        
        window.inventoryChart = new Chart(inventoryCtx, {
            type: 'pie',
            data: {
                labels: inventoryData.map(item => item[0]),
                datasets: [{
                    data: inventoryData.map(item => item[1]),
                    backgroundColor: [
                        '#28A745', // normal - success
                        '#FFC107', // low - warning
                        '#DC3545'  // out of stock - danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    <?php endif; ?>

    // Revenue Chart (Admin)
    <?php if ($user_role === 'admin'): ?>
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = <?php
            // Last 7 days revenue data with fallback for empty data
            $revenue_query = mysqli_query($conn, "
                SELECT DATE(order_date) as date, COALESCE(SUM(total_amount), 0) as revenue 
                FROM orders 
                WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND deleted_at IS NULL 
                GROUP BY DATE(order_date) 
                ORDER BY date ASC
            ");
            $revenue_array = [];
            
            // Generate last 7 days with data
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $revenue = 0;
                
                // Check if we have data for this date
                mysqli_data_seek($revenue_query, 0);
                while ($row = mysqli_fetch_assoc($revenue_query)) {
                    if ($row['date'] === $date) {
                        $revenue = floatval($row['revenue']);
                        break;
                    }
                }
                
                $revenue_array[] = [
                    date('M j', strtotime($date)),
                    $revenue
                ];
            }
            echo json_encode($revenue_array);
        ?>;
        
        window.revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueData.map(item => item[0]),
                datasets: [{
                    label: 'Revenue (Frw)',
                    data: revenueData.map(item => item[1]),
                    borderColor: '#007BFF',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Frw ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    <?php endif; ?>
    </script>

    <!-- Recent Activity Tables -->
    <div class="row g-3">
        <!-- Recent Orders -->
        <?php if (in_array($user_role, ['admin', 'cashier'])): ?>
            <?php
            // Get recent orders data
            $recent_orders_query = mysqli_query($conn, "
                SELECT o.*, c.first_name, c.last_name, t.table_number
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.customer_id AND c.deleted_at IS NULL
                LEFT JOIN restaurant_tables t ON o.table_id = t.table_id AND t.deleted_at IS NULL
                WHERE o.deleted_at IS NULL AND o.order_status NOT IN ('cancelled')
                ORDER BY o.order_date DESC
                LIMIT 5
            ");
            ?>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Orders</h5>
                        <a href="orders" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3">Order #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = mysqli_fetch_assoc($recent_orders_query)): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <a href="orders" class="fw-bold text-primary"><?php echo $order['order_number']; ?></a>
                                            </td>
                                            <td><?php echo ($order['first_name'] ?? 'Walk-in') . ' ' . ($order['last_name'] ?? 'Customer'); ?></td>
                                            <td>Frw <?php echo number_format($order['total_amount'], 0); ?></td>
                                            <td>
                                                <span class="badge badge-soft-<?php 
                                                    echo match($order['order_status']) {
                                                        'pending' => 'warning',
                                                        'confirmed' => 'info',
                                                        'preparing' => 'primary',
                                                        'ready' => 'success',
                                                        'completed' => 'secondary',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($order['order_status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Reservations -->
        <?php if (in_array($user_role, ['admin', 'cashier'])): ?>
            <?php
            $recent_reservations_query = mysqli_query($conn, "
                SELECT r.*, rt.table_number
                FROM reservations r
                LEFT JOIN restaurant_tables rt ON r.table_id = rt.table_id AND rt.deleted_at IS NULL
                WHERE r.deleted_at IS NULL AND r.status NOT IN ('cancelled')
                ORDER BY r.reservation_date DESC, r.reservation_time DESC
                LIMIT 5
            ");
            ?>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Reservations</h5>
                        <a href="reservations" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3">ID</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($reservation = mysqli_fetch_assoc($recent_reservations_query)): ?>
                                        <tr>
                                            <td class="ps-3">#<?php echo $reservation['reservation_id']; ?></td>
                                            <td><?php echo $reservation['customer_name']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($reservation['reservation_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-soft-<?php 
                                                    echo match($reservation['status']) {
                                                        'pending' => 'warning',
                                                        'confirmed' => 'success',
                                                        'cancelled' => 'danger',
                                                        'completed' => 'primary',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($reservation['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Low Stock Items -->
        <?php if (in_array($user_role, ['admin', 'stock_clerk'])): ?>
            <?php
            $low_stock_query = mysqli_query($conn, "
                SELECT * FROM inventory_items 
                WHERE current_stock <= minimum_stock AND is_active = 1 AND deleted_at IS NULL
                ORDER BY (current_stock / minimum_stock) ASC 
                LIMIT 5
            ");
            ?>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Low Stock Alerts</h5>
                        <a href="inventory-items" class="btn btn-sm btn-outline-danger">Manage Inventory</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3">Item</th>
                                        <th>Current Stock</th>
                                        <th>Minimum</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = mysqli_fetch_assoc($low_stock_query)): ?>
                                        <tr>
                                            <td class="fw-bold ps-3"><?php echo $item['item_name']; ?></td>
                                            <td class="text-danger fw-bold"><?php echo $item['current_stock']; ?> <?php echo $item['unit_of_measure']; ?></td>
                                            <td><?php echo $item['minimum_stock']; ?> <?php echo $item['unit_of_measure']; ?></td>
                                            <td>
                                                <span class="badge badge-soft-danger">
                                                    <?php 
                                                    $percentage = ($item['current_stock'] / $item['minimum_stock']) * 100;
                                                    echo $percentage <= 50 ? 'Critical' : 'Low';
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Popular Items -->
        <?php if (in_array($user_role, ['admin', 'cashier'])): ?>
            <?php
            $popular_query = mysqli_query($conn, "
                SELECT mi.item_name, mi.price, c.category_name, COUNT(oi.order_item_id) as order_count
                FROM menu_items mi
                LEFT JOIN categories c ON mi.category_id = c.category_id AND c.deleted_at IS NULL
                LEFT JOIN order_items oi ON mi.menu_item_id = oi.menu_item_id AND oi.deleted_at IS NULL
                LEFT JOIN orders o ON oi.order_id = o.order_id AND o.deleted_at IS NULL AND o.order_status = 'completed'
                WHERE mi.is_available = 1 AND mi.deleted_at IS NULL
                GROUP BY mi.menu_item_id
                ORDER BY order_count DESC, mi.price DESC
                LIMIT 5
            ");
            ?>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Popular Menu Items</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3">Item</th>
                                        <th>Price</th>
                                        <th>Orders</th>
                                        <th>Category</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = mysqli_fetch_assoc($popular_query)): ?>
                                        <tr>
                                            <td class="fw-bold ps-3"><?php echo $item['item_name']; ?></td>
                                            <td>Frw <?php echo number_format($item['price'], 0); ?></td>
                                            <td>
                                                <span class="badge badge-soft-info"><?php echo $item['order_count']; ?></span>
                                            </td>
                                            <td><?php echo $item['category_name']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Handle date range selection
    document.getElementById('dateRange').addEventListener('change', function() {
        const customStart = document.getElementById('customDateStart');
        const customEnd = document.getElementById('customDateEnd');
        
        if (this.value === 'custom') {
            customStart.style.display = 'block';
            customEnd.style.display = 'block';
        } else {
            customStart.style.display = 'none';
            customEnd.style.display = 'none';
        }
    });

    // Get date range based on selection
    function getDateRange(range) {
        const today = new Date();
        let startDate, endDate;
        
        switch(range) {
            case 'today':
                startDate = endDate = today;
                break;
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                startDate = endDate = yesterday;
                break;
            case 'week':
                const weekStart = new Date(today);
                weekStart.setDate(today.getDate() - today.getDay());
                startDate = weekStart;
                endDate = today;
                break;
            case 'lastweek':
                const lastWeekStart = new Date(today);
                lastWeekStart.setDate(today.getDate() - today.getDay() - 7);
                const lastWeekEnd = new Date(lastWeekStart);
                lastWeekEnd.setDate(lastWeekStart.getDate() + 6);
                startDate = lastWeekStart;
                endDate = lastWeekEnd;
                break;
            case 'month':
                const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                startDate = monthStart;
                endDate = today;
                break;
            case 'lastmonth':
                const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                startDate = lastMonthStart;
                endDate = lastMonthEnd;
                break;
            case 'quarter':
                const quarterStart = new Date(today.getFullYear(), Math.floor(today.getMonth() / 3) * 3, 1);
                startDate = quarterStart;
                endDate = today;
                break;
            case 'year':
                const yearStart = new Date(today.getFullYear(), 0, 1);
                startDate = yearStart;
                endDate = today;
                break;
            case 'custom':
                startDate = new Date(document.getElementById('startDate').value);
                endDate = new Date(document.getElementById('endDate').value);
                break;
            default:
                startDate = endDate = today;
        }
        
        return {
            start: startDate.toISOString().split('T')[0],
            end: endDate.toISOString().split('T')[0]
        };
    }

    // Update dashboard with filtered data
    function updateDashboard() {
        const dateRange = document.getElementById('dateRange').value;
        const dates = getDateRange(dateRange);
        
        // Show loading state
        showLoadingState();
        
        // Make AJAX call to get filtered data
        fetch('src/services/dashboard/get_dashboard_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                start_date: dates.start,
                end_date: dates.end,
                user_role: '<?php echo $user_role; ?>'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Raw response:', text);
                    throw new Error('Invalid JSON response: ' + e.message);
                }
            });
        })
        .then(data => {
            if (data.success) {
                updateMetrics(data.metrics);
                updateCharts(data.charts);
                updateTables(data.tables);
            } else {
                console.error('Error updating dashboard:', data.message);
                alert('Error updating dashboard: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Error updating dashboard: ' + error.message);
        })
        .finally(() => {
            hideLoadingState();
        });
    }

    // Show loading state
    function showLoadingState() {
        // Add loading spinners to metrics
        document.querySelectorAll('.card-body h4').forEach(element => {
            element.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';
        });
        
        // Disable refresh button
        document.querySelector('button[onclick="updateDashboard()"]').disabled = true;
    }

    // Hide loading state
    function hideLoadingState() {
        // Re-enable refresh button
        document.querySelector('button[onclick="updateDashboard()"]').disabled = false;
    }

    // Update metrics with new data
    function updateMetrics(metrics) {
        if (metrics.revenue_today) {
            document.querySelector('.bg-success').closest('.card-body').querySelector('h4').textContent = 'Frw ' + parseFloat(metrics.revenue_today).toLocaleString();
        }
        if (metrics.revenue_month) {
            document.querySelector('.bg-info').closest('.card-body').querySelector('h4').textContent = 'Frw ' + parseFloat(metrics.revenue_month).toLocaleString();
        }
        if (metrics.total_orders) {
            document.querySelector('.bg-warning').closest('.card-body').querySelector('h4').textContent = metrics.total_orders;
        }
        if (metrics.total_reservations) {
            document.querySelector('.bg-primary').closest('.card-body').querySelector('h4').textContent = metrics.total_reservations;
        }
        if (metrics.total_customers) {
            document.querySelector('.bg-secondary').closest('.card-body').querySelector('h4').textContent = metrics.total_customers;
        }
        if (metrics.low_stock_count) {
            document.querySelector('.bg-danger').closest('.card-body').querySelector('h4').textContent = metrics.low_stock_count;
        }
    }

    // Update charts with new data
    function updateCharts(charts) {
        // Update revenue chart
        if (charts.revenue_data && window.revenueChart) {
            window.revenueChart.data.labels = charts.revenue_data.labels;
            window.revenueChart.data.datasets[0].data = charts.revenue_data.values;
            window.revenueChart.update();
        }
        
        // Update order status chart
        if (charts.order_status && window.orderStatusChart) {
            window.orderStatusChart.data.labels = charts.order_status.labels;
            window.orderStatusChart.data.datasets[0].data = charts.order_status.values;
            window.orderStatusChart.update();
        }
        
        // Update inventory chart
        if (charts.inventory && window.inventoryChart) {
            window.inventoryChart.data.labels = charts.inventory.labels;
            window.inventoryChart.data.datasets[0].data = charts.inventory.values;
            window.inventoryChart.update();
        }
    }

    // Update tables with new data
    function updateTables(tables) {
        console.log('Updating tables with data:', tables); // Debug log
        
        // Update recent orders table
        if (tables.recent_orders && Array.isArray(tables.recent_orders)) {
            console.log('Updating recent orders table'); // Debug log
            
            const allTables = document.querySelectorAll('table');
            let ordersTable = null;
            
            allTables.forEach(table => {
                const headers = table.querySelectorAll('th');
                headers.forEach(th => {
                    if (th.textContent.includes('Order #')) {
                        ordersTable = table;
                    }
                });
            });
            
            if (ordersTable) {
                const tbody = ordersTable.querySelector('tbody');
                if (tbody) {
                    tbody.innerHTML = tables.recent_orders.map(order => `
                        <tr>
                            <td class="ps-3">
                                <a href="orders" class="fw-bold text-primary">${order.order_number}</a>
                            </td>
                            <td>${order.customer_name}</td>
                            <td>Frw ${parseFloat(order.total_amount).toLocaleString()}</td>
                            <td>
                                <span class="badge badge-soft-${getStatusClass(order.order_status)}">
                                    ${order.order_status}
                                </span>
                            </td>
                        </tr>
                    `).join('');
                }
            }
        } else {
            console.log('No recent_orders data or not an array:', tables.recent_orders);
        }
        
        // Update recent reservations table
        if (tables.recent_reservations && Array.isArray(tables.recent_reservations)) {
            const allTables = document.querySelectorAll('table');
            let reservationsTable = null;
            
            allTables.forEach(table => {
                const headers = table.querySelectorAll('th');
                headers.forEach(th => {
                    if (th.textContent.includes('ID') && th.textContent.includes('Customer')) {
                        reservationsTable = table;
                    }
                });
            });
            
            if (reservationsTable) {
                const tbody = reservationsTable.querySelector('tbody');
                if (tbody) {
                    tbody.innerHTML = tables.recent_reservations.map(reservation => `
                        <tr>
                            <td class="ps-3">#${reservation.reservation_id}</td>
                            <td>${reservation.customer_name}</td>
                            <td>${reservation.reservation_date}</td>
                            <td>
                                <span class="badge badge-soft-${getStatusClass(reservation.status)}">
                                    ${reservation.status}
                                </span>
                            </td>
                        </tr>
                    `).join('');
                }
            }
        }
        
        // Update low stock items table
        if (tables.low_stock_items && Array.isArray(tables.low_stock_items)) {
            const allTables = document.querySelectorAll('table');
            let lowStockTable = null;
            
            allTables.forEach(table => {
                const headers = table.querySelectorAll('th');
                headers.forEach(th => {
                    if (th.textContent.includes('Item') && th.textContent.includes('Current Stock')) {
                        lowStockTable = table;
                    }
                });
            });
            
            if (lowStockTable) {
                const tbody = lowStockTable.querySelector('tbody');
                if (tbody) {
                    tbody.innerHTML = tables.low_stock_items.map(item => `
                        <tr>
                            <td class="fw-bold ps-3">${item.item_name}</td>
                            <td class="text-danger fw-bold">${item.current_stock} ${item.unit_of_measure}</td>
                            <td>${item.minimum_stock} ${item.unit_of_measure}</td>
                            <td>
                                <span class="badge badge-soft-danger">
                                    ${item.status}
                                </span>
                            </td>
                        </tr>
                    `).join('');
                }
            }
        }
        
        // Update popular items table
        if (tables.popular_items && Array.isArray(tables.popular_items)) {
            const allTables = document.querySelectorAll('table');
            let popularItemsTable = null;
            
            allTables.forEach(table => {
                const headers = table.querySelectorAll('th');
                headers.forEach(th => {
                    if (th.textContent.includes('Item') && th.textContent.includes('Price')) {
                        popularItemsTable = table;
                    }
                });
            });
            
            if (popularItemsTable) {
                const tbody = popularItemsTable.querySelector('tbody');
                if (tbody) {
                    tbody.innerHTML = tables.popular_items.map(item => `
                        <tr>
                            <td class="fw-bold ps-3">${item.item_name}</td>
                            <td>Frw ${parseFloat(item.price).toLocaleString()}</td>
                            <td>
                                <span class="badge badge-soft-info">${item.order_count}</span>
                            </td>
                            <td>${item.category_name}</td>
                        </tr>
                    `).join('');
                }
            }
        }
    }

    // Get status class for badges
    function getStatusClass(status) {
        const statusMap = {
            'pending': 'warning',
            'confirmed': 'info',
            'preparing': 'primary',
            'ready': 'success',
            'completed': 'secondary',
            'cancelled': 'danger'
        };
        return statusMap[status] || 'secondary';
    }

    // Store chart instances globally for updates
    window.revenueChart = null;
    window.orderStatusChart = null;
    window.inventoryChart = null;

    // Update revenue chart with different time periods
    function updateRevenueChart(period) {
        if (!window.revenueChart) return;
        
        let startDate, endDate;
        const today = new Date();
        
        switch(period) {
            case 'week':
                startDate = new Date(today);
                startDate.setDate(today.getDate() - 6);
                endDate = today;
                break;
            case 'month':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                endDate = today;
                break;
            case 'year':
                startDate = new Date(today.getFullYear(), 0, 1);
                endDate = today;
                break;
            default:
                startDate = new Date(today);
                startDate.setDate(today.getDate() - 6);
                endDate = today;
        }
        
        // Fetch revenue data for the selected period
        fetch('src/services/dashboard/get_revenue_chart_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                start_date: startDate.toISOString().split('T')[0],
                end_date: endDate.toISOString().split('T')[0]
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && window.revenueChart) {
                window.revenueChart.data.labels = data.labels;
                window.revenueChart.data.datasets[0].data = data.values;
                window.revenueChart.update();
            }
        })
        .catch(error => {
            console.error('Error updating revenue chart:', error);
        });
    }

    // Initialize dashboard on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Don't auto-update on page load - use the initial PHP data
        console.log('Dashboard loaded with initial data');
    });
</script>
