<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Initialize variables
$db_error = '';
$tables_exist = false;
$report_admin_role = defined('ROLE_ADMIN') ? ROLE_ADMIN : 'admin';
$report_cashier_role = defined('ROLE_CASHIER') ? ROLE_CASHIER : 'cashier';
$report_clerk_role = defined('ROLE_STOCK_CLERK') ? ROLE_STOCK_CLERK : 'stock_clerk';
$user_role = $_SESSION['user_role'] ?? $report_admin_role;
$is_admin = $user_role === $report_admin_role;
$is_clerk = $user_role === $report_clerk_role;
$allowed_report_tabs = function_exists('report_tabs_for_role')
    ? report_tabs_for_role($user_role)
    : ['overview', 'stock', 'movements', 'loss', 'performance', 'customers'];
$default_report_tab = $allowed_report_tabs[0] ?? 'overview';
$tab_labels = [
    'overview' => 'Overview',
    'stock' => 'Stock Analytics',
    'movements' => 'Movement Reports',
    'loss' => 'Loss Analysis',
    'performance' => 'Performance',
    'customers' => 'Customer Insights'
];

// Check if database connection is available
if (!isset($conn) || !$conn) {
    $db_error = 'Database connection not available. Please check your configuration.';
    $tables_exist = false;
} else {
    // Check if key tables exist
    try {
        $check_tables = mysqli_query($conn, "SHOW TABLES LIKE 'inventory_items'");
        if ($check_tables && mysqli_num_rows($check_tables) > 0) {
            $tables_exist = true;
        } else {
            $tables_exist = false;
        }
    } catch (Exception $e) {
        $db_error = 'Database error: ' . htmlspecialchars($e->getMessage());
        $tables_exist = false;
    }
}

// Get current date range for default filters
$current_month = date('Y-m');
$current_year = date('Y');
$start_date = date('Y-m-01');
$end_date = date('Y-m-t');
?>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    
    <!-- Date Range Picker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <style>
        .report-nav {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .report-nav .nav-link {
            color: #666;
            border: none;
            border-radius: 8px;
            margin: 3px;
            transition: all 0.3s ease;
            background: transparent;
        }
        
        .report-nav .nav-link:hover,
        .report-nav .nav-link.active {
            background: #2c3e50;
            color: white;
        }
        
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            color: #333;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #2c3e50;
        }
        
        .metric-card.success {
            border-left-color: #28a745;
        }
        
        .metric-card.warning {
            border-left-color: #ffc107;
        }
        
        .metric-card.danger {
            border-left-color: #dc3545;
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .metric-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 400px;
            position: relative;
        }
        
        .report-section {
            display: none;
        }
        
        .report-section.active {
            display: block;
        }
        
        .data-table {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 50px;
        }
        .error-box {
            display: none;
            background: #fdecea;
            color: #a61b1b;
            border: 1px solid #f5c2c7;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 16px;
        }
        
    .export-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
        
        .export-btn {
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            margin: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            background: #34495e;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>

<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Stock Management Reports</li>
    </ol>
</nav>

<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Stock Management Analytics</h2>
            <p class="text-muted mb-0">Comprehensive inventory tracking and business intelligence</p>
        </div>
        <div class="col-auto ms-auto">
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="refreshAllReports()">Refresh Data</button>
                <button class="btn btn-primary" onclick="exportCurrentReport()">Export Report</button>
            </div>
        </div>
    </div>

    <?php if ($db_error): ?>
    <!-- Database Error Message -->
    <div class="alert alert-danger" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Database Error</h4>
        <p><?= htmlspecialchars($db_error) ?></p>
    </div>
    <?php elseif (!$tables_exist): ?>
    <!-- Setup Required Message -->
    <div class="alert alert-warning" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Setup Required</h4>
        <p>The reporting system requires the inventory tables to be set up first.</p>
    </div>
    <?php else: ?>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date Range</label>
                    <input type="text" id="dateRange" class="form-control" value="<?= $start_date ?> - <?= $end_date ?>">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" onclick="applyDateFilter()">Apply Filter</button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" onclick="resetDateFilter()">Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Navigation -->
    <div class="report-nav">
        <ul class="nav nav-pills justify-content-center flex-wrap" id="reportTabs" role="tablist">
            <?php foreach ($tab_labels as $tabKey => $tabLabel): ?>
                <?php if (!in_array($tabKey, $allowed_report_tabs, true)) continue; ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $tabKey === $default_report_tab ? 'active' : ''; ?>" id="<?php echo $tabKey; ?>-tab" data-bs-toggle="pill" data-bs-target="#<?php echo $tabKey; ?>" type="button" role="tab"><?php echo $tabLabel; ?></button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Report Content -->
    <div class="tab-content" id="reportContent">
        
        <!-- Overview Tab -->
        <?php if (in_array('overview', $allowed_report_tabs, true)): ?>
        <div class="tab-pane fade <?php echo $default_report_tab === 'overview' ? 'show active' : ''; ?>" id="overview" role="tabpanel">
            <div class="error-box" id="overviewError"></div>
            <div class="loading-spinner" id="overviewLoading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading overview data...</p>
            </div>
            
            <div id="overviewContent">
                <!-- Key Metrics Row -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-card success">
                            <div class="metric-value" id="totalItems">-</div>
                            <div class="metric-label">Total Items</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card warning">
                            <div class="metric-value" id="lowStockItems">-</div>
                            <div class="metric-label">Low Stock Items</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value" id="totalValue">-</div>
                            <div class="metric-label">Total Stock Value</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card danger">
                            <div class="metric-value" id="expiringItems">-</div>
                            <div class="metric-label">Expiring Soon</div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Stock Value by Category</h5>
                            <canvas id="stockValueChart" height="300"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Movement Trends</h5>
                            <canvas id="movementTrendsChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-12">
                        <div class="data-table">
                            <h5>Recent Stock Movements</h5>
                            <div class="table-responsive">
                                <table class="table table-hover" id="recentMovementsTable">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Type</th>
                                            <th>Quantity</th>
                                            <th>Value</th>
                                            <th>Date</th>
                                            <th>Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="6" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stock Analytics Tab -->
        <?php if (in_array('stock', $allowed_report_tabs, true)): ?>
        <div class="tab-pane fade <?php echo $default_report_tab === 'stock' ? 'show active' : ''; ?>" id="stock" role="tabpanel">
            <div class="error-box" id="stockError"></div>
            <div class="loading-spinner" id="stockLoading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading stock analytics...</p>
            </div>
            
            <div id="stockContent">
                <!-- Stock Levels Chart -->
                <div class="chart-container">
                            <h5>Current Stock Levels</h5>
                    <canvas id="stockLevelsChart" height="400"></canvas>
                </div>

                <!-- ABC Analysis -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>ABC Analysis</h5>
                            <canvas id="abcAnalysisChart" height="300"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Stock Turnover Rates</h5>
                            <canvas id="turnoverChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Stock Details Table -->
                <div class="data-table">
                            <h5>Detailed Stock Report</h5>
                    <div class="table-responsive">
                        <table class="table table-hover" id="stockDetailsTable">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Min Stock</th>
                                    <th>Unit Cost</th>
                                    <th>Total Value</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="8" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Movement Reports Tab -->
        <?php if (in_array('movements', $allowed_report_tabs, true)): ?>
        <div class="tab-pane fade <?php echo $default_report_tab === 'movements' ? 'show active' : ''; ?>" id="movements" role="tabpanel">
            <div class="error-box" id="movementsError"></div>
            <div class="loading-spinner" id="movementsLoading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading movement reports...</p>
            </div>
            
            <div id="movementsContent">
                <!-- Movement Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-card success">
                            <div class="metric-value" id="totalIn">-</div>
                            <div class="metric-label">Total Stock In</div>
                            <div class="metric-value" id="totalInValue" style="font-size: 1rem; margin-top: 5px;">$0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value" id="totalOut">-</div>
                            <div class="metric-label">Total Stock Out</div>
                            <div class="metric-value" id="totalOutValue" style="font-size: 1rem; margin-top: 5px;">$0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card warning">
                            <div class="metric-value" id="totalAdjustments">-</div>
                            <div class="metric-label">Adjustments</div>
                            <div class="metric-value" id="totalAdjustmentsValue" style="font-size: 1rem; margin-top: 5px;">$0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card danger">
                            <div class="metric-value" id="totalWaste">-</div>
                            <div class="metric-label">Waste/Loss</div>
                            <div class="metric-value" id="totalWasteValue" style="font-size: 1rem; margin-top: 5px;">$0</div>
                        </div>
                    </div>
                </div>

                <!-- Movement Charts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Movement Trends Over Time</h5>
                            <canvas id="movementTrendsDetailedChart" height="300"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Movement by Category</h5>
                            <canvas id="movementByCategoryChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Movement Details Table -->
                <div class="data-table">
                            <h5>Movement History</h5>
                    <div class="table-responsive">
                        <table class="table table-hover" id="movementsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Unit Cost</th>
                                    <th>Total Value</th>
                                    <th>In Value</th>
                                    <th>Out Value</th>
                                    <th>Reason</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="8" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Loss Analysis Tab -->
        <?php if (in_array('loss', $allowed_report_tabs, true)): ?>
        <div class="tab-pane fade <?php echo $default_report_tab === 'loss' ? 'show active' : ''; ?>" id="loss" role="tabpanel">
            <div class="loading-spinner" id="lossLoading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading loss analysis...</p>
            </div>
            
            <div id="lossContent">
                <!-- Loss Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-card danger">
                            <div class="metric-value" id="totalLossValue">-</div>
                            <div class="metric-label">Total Loss Value</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card warning">
                            <div class="metric-value" id="totalQuantityLost">-</div>
                            <div class="metric-label">Quantity Lost</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value" id="totalIncidents">-</div>
                            <div class="metric-label">Total Incidents</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value" id="lossPercentage">-</div>
                            <div class="metric-label">Loss Percentage</div>
                        </div>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-12 text-center">
                        <div class="metric-card">
                            <div class="metric-value" id="avgLossPerItem">-</div>
                            <div class="metric-label">Average Loss per Item</div>
                        </div>
                    </div>
                </div>

                <!-- Loss Charts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Loss by Category</h5>
                            <canvas id="lossByCategoryChart" height="300"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Loss Trends Over Time</h5>
                            <canvas id="lossTrendsChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Loss Details Table -->
                <div class="data-table">
                            <h5>Loss Details</h5>
                    <div class="table-responsive">
                        <table class="table table-hover" id="lossDetailsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Loss Type</th>
                                    <th>Quantity Lost</th>
                                    <th>Unit Cost</th>
                                    <th>Value Lost</th>
                                    <th>Reason</th>
                                    <th>Reported By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Performance Tab -->
        <?php if (in_array('performance', $allowed_report_tabs, true)): ?>
        <div class="tab-pane fade <?php echo $default_report_tab === 'performance' ? 'show active' : ''; ?>" id="performance" role="tabpanel">
            <div class="loading-spinner" id="performanceLoading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading performance metrics...</p>
            </div>
            
            <div id="performanceContent">
                <!-- Performance Metrics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-card success">
                            <div class="metric-value" id="avgTurnoverRate">-</div>
                            <div class="metric-label">Avg Turnover Rate</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value" id="stockAccuracy">-</div>
                            <div class="metric-label">Stock Accuracy %</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card warning">
                            <div class="metric-value" id="carryingCost">-</div>
                            <div class="metric-label">Carrying Cost</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card danger">
                            <div class="metric-value" id="stockoutRate">-</div>
                            <div class="metric-label">Stockout Rate %</div>
                        </div>
                    </div>
                </div>

                <!-- Performance Charts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Turnover Rate Trends</h5>
                            <canvas id="turnoverTrendsChart" height="300"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Performance by Category</h5>
                            <canvas id="performanceByCategoryChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Performance Table -->
                <div class="data-table">
                            <h5>Performance Summary</h5>
                    <div class="table-responsive">
                        <table class="table table-hover" id="performanceTable">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Turnover Rate</th>
                                    <th>Stock Accuracy</th>
                                    <th>Carrying Cost</th>
                                    <th>Stockout Rate</th>
                                    <th>Performance Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Customer Reports Tab -->
        <?php if (in_array('customers', $allowed_report_tabs, true)): ?>
        <div class="tab-pane fade <?php echo $default_report_tab === 'customers' ? 'show active' : ''; ?>" id="customers" role="tabpanel">
            <div class="loading-spinner" id="customersLoading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading customer reports...</p>
            </div>
            
            <div id="customersContent">
                <!-- Customer Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-card success">
                            <div class="metric-value" id="totalCustomers">-</div>
                            <div class="metric-label">Total Customers</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value" id="activeSubscriptions">-</div>
                            <div class="metric-label">Active Subscriptions</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card warning">
                            <div class="metric-value" id="expiringSubscriptions">-</div>
                            <div class="metric-label">Expiring Soon</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card danger">
                            <div class="metric-value" id="paymentDue">-</div>
                            <div class="metric-label">Payment Due</div>
                        </div>
                    </div>
                </div>

                <!-- Customer Charts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Customer Distribution</h5>
                            <canvas id="customerDistributionChart" height="300"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Subscription Trends</h5>
                            <canvas id="subscriptionTrendsChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Customer Tables -->
                <div class="row">
                    <div class="col-lg-7">
                        <div class="data-table">
                            <h5>Active Subscriptions</h5>
                            <div class="table-responsive">
                                <table class="table table-hover" id="activeSubscriptionsTable">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Type</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Next Payment</th>
                                            <th>Status</th>
                                            <th>Days Left</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="7" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="data-table mb-4">
                            <h5>Recent Reservations</h5>
                            <div class="table-responsive">
                                <table class="table table-hover" id="recentReservationsTable">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Guests</th>
                                            <th>Value</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="6" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="data-table">
                            <h5>Expiring Soon (Next 7 Days)</h5>
                            <div id="expiringSoonList" class="list-group list-group-flush small">
                                <div class="text-center text-muted py-3">Loading...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<!-- Export Buttons -->
<div class="export-buttons">
    <button class="export-btn" onclick="exportToPDF()">PDF</button>
    <button class="export-btn" onclick="exportToExcel()">Excel</button>
</div>


<script>
// Global variables
let currentTab = '<?= $default_report_tab ?>';
let dateRange = {
    start: '<?= $start_date ?>',
    end: '<?= $end_date ?>'
};

// Store chart instances for proper cleanup
let chartInstances = {};

// Helper function to destroy charts before creating new ones
function destroyChart(chartId) {
    if (chartInstances[chartId]) {
        chartInstances[chartId].destroy();
        delete chartInstances[chartId];
    }
}

// Initialize date range picker
$(document).ready(function() {
    $('#dateRange').daterangepicker({
        startDate: moment('<?= $start_date ?>'),
        endDate: moment('<?= $end_date ?>'),
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    // Load initial data
    loadOverviewData();
    
    // Set up tab change listeners
    $('#reportTabs button').on('click', function() {
        const target = $(this).data('bs-target');
        currentTab = target.replace('#', '');
        
        // Load data for the selected tab
        switch(currentTab) {
            case 'overview':
                loadOverviewData();
                break;
            case 'stock':
                loadStockData();
                break;
            case 'movements':
                loadMovementsData();
                break;
            case 'loss':
                loadLossData();
                break;
            case 'performance':
                loadPerformanceData();
                break;
            case 'customers':
                loadCustomersData();
                break;
        }
    });
});

// Date filter functions
function applyDateFilter() {
    const range = $('#dateRange').data('daterangepicker');
    dateRange.start = range.startDate.format('YYYY-MM-DD');
    dateRange.end = range.endDate.format('YYYY-MM-DD');
    
    // Reload current tab data
    switch(currentTab) {
        case 'overview':
            loadOverviewData();
            break;
        case 'stock':
            loadStockData();
            break;
        case 'movements':
            loadMovementsData();
            break;
        case 'loss':
            loadLossData();
            break;
        case 'performance':
            loadPerformanceData();
            break;
        case 'customers':
            loadCustomersData();
            break;
    }
}

function resetDateFilter() {
    $('#dateRange').data('daterangepicker').setStartDate(moment('<?= $start_date ?>'));
    $('#dateRange').data('daterangepicker').setEndDate(moment('<?= $end_date ?>'));
    dateRange.start = '<?= $start_date ?>';
    dateRange.end = '<?= $end_date ?>';
    applyDateFilter();
}

function refreshAllReports() {
    applyDateFilter();
}

// Data loading functions
function loadOverviewData() {
    showLoading('overview');
    
    fetch('./src/services/reports/get_overview_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dateRange)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading('overview');
        updateOverviewMetrics(data);
        createOverviewCharts(data);
        updateRecentMovementsTable(data.recentMovements);
    })
    .catch(error => {
        hideLoading('overview');
        showError('overview', 'Failed to load overview data.');
    });
}

function loadStockData() {
    showLoading('stock');
    
    fetch('./src/services/reports/get_stock_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            ...dateRange,
            include_abc: true
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading('stock');
        createStockCharts(data);
        updateStockDetailsTable(data.stockItems);
    })
    .catch(error => {
        hideLoading('stock');
        showError('stock', 'Failed to load stock data.');
    });
}

function loadMovementsData() {
    showLoading('movements');
    
    fetch('./src/services/reports/get_movements_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dateRange)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading('movements');
        updateMovementMetrics(data);
        createMovementCharts(data);
        updateMovementsTable(data.movements);
    })
    .catch(error => {
        hideLoading('movements');
        showError('movements', 'Failed to load movements data.');
    });
}

function loadLossData() {
    showLoading('loss');
    
    fetch('./src/services/reports/get_loss_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dateRange)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading('loss');
        updateLossMetrics(data);
        createLossCharts(data);
        updateLossDetailsTable(data.losses);
    })
    .catch(error => {
        hideLoading('loss');
        console.error('Error loading loss data:', error);
    });
}

function loadPerformanceData() {
    showLoading('performance');
    
    fetch('./src/services/reports/get_performance_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dateRange)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading('performance');
        updatePerformanceMetrics(data);
        createPerformanceCharts(data);
        updatePerformanceTable(data.performance);
    })
    .catch(error => {
        hideLoading('performance');
        console.error('Error loading performance data:', error);
        showError('performance', 'Failed to load performance metrics.');
    });
}

function loadCustomersData() {
    showLoading('customers');
    
    fetch('./src/services/reports/get_customers_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dateRange)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading('customers');
        updateCustomerMetrics(data);
        createCustomerCharts(data);
        updateCustomerTables(data);
    })
    .catch(error => {
        hideLoading('customers');
        console.error('Error loading customers data:', error);
        showError('customers', 'Failed to load customer insights.');
    });
}

// Loading state functions
function showLoading(tab) {
    document.getElementById(tab + 'Loading').style.display = 'block';
    document.getElementById(tab + 'Content').style.display = 'none';
    const err = document.getElementById(tab + 'Error');
    if (err) err.style.display = 'none';
}

function hideLoading(tab) {
    document.getElementById(tab + 'Loading').style.display = 'none';
    document.getElementById(tab + 'Content').style.display = 'block';
}

function showError(tab, message) {
    const box = document.getElementById(tab + 'Error');
    if (box) {
        box.textContent = message || 'An error occurred while loading data.';
        box.style.display = 'block';
    }
}

// Export functions
function exportCurrentReport(format = 'pdf') {
    const exportUrls = {
        'overview': {
            'pdf': './src/services/reports/export_overview_pdf.php',
            'excel': './src/services/reports/export_overview_excel.php'
        },
        'stock': {
            'pdf': './src/services/reports/export_stock_pdf.php',
            'excel': './src/services/reports/export_stock_excel.php'
        },
        'movements': {
            'pdf': './src/services/reports/export_movements_pdf.php',
            'excel': './src/services/reports/export_movements_excel.php'
        },
        'loss': {
            'pdf': './src/services/reports/export_loss_pdf.php',
            'excel': './src/services/reports/export_loss_excel.php'
        },
        'performance': {
            'pdf': './src/services/reports/export_performance_pdf.php',
            'excel': './src/services/reports/export_performance_excel.php'
        },
        'customers': {
            'pdf': './src/services/reports/export_customers_pdf.php',
            'excel': './src/services/reports/export_customers_excel.php'
        }
    };
    
    const baseUrl = exportUrls[currentTab]?.[format] || exportUrls['overview']['pdf'];
    const url = `${baseUrl}?start_date=${dateRange.start}&end_date=${dateRange.end}`;
    
    // Create form and submit for download
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = baseUrl;
    form.style.display = 'none';
    
    const startDateInput = document.createElement('input');
    startDateInput.type = 'hidden';
    startDateInput.name = 'start';
    startDateInput.value = dateRange.start;
    form.appendChild(startDateInput);
    
    const endDateInput = document.createElement('input');
    endDateInput.type = 'hidden';
    endDateInput.name = 'end';
    endDateInput.value = dateRange.end;
    form.appendChild(endDateInput);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function exportToPDF() {
    exportCurrentReport('pdf');
}

function exportToExcel() {
    exportCurrentReport('excel');
}

// Formatting helpers
function formatNumber(value, decimals = 0) {
    if (value === undefined || value === null || isNaN(value)) return '0';
    return Number(value).toLocaleString(undefined, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function formatCurrency(value, decimals = 2) {
    return '$' + formatNumber(value, decimals);
}

function formatDate(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (isNaN(date)) return value;
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

// Chart creation functions will be implemented in the next part
function updateOverviewMetrics(data) {
    document.getElementById('totalItems').textContent = data.totalItems !== undefined ? formatNumber(data.totalItems) : '-';
    document.getElementById('lowStockItems').textContent = data.lowStockItems !== undefined ? formatNumber(data.lowStockItems) : '-';
    document.getElementById('totalValue').textContent = data.totalValue !== undefined ? formatCurrency(data.totalValue) : '-';
    document.getElementById('expiringItems').textContent = data.expiringItems !== undefined ? formatNumber(data.expiringItems) : '-';
}

function createOverviewCharts(data) {
    // Destroy existing charts first
    destroyChart('stockValueChart');
    destroyChart('movementTrendsChart');
    
    // Stock Value by Category Chart
    const stockValueCtx = document.getElementById('stockValueChart').getContext('2d');
    chartInstances['stockValueChart'] = new Chart(stockValueCtx, {
        type: 'doughnut',
        data: {
            labels: data.stockValueByCategory?.labels || [],
            datasets: [{
                data: data.stockValueByCategory?.data || [],
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
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

    // Movement Trends Chart
    const movementTrendsCtx = document.getElementById('movementTrendsChart').getContext('2d');
    chartInstances['movementTrendsChart'] = new Chart(movementTrendsCtx, {
        type: 'line',
        data: {
            labels: data.movementTrends?.labels || [],
            datasets: [{
                label: 'Stock In',
                data: data.movementTrends?.stockIn || [],
                borderColor: '#36A2EB',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4
            }, {
                label: 'Stock Out',
                data: data.movementTrends?.stockOut || [],
                borderColor: '#FF6384',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function updateRecentMovementsTable(movements) {
    const tbody = document.querySelector('#recentMovementsTable tbody');
    if (!movements || movements.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No recent movements</td></tr>';
        return;
    }

    tbody.innerHTML = movements.map(movement => `
        <tr>
            <td>${movement.item_name}</td>
            <td><span class="badge bg-${getMovementTypeColor(movement.movement_type)}">${movement.movement_type}</span></td>
            <td>${movement.quantity}</td>
            <td>$${movement.total_cost}</td>
            <td>${new Date(movement.movement_date).toLocaleDateString()}</td>
            <td>${movement.reason}</td>
        </tr>
    `).join('');
}

function getMovementTypeColor(type) {
    switch(type) {
        case 'in': return 'success';
        case 'out': return 'primary';
        case 'adjustment': return 'warning';
        case 'waste': return 'danger';
        default: return 'secondary';
    }
}

// Stock Analytics Functions
function createStockCharts(data) {
    // Destroy existing charts first
    destroyChart('stockLevelsChart');
    destroyChart('abcAnalysisChart');
    destroyChart('turnoverChart');
    
    // Stock Levels Chart
    const stockLevelsCtx = document.getElementById('stockLevelsChart').getContext('2d');
    chartInstances['stockLevelsChart'] = new Chart(stockLevelsCtx, {
        type: 'bar',
        data: {
            labels: data.stockLevels?.labels || [],
            datasets: [{
                label: 'Current Stock',
                data: data.stockLevels?.currentStock || [],
                backgroundColor: '#36A2EB',
                borderColor: '#36A2EB',
                borderWidth: 1
            }, {
                label: 'Minimum Stock',
                data: data.stockLevels?.minimumStock || [],
                backgroundColor: '#FF6384',
                borderColor: '#FF6384',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // ABC Analysis Chart
    const abcCtx = document.getElementById('abcAnalysisChart').getContext('2d');
    chartInstances['abcAnalysisChart'] = new Chart(abcCtx, {
        type: 'doughnut',
        data: {
            labels: ['A Items (High Value)', 'B Items (Medium Value)', 'C Items (Low Value)'],
            datasets: [{
                data: [
                    data.abcAnalysis?.A?.data?.length || 0,
                    data.abcAnalysis?.B?.data?.length || 0,
                    data.abcAnalysis?.C?.data?.length || 0
                ],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
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

    // Turnover Rates Chart
    const turnoverCtx = document.getElementById('turnoverChart').getContext('2d');
    chartInstances['turnoverChart'] = new Chart(turnoverCtx, {
        type: 'bar',
        data: {
            labels: data.turnoverRates?.labels || [],
            datasets: [{
                label: 'Turnover Rate',
                data: data.turnoverRates?.turnoverRates || [],
                backgroundColor: '#4BC0C0',
                borderColor: '#4BC0C0',
                borderWidth: 1
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
                    beginAtZero: true
                }
            }
        }
    });
}

function updateStockDetailsTable(data) {
    const tbody = document.querySelector('#stockDetailsTable tbody');
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No stock items found</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(item => `
        <tr>
            <td>${item.item_name}</td>
            <td>${item.category_name}</td>
            <td>${item.current_stock} ${item.unit_of_measure}</td>
            <td>${item.minimum_stock} ${item.unit_of_measure}</td>
            <td>$${item.unit_cost}</td>
            <td>$${item.total_value}</td>
            <td><span class="badge bg-${getStockStatusColor(item.status)}">${item.status}</span></td>
            <td>${item.last_restocked ? new Date(item.last_restocked).toLocaleDateString() : 'Never'}</td>
        </tr>
    `).join('');
}

function getStockStatusColor(status) {
    switch(status) {
        case 'Low Stock': return 'danger';
        case 'High Stock': return 'success';
        case 'Expiring Soon': return 'warning';
        default: return 'primary';
    }
}

function updateMovementMetrics(data) {
    const summary = data.movementSummary || {};
    document.getElementById('totalIn').textContent = formatNumber(summary.totalIn);
    document.getElementById('totalOut').textContent = formatNumber(summary.totalOut);
    document.getElementById('totalAdjustments').textContent = formatNumber(summary.totalAdjustments);
    document.getElementById('totalWaste').textContent = formatNumber(summary.totalWaste);
    document.getElementById('totalInValue').textContent = formatCurrency(summary.totalInValue);
    document.getElementById('totalOutValue').textContent = formatCurrency(summary.totalOutValue);
    document.getElementById('totalAdjustmentsValue').textContent = formatCurrency(summary.totalAdjustmentsValue);
    document.getElementById('totalWasteValue').textContent = formatCurrency(summary.totalWasteValue);
}

function createMovementCharts(data) {
    // Destroy existing charts first
    destroyChart('movementTrendsDetailedChart');
    destroyChart('movementByCategoryChart');
    
    // Movement Trends Chart (quantities on left axis, values on right axis)
    const trendsCtx = document.getElementById('movementTrendsDetailedChart').getContext('2d');
    chartInstances['movementTrendsDetailedChart'] = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: data.movementTrends?.labels || [],
            datasets: [{
                label: 'Stock In (Qty)',
                data: data.movementTrends?.stockIn || [],
                borderColor: '#36A2EB',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Stock Out (Qty)',
                data: data.movementTrends?.stockOut || [],
                borderColor: '#FF6384',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Adjustments (Qty)',
                data: data.movementTrends?.adjustments || [],
                borderColor: '#FFCE56',
                backgroundColor: 'rgba(255, 206, 86, 0.1)',
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Waste (Qty)',
                data: data.movementTrends?.waste || [],
                borderColor: '#FF9F40',
                backgroundColor: 'rgba(255, 159, 64, 0.1)',
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Stock In (Value)',
                data: data.movementTrends?.stockInValue || [],
                borderColor: '#2c3e50',
                backgroundColor: 'rgba(44, 62, 80, 0.1)',
                borderDash: [5, 5],
                tension: 0.4,
                yAxisID: 'y1'
            }, {
                label: 'Stock Out (Value)',
                data: data.movementTrends?.stockOutValue || [],
                borderColor: '#8e44ad',
                backgroundColor: 'rgba(142, 68, 173, 0.1)',
                borderDash: [5, 5],
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Quantity' }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Value' }
                }
            }
        }
    });

    // Movement by Category Chart (qty and value)
    const movementByCategory = data.movementByCategory || {};
    const categories = movementByCategory.categories || [];
    const buildQuantitySeries = (type) => categories.map(category => (movementByCategory.data?.[type]?.[category] ?? 0));
    const buildValueSeries = (type) => categories.map(category => (movementByCategory.valueData?.[type]?.[category] ?? 0));

    const categoryCtx = document.getElementById('movementByCategoryChart').getContext('2d');
    chartInstances['movementByCategoryChart'] = new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: categories,
            datasets: [{
                label: 'Stock In (Qty)',
                data: buildQuantitySeries('in'),
                backgroundColor: 'rgba(54,162,235,0.6)',
                yAxisID: 'y'
            }, {
                label: 'Stock Out (Qty)',
                data: buildQuantitySeries('out'),
                backgroundColor: 'rgba(255,99,132,0.6)',
                yAxisID: 'y'
            }, {
                label: 'Adjustments (Qty)',
                data: buildQuantitySeries('adjustment'),
                backgroundColor: 'rgba(255,206,86,0.6)',
                yAxisID: 'y'
            }, {
                label: 'Waste (Qty)',
                data: buildQuantitySeries('waste'),
                backgroundColor: 'rgba(255,159,64,0.6)',
                yAxisID: 'y'
            }, {
                type: 'line',
                label: 'Stock In (Value)',
                data: buildValueSeries('in'),
                borderColor: '#2c3e50',
                backgroundColor: 'transparent',
                tension: 0.3,
                yAxisID: 'y1'
            }, {
                type: 'line',
                label: 'Stock Out (Value)',
                data: buildValueSeries('out'),
                borderColor: '#8e44ad',
                backgroundColor: 'transparent',
                tension: 0.3,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Quantity' }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Value' }
                }
            }
        }
    });
}

function updateMovementsTable(data) {
    const tbody = document.querySelector('#movementsTable tbody');
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" class="text-center">No movements found</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(movement => `
        <tr>
            <td>${new Date(movement.movement_date).toLocaleDateString()}</td>
            <td>${movement.item_name}</td>
            <td>${movement.category_name}</td>
            <td><span class="badge bg-${getMovementTypeColor(movement.movement_type)}">${movement.movement_type}</span></td>
            <td>${movement.quantity} ${movement.unit_of_measure}</td>
            <td>$${movement.unit_cost}</td>
            <td>$${movement.total_cost}</td>
            <td>${movement.movement_type === 'in' ? `$${movement.total_cost}` : '$0'}</td>
            <td>${movement.movement_type === 'out' ? `$${movement.total_cost}` : '$0'}</td>
            <td>${movement.reason || '-'}</td>
            <td>${movement.user_name || 'System'}</td>
        </tr>
    `).join('');
}

function updateLossMetrics(data) {
    const summary = data.lossSummary || {};
    document.getElementById('totalLossValue').textContent = formatCurrency(summary.totalLossValue);
    document.getElementById('totalQuantityLost').textContent = formatNumber(summary.totalQuantityLost);
    document.getElementById('totalIncidents').textContent = formatNumber(summary.totalIncidents);
    document.getElementById('lossPercentage').textContent = summary.lossPercentage ? `${formatNumber(summary.lossPercentage, 2)}%` : '0%';
    document.getElementById('avgLossPerItem').textContent = formatCurrency(summary.avgLossPerItem);
}

function createLossCharts(data) {
    // Destroy existing charts first
    destroyChart('lossByCategoryChart');
    destroyChart('lossTrendsChart');
    
    // Loss by Category Chart
    const categoryCtx = document.getElementById('lossByCategoryChart').getContext('2d');
    chartInstances['lossByCategoryChart'] = new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: data.lossByCategory?.categories || [],
            datasets: [{
                label: 'Waste',
                data: data.lossByCategory?.waste || [],
                backgroundColor: '#FF6384'
            }, {
                label: 'Adjustments',
                data: data.lossByCategory?.adjustments || [],
                backgroundColor: '#FFCE56'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Loss Trends Chart
    const trendsCtx = document.getElementById('lossTrendsChart').getContext('2d');
    chartInstances['lossTrendsChart'] = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: data.lossTrends?.labels || [],
            datasets: [{
                label: 'Waste',
                data: data.lossTrends?.waste || [],
                borderColor: '#FF6384',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4
            }, {
                label: 'Adjustments',
                data: data.lossTrends?.adjustments || [],
                borderColor: '#FFCE56',
                backgroundColor: 'rgba(255, 206, 86, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function updateLossDetailsTable(data) {
    const tbody = document.querySelector('#lossDetailsTable tbody');
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No loss incidents found</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(loss => `
        <tr>
            <td>${new Date(loss.movement_date).toLocaleDateString()}</td>
            <td>${loss.item_name}</td>
            <td>${loss.category_name}</td>
            <td><span class="badge bg-${getLossTypeColor(loss.loss_type)}">${loss.loss_type}</span></td>
            <td>${loss.quantity_lost} ${loss.unit_of_measure}</td>
            <td>$${loss.unit_cost}</td>
            <td>$${loss.value_lost}</td>
            <td>${loss.reason || '-'}</td>
            <td>${loss.user_name || 'System'}</td>
        </tr>
    `).join('');
}

function getLossTypeColor(type) {
    switch(type) {
        case 'waste': return 'danger';
        case 'adjustment': return 'warning';
        default: return 'secondary';
    }
}

function updatePerformanceMetrics(data) {
    const metrics = data.performanceMetrics || {};
    document.getElementById('avgTurnoverRate').textContent = formatNumber(metrics.avgTurnoverRate, 2);
    document.getElementById('stockAccuracy').textContent = metrics.stockAccuracy ? `${formatNumber(metrics.stockAccuracy, 2)}%` : '0%';
    document.getElementById('carryingCost').textContent = formatCurrency(metrics.carryingCost);
    document.getElementById('stockoutRate').textContent = metrics.stockoutRate ? `${formatNumber(metrics.stockoutRate, 2)}%` : '0%';
}

function createPerformanceCharts(data) {
    // Destroy existing charts first
    destroyChart('turnoverTrendsChart');
    destroyChart('performanceByCategoryChart');
    
    // Turnover Trends Chart
    const turnoverCtx = document.getElementById('turnoverTrendsChart').getContext('2d');
    chartInstances['turnoverTrendsChart'] = new Chart(turnoverCtx, {
        type: 'line',
        data: {
            labels: data.turnoverTrends?.labels || [],
            datasets: [{
                label: 'Turnover Rate',
                data: data.turnoverTrends?.turnoverRates || [],
                borderColor: '#36A2EB',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Performance by Category Chart
    const categoryCtx = document.getElementById('performanceByCategoryChart').getContext('2d');
    chartInstances['performanceByCategoryChart'] = new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: data.performanceByCategory?.categories || [],
            datasets: [{
                label: 'Turnover Rate',
                data: data.performanceByCategory?.turnoverRates || [],
                backgroundColor: '#36A2EB'
            }, {
                label: 'Stock Accuracy',
                data: data.performanceByCategory?.accuracyRates || [],
                backgroundColor: '#4BC0C0'
            }, {
                label: 'Carrying Cost',
                data: data.performanceByCategory?.carryingCosts || [],
                backgroundColor: '#FFCE56'
            }, {
                label: 'Stockout Risk',
                data: data.performanceByCategory?.stockoutRisks || [],
                backgroundColor: '#FF6384'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function updatePerformanceTable(data) {
    const tbody = document.querySelector('#performanceTable tbody');
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No performance data found</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(perf => `
        <tr>
            <td>${perf.category_name}</td>
            <td>${perf.total_items}</td>
            <td>${perf.turnover_rate}</td>
            <td>${perf.stock_accuracy}%</td>
            <td>$${perf.carrying_cost}</td>
            <td>${perf.stockout_rate}%</td>
            <td><span class="badge bg-${getPerformanceScoreColor(perf.performance_score)}">${perf.performance_score}</span></td>
        </tr>
    `).join('');
}

function getPerformanceScoreColor(score) {
    if (score >= 80) return 'success';
    if (score >= 60) return 'warning';
    return 'danger';
}

function updateCustomerMetrics(data) {
    const summary = data.customerSummary || {};
    document.getElementById('totalCustomers').textContent = formatNumber(summary.totalCustomers);
    document.getElementById('activeSubscriptions').textContent = formatNumber(summary.activeSubscriptions);
    document.getElementById('expiringSubscriptions').textContent = formatNumber(summary.expiringSubscriptions);
    document.getElementById('paymentDue').textContent = formatNumber(summary.paymentDue);
}

function createCustomerCharts(data) {
    // Destroy existing charts first
    destroyChart('customerDistributionChart');
    destroyChart('subscriptionTrendsChart');
    
    // Customer Distribution Chart
    const distributionCtx = document.getElementById('customerDistributionChart').getContext('2d');
    chartInstances['customerDistributionChart'] = new Chart(distributionCtx, {
        type: 'doughnut',
        data: {
            labels: data.customerDistribution?.labels || [],
            datasets: [{
                data: data.customerDistribution?.data || [],
                backgroundColor: data.customerDistribution?.colors || ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
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

    // Subscription Trends Chart
    const trendsCtx = document.getElementById('subscriptionTrendsChart').getContext('2d');
    chartInstances['subscriptionTrendsChart'] = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: data.subscriptionTrends?.labels || [],
            datasets: [{
                label: 'New Subscriptions',
                data: data.subscriptionTrends?.newSubscriptions || [],
                borderColor: '#36A2EB',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4
            }, {
                label: 'Active Subscriptions',
                data: data.subscriptionTrends?.activeSubscriptions || [],
                borderColor: '#4BC0C0',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function updateCustomerTables(data) {
    // Active Subscriptions Table
    const subscriptionsTbody = document.querySelector('#activeSubscriptionsTable tbody');
    if (!data.activeSubscriptions || data.activeSubscriptions.length === 0) {
        subscriptionsTbody.innerHTML = '<tr><td colspan="7" class="text-center">No active subscriptions found</td></tr>';
    } else {
        subscriptionsTbody.innerHTML = data.activeSubscriptions.map(sub => `
            <tr>
                <td>
                    <div class="fw-semibold">${sub.customer_name}</div>
                    <div class="text-muted small">${sub.email}</div>
                </td>
                <td>${sub.subscription_type || '—'}</td>
                <td>${formatDate(sub.start_date)}</td>
                <td>${formatDate(sub.end_date)}</td>
                <td>${sub.next_payment_date ? formatDate(sub.next_payment_date) : '—'}</td>
                <td><span class="badge bg-${getSubscriptionStatusColor(sub.status)} text-capitalize">${sub.status}</span></td>
                <td>${sub.days_remaining !== null && sub.days_remaining !== undefined ? sub.days_remaining + ' days' : '—'}</td>
            </tr>
        `).join('');
    }

    // Recent Reservations Table
    const reservationsTbody = document.querySelector('#recentReservationsTable tbody');
    if (!data.recentReservations || data.recentReservations.length === 0) {
        reservationsTbody.innerHTML = '<tr><td colspan="6" class="text-center">No recent reservations found</td></tr>';
    } else {
        reservationsTbody.innerHTML = data.recentReservations.map(res => `
            <tr>
                <td>
                    <div class="fw-semibold">${res.customer_name}</div>
                    <div class="text-muted small">${res.email}</div>
                </td>
                <td>${formatDate(res.reservation_date)}</td>
                <td>${res.reservation_time}</td>
                <td>${res.number_of_guests}</td>
                <td>${formatCurrency(res.total_amount)}</td>
                <td><span class="badge bg-${getReservationStatusColor(res.status)} text-capitalize">${res.status}</span></td>
            </tr>
        `).join('');
    }

    // Expiring soon list
    const expiringSoonList = document.getElementById('expiringSoonList');
    if (expiringSoonList) {
        const expiringSoon = (data.activeSubscriptions || [])
            .filter(sub => sub.days_remaining !== null && sub.days_remaining !== undefined && sub.days_remaining >= 0 && sub.days_remaining <= 7)
            .sort((a, b) => a.days_remaining - b.days_remaining)
            .slice(0, 6);

        if (expiringSoon.length === 0) {
            expiringSoonList.innerHTML = '<div class="text-center text-muted py-3">No subscriptions expiring within 7 days</div>';
        } else {
            expiringSoonList.innerHTML = expiringSoon.map(sub => `
                <div class="list-group-item d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold">${sub.customer_name}</div>
                        <div class="text-muted small">${sub.subscription_type || '—'} &bull; Ends ${formatDate(sub.end_date)}</div>
                    </div>
                    <span class="badge rounded-pill bg-${sub.days_remaining <= 2 ? 'danger' : 'warning'}">${sub.days_remaining}d</span>
                </div>
            `).join('');
        }
    }
}

function getSubscriptionStatusColor(status) {
    switch(status) {
        case 'active': return 'success';
        case 'expired': return 'danger';
        case 'cancelled': return 'secondary';
        case 'pending': return 'warning';
        default: return 'secondary';
    }
}

function getReservationStatusColor(status) {
    switch(status) {
        case 'confirmed': return 'success';
        case 'pending': return 'warning';
        case 'cancelled': return 'danger';
        case 'completed': return 'primary';
        default: return 'secondary';
    }
}
</script>
