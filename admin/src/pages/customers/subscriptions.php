<?php
if (session_status() === PHP_SESSION_NONE) session_start();


// Initialize variables
$summary_data = array(
    'total_subscriptions' => 0,
    'active_subscriptions' => 0,
    'expired_subscriptions' => 0,
    'cancelled_subscriptions' => 0,
    'auto_renewal_subscriptions' => 0,
    'expiring_soon' => 0,
    'payment_due_soon' => 0
);

$expiring_subscriptions = array();
$tables_exist = false;

// Check if subscription tables exist
try {
    // Debug: Check if connection is available
    if (!isset($conn) || !$conn) {
        echo '<div class="alert alert-danger">Database connection not available</div>';
        $tables_exist = false;
    } else {
        $check_tables = mysqli_query($conn, "SHOW TABLES LIKE 'subscription_types'");
        if ($check_tables && mysqli_num_rows($check_tables) > 0) {
            $tables_exist = true;
            
            // Load subscription summary data
            $summary_query = mysqli_query($conn, "
                SELECT 
                    COUNT(*) as total_subscriptions,
                    SUM(CASE WHEN c.subscription_status = 'active' THEN 1 ELSE 0 END) as active_subscriptions,
                    SUM(CASE WHEN c.subscription_status = 'expired' THEN 1 ELSE 0 END) as expired_subscriptions,
                    SUM(CASE WHEN c.subscription_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_subscriptions,
                    SUM(CASE WHEN c.auto_renewal = 1 THEN 1 ELSE 0 END) as auto_renewal_subscriptions,
                    SUM(CASE WHEN c.subscription_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as expiring_soon,
                    SUM(CASE WHEN c.next_payment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as payment_due_soon
                FROM customers c
                WHERE c.has_subscription = 1
            ");
            
            if ($summary_query) {
                $summary_data = mysqli_fetch_assoc($summary_query);
            }
            
            // Load expiring subscriptions
            $expiring_query = mysqli_query($conn, "
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
                WHERE c.subscription_status = 'active' 
                AND c.subscription_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ORDER BY c.subscription_end_date ASC
                LIMIT 20
            ");
            
            if ($expiring_query) {
                while ($row = mysqli_fetch_assoc($expiring_query)) {
                    $expiring_subscriptions[] = $row;
                }
            }
        } else {
            $tables_exist = false;
        }
    }
} catch (Exception $e) {
    // Handle any database errors gracefully
    echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $tables_exist = false;
}
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="customers">Customers</a></li>
        <li class="breadcrumb-item active">Subscriptions</li>
    </ol>
</nav>

<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Subscription Management</h2>
        </div>
    </div>

    <?php if (!$tables_exist): ?>
    <!-- Setup Required Message -->
    <div class="alert alert-warning" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></span>Setup Required</h4>
        <p>The subscription system has not been set up yet. You need to import the subscription database schema first.</p>
        <hr>
        <p class="mb-0">
            <strong>To set up the subscription system:</strong>
            <ol class="mb-0 mt-2">
                <li>Import the <code>config/subscription_schema.sql</code> file into your database</li>
                <li>Or run the SQL commands manually in phpMyAdmin</li>
                <li>Refresh this page after setup is complete</li>
            </ol>
        </p>
        <div class="mt-3">
            <a href="customers" class="btn btn-primary me-2">
                <i class="fas fa-arrow-left me-2"></span>Back to Customers
            </a>
            <button class="btn btn-outline-secondary" onclick="location.reload()">
                <i class="fas fa-sync-alt me-2"></span>Refresh Page
            </button>
        </div>
    </div>
    <?php else: ?>

    <!-- Subscription Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary-subtle border-primary">
                <div class="card-body">
                    <h6 class="card-title">Total Subscriptions</h6>
                    <h3><?= $summary_data['total_subscriptions'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success-subtle border-success">
                <div class="card-body">
                    <h6 class="card-title">Active</h6>
                    <h3><?= $summary_data['active_subscriptions'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning-subtle border-warning">
                <div class="card-body">
                    <h6 class="card-title">Expiring Soon</h6>
                    <h3><?= $summary_data['expiring_soon'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger-subtle border-danger">
                <div class="card-body">
                    <h6 class="card-title">Payment Due</h6>
                    <h3><?= $summary_data['payment_due_soon'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Expiring Subscriptions -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Subscriptions Expiring Within 7 Days</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Subscription Type</th>
                            <th>Expiry Date</th>
                            <th>Days Left</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($expiring_subscriptions)): ?>
                            <?php foreach ($expiring_subscriptions as $subscription): ?>
                                <?php 
                                $days_left = $subscription['days_until_expiry'];
                                $badge_class = $days_left <= 3 ? 'bg-danger' : 'bg-warning';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($subscription['first_name'] . ' ' . $subscription['last_name']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($subscription['email']) ?><br>
                                        <small><?= htmlspecialchars($subscription['phone']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($subscription['type_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($subscription['subscription_end_date'])) ?></td>
                                    <td><span class="badge <?= $badge_class ?>"><?= $days_left ?> days</span></td>
                                    <td>
                                        <a href="customers" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No subscriptions expiring soon</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Additional Subscription Statistics -->
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Subscription Status Breakdown</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Active Subscriptions:</span>
                        <strong><?= $summary_data['active_subscriptions'] ?? 0 ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Expired Subscriptions:</span>
                        <strong><?= $summary_data['expired_subscriptions'] ?? 0 ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Cancelled Subscriptions:</span>
                        <strong><?= $summary_data['cancelled_subscriptions'] ?? 0 ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Auto-Renewal Enabled:</span>
                        <strong><?= $summary_data['auto_renewal_subscriptions'] ?? 0 ?></strong>
                    </div>
                </div>
            </div>
        </div>
        <!-- <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="customers" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-2"></span>Add Customer Subscription
                        </a>
                        <a href="customers-subscriptions-types.php" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></span>Manage Subscription Types
                        </a>
                        <a href="customers" class="btn btn-outline-info">
                            <i class="fas fa-list me-2"></span>View All Customers
                        </a>
                    </div>
                </div>
            </div>
        </div> -->
    </div>

    <?php endif; ?>
</div>
