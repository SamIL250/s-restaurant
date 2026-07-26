<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Initialize variables
$subscription_types = array();
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
            
            // Load subscription types
            $types_query = mysqli_query($conn, "SELECT * FROM subscription_types ORDER BY price ASC");
            if ($types_query) {
                while ($row = mysqli_fetch_assoc($types_query)) {
                    $subscription_types[] = $row;
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
        <li class="breadcrumb-item active">Subscription Types</li>
    </ol>
</nav>

<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Subscription Types</h2>
        </div>
        <div class="col-auto ms-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubscriptionTypeModal">
                <span class="fas fa-plus me-2"></span>Add Type
            </button>
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

    <!-- Search and Filters Section -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <!-- Search Input -->
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by name, description, or service hours...">
                    </div>
                </div>
                
                <!-- Status Filter -->
                <div class="col-md-2">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <!-- Price Range Filter -->
                <div class="col-md-2">
                    <select class="form-select" id="priceFilter">
                        <option value="">All Prices</option>
                        <option value="0-50">$0 - $50</option>
                        <option value="50-100">$50 - $100</option>
                        <option value="100-200">$100 - $200</option>
                        <option value="200+">$200+</option>
                    </select>
                </div>
                
                <!-- Duration Filter -->
                <div class="col-md-2">
                    <select class="form-select" id="durationFilter">
                        <option value="">All Durations</option>
                        <option value="1-7">1-7 days</option>
                        <option value="8-30">8-30 days</option>
                        <option value="31-90">31-90 days</option>
                        <option value="90+">90+ days</option>
                    </select>
                </div>
                
                <!-- Clear Filters -->
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                        <i class="fas fa-times me-1"></i>Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive bg-body-emphasis border-top border-bottom border-translucent position-relative top-1 p-3">
        <table class="table fs-9 mb-0">
            <thead>
                <tr>
                    <th>Type Name</th>
                    <th>Description</th>
                    <th>Duration</th>
                    <th>Price</th>
                    <th>Meals/Day</th>
                    <th>Service Hours</th>
                    <th>Beverages</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($subscription_types)): ?>
                    <?php foreach ($subscription_types as $type): ?>
                        <?php 
                        $status_badge = $type['is_active'] ?
                            '<span class="badge bg-success-subtle text-success">Active</span>' :
                            '<span class="badge bg-secondary-subtle text-secondary">Inactive</span>';
                        $beverages_badge = $type['includes_beverages'] ?
                            '<span class="badge bg-info-subtle text-info">Yes</span>' :
                            '<span class="badge bg-light-subtle text-secondary">No</span>';
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($type['type_name']) ?></strong></td>
                            <td><?= htmlspecialchars($type['description']) ?></td>
                            <td><?= $type['duration_days'] ?> days</td>
                            <td><strong>$<?= number_format($type['price'], 2) ?></strong></td>
                            <td><?= $type['meals_per_day'] ?></td>
                            <td><?= htmlspecialchars($type['service_hours']) ?></td>
                            <td><?= $beverages_badge ?></td>
                            <td><?= $status_badge ?></td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary me-1" title="Edit Subscription Type" onclick="editSubscriptionType(<?= $type['subscription_type_id'] ?>)">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Delete Subscription Type" onclick="deleteSubscriptionType(<?= $type['subscription_type_id'] ?>)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9"><div class='alert alert-warning text-center p-2 rounded-2 mt-2 mb-2'>No subscription types found</div></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Subscription Type Modal -->
    <div class="modal fade" id="addSubscriptionTypeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="src/services/customers/add_subscription_type.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Subscription Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Type Name</label>
                                <input type="text" class="form-control" name="type_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Duration (Days)</label>
                                <input type="number" class="form-control" name="duration_days" min="1" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Meals Per Day</label>
                                <input type="number" class="form-control" name="meals_per_day" min="1" value="1" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Service Hours</label>
                                <input type="text" class="form-control" name="service_hours" placeholder="e.g., Lunch only (12:00-14:00)">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Meals Per Month</label>
                                <input type="number" class="form-control" name="max_meals_per_month" min="1">
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="includes_beverages" id="includesBeverages">
                            <label class="form-check-label" for="includesBeverages">Includes Beverages</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Subscription Type Modal -->
    <div class="modal fade" id="editSubscriptionTypeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="src/services/customers/edit_subscription_type.php" method="POST">
                    <input type="hidden" name="subscription_type_id" id="editTypeId">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Subscription Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Type Name</label>
                                <input type="text" class="form-control" name="type_name" id="editTypeName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Duration (Days)</label>
                                <input type="number" class="form-control" name="duration_days" id="editDurationDays" min="1" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="editDescription" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="price" id="editPrice" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Meals Per Day</label>
                                <input type="number" class="form-control" name="meals_per_day" id="editMealsPerDay" min="1" value="1" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Service Hours</label>
                                <input type="text" class="form-control" name="service_hours" id="editServiceHours" placeholder="e.g., Lunch only (12:00-14:00)">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Meals Per Month</label>
                                <input type="number" class="form-control" name="max_meals_per_month" id="editMaxMealsPerMonth" min="1">
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="includes_beverages" id="editIncludesBeverages">
                            <label class="form-check-label" for="editIncludesBeverages">Includes Beverages</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive">
                            <label class="form-check-label" for="editIsActive">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<style>
/* Custom scrollbar styling */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Action buttons styling to match main customers table */
.btn-group .btn {
    border-radius: 4px;
    transition: all 0.2s ease;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-group .btn-outline-primary:hover {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.btn-group .btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

/* Ensure table has proper spacing */
.table th {
    font-weight: 600;
    color: #495057;
}

.table td {
    vertical-align: middle;
}

/* Match the main customers table styling */
.bg-body-emphasis {
    background-color: #f8f9fa !important;
}

.border-translucent {
    border-color: rgba(0, 0, 0, 0.125) !important;
}

/* Search and Filter Section Styling */
.card {
    border: 1px solid rgba(0, 0, 0, 0.125);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-body {
    padding: 1.25rem;
}

.input-group-text {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    color: #6c757d;
}

.form-control:focus,
.form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.form-select {
    cursor: pointer;
}

/* Filter button styling */
.btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
}

/* Results count styling */
#resultsCount {
    font-size: 0.875rem;
    color: #6c757d;
    font-style: italic;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .col-md-2 {
        margin-bottom: 0.5rem;
    }
    
    .card-body .row {
        margin-bottom: 0;
    }
}
</style>

<script>
// Search and Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get filter elements
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const priceFilter = document.getElementById('priceFilter');
    const durationFilter = document.getElementById('durationFilter');
    
    // Add event listeners
    searchInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);
    priceFilter.addEventListener('change', filterTable);
    durationFilter.addEventListener('change', filterTable);
});

function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusValue = document.getElementById('statusFilter').value;
    const priceValue = document.getElementById('priceFilter').value;
    const durationValue = document.getElementById('durationFilter').value;
    
    const table = document.querySelector('table tbody');
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        if (row.cells.length < 9) return; // Skip "no data" rows
        
        const typeName = row.cells[0].textContent.toLowerCase();
        const description = row.cells[1].textContent.toLowerCase();
        const serviceHours = row.cells[5].textContent.toLowerCase();
        const status = row.cells[7].textContent.toLowerCase();
        const price = parseFloat(row.cells[3].textContent.replace('$', '').replace(',', ''));
        const duration = parseInt(row.cells[2].textContent);
        
        let showRow = true;
        
        // Search filter
        if (searchTerm && !typeName.includes(searchTerm) && !description.includes(searchTerm) && !serviceHours.includes(searchTerm)) {
            showRow = false;
        }
        
        // Status filter
        if (statusValue && !status.includes(statusValue)) {
            showRow = false;
        }
        
        // Price filter
        if (priceValue) {
            const [min, max] = priceValue.split('-').map(v => v === '+' ? Infinity : parseFloat(v));
            if (price < min || (max !== Infinity && price > max)) {
                showRow = false;
            }
        }
        
        // Duration filter
        if (durationValue) {
            const [min, max] = durationValue.split('-').map(v => v === '+' ? Infinity : parseInt(v));
            if (duration < min || (max !== Infinity && duration > max)) {
                showRow = false;
            }
        }
        
        // Show/hide row
        row.style.display = showRow ? '' : 'none';
    });
    
    // Update results count
    updateResultsCount();
}

function updateResultsCount() {
    const visibleRows = document.querySelectorAll('table tbody tr:not([style*="display: none"])');
    const totalRows = document.querySelectorAll('table tbody tr');
    
    // Find or create results count element
    let resultsCount = document.getElementById('resultsCount');
    if (!resultsCount) {
        resultsCount = document.createElement('div');
        resultsCount.id = 'resultsCount';
        resultsCount.className = 'text-muted small mt-2';
        document.querySelector('.card-body').appendChild(resultsCount);
    }
    
    const visibleCount = visibleRows.length;
    const totalCount = totalRows.length;
    
    if (visibleCount === totalCount) {
        resultsCount.textContent = `Showing all ${totalCount} subscription types`;
    } else {
        resultsCount.textContent = `Showing ${visibleCount} of ${totalCount} subscription types`;
    }
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('priceFilter').value = '';
    document.getElementById('durationFilter').value = '';
    
    // Show all rows
    const rows = document.querySelectorAll('table tbody tr');
    rows.forEach(row => {
        row.style.display = '';
    });
    
    updateResultsCount();
}

function editSubscriptionType(typeId) {
    // Get the subscription type data from the table row
    const row = event.target.closest('tr');
    const cells = row.cells;
    
    // Populate the edit modal with current values
    document.getElementById('editTypeId').value = typeId;
    document.getElementById('editTypeName').value = cells[0].textContent.trim();
    document.getElementById('editDescription').value = cells[1].textContent.trim();
    document.getElementById('editDurationDays').value = cells[2].textContent.replace(' days', '');
    document.getElementById('editPrice').value = cells[3].textContent.replace('$', '').replace(',', '');
    document.getElementById('editMealsPerDay').value = cells[4].textContent.trim();
    document.getElementById('editServiceHours').value = cells[5].textContent.trim();
    document.getElementById('editMaxMealsPerMonth').value = cells[5].textContent.trim() || '';
    document.getElementById('editIncludesBeverages').checked = cells[6].textContent.includes('Yes');
    document.getElementById('editIsActive').checked = cells[7].textContent.includes('Active');
    
    // Show the edit modal
    const editModal = new bootstrap.Modal(document.getElementById('editSubscriptionTypeModal'));
    editModal.show();
}

function deleteSubscriptionType(typeId) {
    if (confirm('Are you sure you want to delete this subscription type? This action cannot be undone.')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'src/services/customers/delete_subscription_type.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'subscription_type_id';
        input.value = typeId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
