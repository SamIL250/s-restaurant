<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Customers</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Customers</h2>
        </div>
        <div class="col-auto ms-auto">
            <a href="customers-subscriptions.php" class="btn btn-outline-info me-2">
                <span class="fas fa-credit-card me-2"></span>Subscriptions
            </a>
            <a href="customers-subscriptions-types.php" class="btn btn-outline-secondary me-2">
                <span class="fas fa-cog me-2"></span>Subscription Types
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                <span class="fas fa-plus me-2"></span>Add Customer
            </button>
        </div>
    </div>
    <?php
    if (!empty($_SESSION['notification'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['notification']) . '</div>';
        unset($_SESSION['notification']);
    }
    ?>
    <?php
    // Count customers by status
    $all_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM customers WHERE deleted_at IS NULL"))['cnt'];
    $active_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM customers WHERE deleted_at IS NULL AND has_subscription = 1 AND subscription_status = 'active'"))['cnt'];
    $expired_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM customers WHERE deleted_at IS NULL AND subscription_status = 'expired'"))['cnt'];
    $no_subscription_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM customers WHERE deleted_at IS NULL AND (has_subscription = 0 OR subscription_status IS NULL)"))['cnt'];
    $deleted_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM customers WHERE deleted_at IS NOT NULL"))['cnt'];
    ?>
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3" id="customer-filters">
        <li class="nav-item">
            <a class="nav-link active" data-filter="all" aria-current="page" href="#">
                All <span class="text-body-tertiary fw-semibold">(<?= $all_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="active" href="#">
                Active Subscriptions <span class="text-body-tertiary fw-semibold">(<?= $active_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="expired" href="#">
                Expired <span class="text-body-tertiary fw-semibold">(<?= $expired_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="no_subscription" href="#">
                No Subscription <span class="text-body-tertiary fw-semibold">(<?= $no_subscription_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="deleted" href="#">
                Deleted <span class="text-body-tertiary fw-semibold">(<?= $deleted_count ?>)</span>
            </a>
        </li>
    </ul>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="search-box">
                <form class="position-relative" data-bs-toggle="search" data-bs-display="static">
                    <input class="form-control search-input fuzzy-search" type="search" placeholder="Search customers..." aria-label="Search" id="customerSearch">
                    <span class="fas fa-search search-box-icon"></span>
                </form>
            </div>
        </div>
    </div>
    <div class="table-responsive bg-body-emphasis border-top border-bottom border-translucent position-relative top-1 p-3">
        <table class="table fs-9 mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Subscription Status</th>
                    <th>Subscription Type</th>
                    <th>Expiry Date</th>
                    <th>Loyalty Points</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="customers-table-body">
                <?php
                $customers_query = mysqli_query($conn, "
                    SELECT c.*, st.type_name, st.price,
                           CASE WHEN c.deleted_at IS NOT NULL THEN 'deleted'
                                WHEN c.has_subscription = 1 AND c.subscription_status = 'active' THEN 'active'
                                WHEN c.subscription_status = 'expired' THEN 'expired'
                                ELSE 'no_subscription' END as display_status
                    FROM customers c 
                    LEFT JOIN subscription_types st ON c.subscription_type_id = st.subscription_type_id 
                    ORDER BY c.deleted_at IS NOT NULL DESC, c.first_name, c.last_name
                ");
                
                // Get subscription types for all modals
                $subscription_types = mysqli_query($conn, "SELECT * FROM subscription_types WHERE is_active = 1 ORDER BY price ASC");
                
                $has_customers = false;
                while ($row = mysqli_fetch_assoc($customers_query)) {
                    $has_customers = true;
                    $full_name = trim($row['first_name'] . ' ' . $row['last_name']);
                    if (empty($full_name)) $full_name = 'Anonymous Customer';
                    
                    // Subscription status badge
                    $subscription_badge = '';
                    if ($row['has_subscription'] && $row['subscription_status'] === 'active') {
                        if ($row['subscription_end_date'] && strtotime($row['subscription_end_date']) < strtotime('+7 days')) {
                            $subscription_badge = '<span class="badge bg-warning-subtle text-warning">Expiring Soon</span>';
                        } else {
                            $subscription_badge = '<span class="badge bg-success-subtle text-success">Active</span>';
                        }
                    } elseif ($row['subscription_status'] === 'expired') {
                        $subscription_badge = '<span class="badge bg-danger-subtle text-danger">Expired</span>';
                    } elseif ($row['subscription_status'] === 'cancelled') {
                        $subscription_badge = '<span class="badge bg-secondary-subtle text-secondary">Cancelled</span>';
                    } else {
                        $subscription_badge = '<span class="badge bg-light-subtle text-secondary">No Subscription</span>';
                    }
                    
                    // Subscription type display
                    $subscription_type = $row['type_name'] ?? '-';
                    $expiry_date = $row['subscription_end_date'] ? date('M d, Y', strtotime($row['subscription_end_date'])) : '-';
                ?>
                <tr data-customer-id="<?= (int)$row['customer_id'] ?>" data-status="<?= $row['display_status'] ?>">
                    <td><?= htmlspecialchars($full_name) ?></td>
                    <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                    <td><?= $subscription_badge ?></td>
                    <td><?= htmlspecialchars($subscription_type) ?></td>
                    <td><?= $expiry_date ?></td>
                    <td><span class="badge bg-primary-subtle text-primary"><?= (int)$row['loyalty_points'] ?></span></td>
                    <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                    <td class="text-end">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary me-1" title="Edit" data-bs-toggle="modal" data-bs-target="#editCustomerModal<?= (int)$row['customer_id'] ?>"><i class="fa fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-info me-1" title="View Details" data-bs-toggle="modal" data-bs-target="#customerDetailsModal<?= (int)$row['customer_id'] ?>"><i class="fa fa-eye"></i></button>
                            <?php if ($row['has_subscription']): ?>
                            <button class="btn btn-sm btn-outline-warning me-1" title="Manage Subscription" onclick="openManageSubscriptionModal(<?= (int)$row['customer_id'] ?>)"><i class="fa fa-credit-card"></i></button>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-success me-1" title="Add Subscription" onclick="openAddSubscriptionModal(<?= (int)$row['customer_id'] ?>)"><i class="fa fa-plus"></i></button>
                            <?php endif; ?>
                            <form method="POST" action="src/services/customers/delete_customer.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                <input type="hidden" name="customer_id" value="<?= (int)$row['customer_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fa fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <!-- Edit Modal for this customer -->
                <div class="modal fade" id="editCustomerModal<?= (int)$row['customer_id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form action="src/services/customers/edit_customer.php" method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Customer</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="customer_id" value="<?= (int)$row['customer_id'] ?>">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($row['first_name']) ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($row['last_name']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($row['email']) ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone</label>
                                            <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($row['phone']) ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Subscription Type <span class="text-danger">*</span></label>
                                            <select class="form-control" name="subscription_type_id" required>
                                                <option value="">Select Subscription Type</option>
                                                <?php
                                                    mysqli_data_seek($subscription_types, 0);
                                                    while ($type = mysqli_fetch_assoc($subscription_types)) {
                                                        $selected = ($type['subscription_type_id'] == $row['subscription_type_id']) ? 'selected' : '';
                                                        echo '<option value="' . $type['subscription_type_id'] . '" ' . $selected . '>' . htmlspecialchars($type['type_name']) . ' - $' . number_format($type['price'], 2) . '</option>';
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                            <select class="form-control" name="payment_method" required>
                                                <option value="">Select Payment Method</option>
                                                <option value="cash" <?= ($row['payment_method'] == 'cash') ? 'selected' : '' ?>>Cash</option>
                                                <option value="card" <?= ($row['payment_method'] == 'card') ? 'selected' : '' ?>>Card</option>
                                                <option value="mobile_money" <?= ($row['payment_method'] == 'mobile_money') ? 'selected' : '' ?>>Mobile Money</option>
                                                <option value="bank_transfer" <?= ($row['payment_method'] == 'bank_transfer') ? 'selected' : '' ?>>Bank Transfer</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="auto_renewal" id="editAutoRenewal<?= (int)$row['customer_id'] ?>" <?= ($row['auto_renewal'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="editAutoRenewal<?= (int)$row['customer_id'] ?>">Auto-renewal</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Customer Details Modal -->
                <div class="modal fade" id="customerDetailsModal<?= (int)$row['customer_id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Customer Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Name:</strong> <?= htmlspecialchars($full_name) ?></p>
                                        <p><strong>Email:</strong> <?= htmlspecialchars($row['email'] ?? 'Not provided') ?></p>
                                        <p><strong>Phone:</strong> <?= htmlspecialchars($row['phone'] ?? 'Not provided') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Subscription Type:</strong> <?= htmlspecialchars($subscription_type) ?></p>
                                        <p><strong>Subscription Status:</strong> <?= $subscription_badge ?></p>
                                        <p><strong>Expiry Date:</strong> <?= $expiry_date ?></p>
                                        <p><strong>Loyalty Points:</strong> <?= (int)$row['loyalty_points'] ?></p>
                                        <p><strong>Joined:</strong> <?= date('M d, Y', strtotime($row['created_at'])) ?></p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <p><strong>Payment Method:</strong> <?= ucfirst(htmlspecialchars($row['payment_method'] ?? 'Not specified')) ?></p>
                                    <p><strong>Auto-Renewal:</strong> <?= ($row['auto_renewal'] == 1) ? 'Yes' : 'No' ?></p>
                                    <?php if ($row['last_payment_date']): ?>
                                    <p><strong>Last Payment:</strong> <?= date('M d, Y', strtotime($row['last_payment_date'])) ?></p>
                                    <?php endif; ?>
                                    <?php if ($row['next_payment_date']): ?>
                                    <p><strong>Next Payment:</strong> <?= date('M d, Y', strtotime($row['next_payment_date'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php }
                if (!$has_customers): ?>
                <tr><td colspan="9"><div class='alert alert-warning text-center p-2 rounded-2 mt-2 mb-2'>No customers found</div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="src/services/customers/add_customer.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Customer with Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subscription Type <span class="text-danger">*</span></label>
                            <select class="form-control" name="subscription_type_id" required>
                                <option value="">Select Subscription Type</option>
                                <?php
                                $subscription_types = mysqli_query($conn, "SELECT * FROM subscription_types WHERE is_active = 1 ORDER BY price ASC");
                                while ($type = mysqli_fetch_assoc($subscription_types)) {
                                    echo '<option value="' . $type['subscription_type_id'] . '">' . htmlspecialchars($type['type_name']) . ' - $' . number_format($type['price'], 2) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-control" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="auto_renewal" id="autoRenewal">
                                <label class="form-check-label" for="autoRenewal">Auto-renewal</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Customer with Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Subscription Modal Template -->
<div class="modal fade" id="addSubscriptionModalTemplate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="src/services/customers/manage_subscription.php" method="POST">
                <input type="hidden" name="action" value="add_subscription">
                <input type="hidden" name="customer_id" id="addSubCustomerId">
                <div class="modal-header">
                    <h5 class="modal-title">Add Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subscription Type</label>
                            <select class="form-control" name="subscription_type_id" required>
                                <option value="">Select Subscription Type</option>
                                <?php
                                $subscription_types = mysqli_query($conn, "SELECT * FROM subscription_types WHERE is_active = 1 ORDER BY price ASC");
                                while ($type = mysqli_fetch_assoc($subscription_types)) {
                                    echo '<option value="' . $type['subscription_type_id'] . '">' . htmlspecialchars($type['type_name']) . ' - $' . number_format($type['price'], 2) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-control" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="auto_renewal" id="autoRenewal">
                                <label class="form-check-label" for="autoRenewal">Auto-renewal</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Manage Subscription Modal Template -->
<div class="modal fade" id="subscriptionModalTemplate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="src/services/customers/manage_subscription.php" method="POST">
                <input type="hidden" name="action" value="renew_subscription">
                <input type="hidden" name="customer_id" id="manageSubCustomerId">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subscription Type</label>
                            <select class="form-control" name="subscription_type_id" required>
                                <option value="">Select Subscription Type</option>
                                <?php
                                mysqli_data_seek($subscription_types, 0);
                                while ($type = mysqli_fetch_assoc($subscription_types)) {
                                    echo '<option value="' . $type['subscription_type_id'] . '">' . htmlspecialchars($type['type_name']) . ' - $' . number_format($type['price'], 2) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-control" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="auto_renewal" id="manageAutoRenewal">
                                <label class="form-check-label" for="manageAutoRenewal">Auto-renewal</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <button type="button" class="btn btn-outline-danger" onclick="cancelSubscription()">Cancel Subscription</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Renew Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden form for subscription cancellation -->
<form id="cancelSubscriptionForm" action="src/services/customers/manage_subscription.php" method="POST" style="display:none;">
    <input type="hidden" name="action" value="cancel_subscription">
    <input type="hidden" name="customer_id" id="cancelSubCustomerId">
    <input type="hidden" name="reason" value="Customer requested cancellation">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality for customers
    document.querySelectorAll('#customer-filters .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#customer-filters .nav-link').forEach(function(l) { l.classList.remove('active'); });
            link.classList.add('active');
            var filter = link.getAttribute('data-filter');
            applyCustomerFilter(filter);
        });
    });

    // Search functionality
    const searchInput = document.getElementById('customerSearch');
    const tableBody = document.getElementById('customers-table-body');
    const rows = tableBody.querySelectorAll('tr');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const activeFilter = document.querySelector('#customer-filters .nav-link.active').getAttribute('data-filter');
        
        rows.forEach(function(row) {
            const text = row.textContent.toLowerCase();
            const rowStatus = row.getAttribute('data-status');
            
            let showRow = true;
            
            // Apply status filter
            if (activeFilter !== 'all') {
                if (rowStatus !== activeFilter) {
                    showRow = false;
                }
            }
            
            // Apply search filter
            if (showRow && searchTerm !== '') {
                if (!text.includes(searchTerm)) {
                    showRow = false;
                }
            }
            
            row.style.display = showRow ? '' : 'none';
        });
    });
});

// Function to apply customer filter
function applyCustomerFilter(filter) {
    const rows = document.querySelectorAll('#customers-table-body tr');
    
    rows.forEach(function(row) {
        if (filter === 'all') {
            row.style.display = '';
        } else if (row.getAttribute('data-status') === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Function to open add subscription modal
function openAddSubscriptionModal(customerId) {
    document.getElementById('addSubCustomerId').value = customerId;
    var modal = new bootstrap.Modal(document.getElementById('addSubscriptionModalTemplate'));
    modal.show();
}

// Function to open manage subscription modal
function openManageSubscriptionModal(customerId) {
    document.getElementById('manageSubCustomerId').value = customerId;
    var modal = new bootstrap.Modal(document.getElementById('subscriptionModalTemplate'));
    modal.show();
}

// Function to cancel subscription
function cancelSubscription() {
    if (confirm('Are you sure you want to cancel this subscription? This action cannot be undone.')) {
        var customerId = document.getElementById('manageSubCustomerId').value;
        document.getElementById('cancelSubCustomerId').value = customerId;
        document.getElementById('cancelSubscriptionForm').submit();
    }
}

// Function to refresh subscription alerts
function refreshSubscriptionAlerts() {
    fetch('./src/services/customers/get_subscription_alerts.php?type=all')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardAlerts(data.data);
            }
        })
        .catch(error => {
            console.error('Error fetching alerts:', error);
        });
}

// Function to update dashboard alerts
function updateDashboardAlerts(alerts) {
    // This function can be used to update any dashboard elements with subscription alerts
    console.log('Subscription alerts updated:', alerts);
}

// Auto-refresh alerts every 5 minutes
setInterval(refreshSubscriptionAlerts, 300000);
</script>
