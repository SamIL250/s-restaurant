<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Initialize variables
$promotions = array();
$tables_exist = false;
$db_error = '';

// Check if database connection is available
if (!isset($conn) || !$conn) {
    $db_error = 'Database connection not available. Please check your configuration.';
    $tables_exist = false;
} else {
    // Check if promotions table exists
    try {
        $check_tables = mysqli_query($conn, "SHOW TABLES LIKE 'promotions'");
        if ($check_tables && mysqli_num_rows($check_tables) > 0) {
            $tables_exist = true;
            
            // Load promotions with status calculation
            $promotions_query = mysqli_query($conn, "
                SELECT p.*, 
                       CASE 
                           WHEN p.deleted_at IS NOT NULL THEN 'deleted'
                           WHEN p.is_active = 0 THEN 'inactive'
                           WHEN p.end_date < CURDATE() THEN 'expired'
                           WHEN p.start_date > CURDATE() THEN 'upcoming'
                           WHEN p.max_uses > 0 AND p.current_uses >= p.max_uses THEN 'fully_used'
                           ELSE 'active' 
                       END as display_status
                FROM promotions p 
                ORDER BY p.deleted_at IS NOT NULL DESC, p.is_active DESC, p.created_at DESC
            ");
            if ($promotions_query) {
                while ($row = mysqli_fetch_assoc($promotions_query)) {
                    $promotions[] = $row;
                }
            } else {
                $db_error = 'Error loading promotions: ' . mysqli_error($conn);
            }
        } else {
            $tables_exist = false;
        }
    } catch (Exception $e) {
        $db_error = 'Database error: ' . htmlspecialchars($e->getMessage());
        $tables_exist = false;
    }
}

// Calculate counts for different statuses
$total_count = 0;
$active_count = 0;
$inactive_count = 0;
$expired_count = 0;
$upcoming_count = 0;
$fully_used_count = 0;
$deleted_count = 0;

if ($tables_exist && !empty($promotions)) {
    foreach ($promotions as $promotion) {
        $total_count++;
        switch ($promotion['display_status']) {
            case 'active':
                $active_count++;
                break;
            case 'inactive':
                $inactive_count++;
                break;
            case 'expired':
                $expired_count++;
                break;
            case 'upcoming':
                $upcoming_count++;
                break;
            case 'fully_used':
                $fully_used_count++;
                break;
            case 'deleted':
                $deleted_count++;
                break;
        }
    }
}
?>

<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Promotions</li>
    </ol>
</nav>

<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Promotions Management</h2>
        </div>
    </div>
    
    <!-- Navigation Tabs -->
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3" id="promotions-filters">
        <li class="nav-item">
            <a class="nav-link active" data-filter="all" aria-current="page" href="#">
                <span>All Promotions </span>
                <span class="text-body-tertiary fw-semibold">(<?= $total_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="active" href="#">
                <span>Active </span>
                <span class="text-body-tertiary fw-semibold">(<?= $active_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="upcoming" href="#">
                <span>Upcoming </span>
                <span class="text-body-tertiary fw-semibold">(<?= $upcoming_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="expired" href="#">
                <span>Expired </span>
                <span class="text-body-tertiary fw-semibold">(<?= $expired_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="fully_used" href="#">
                <span>Fully Used </span>
                <span class="text-body-tertiary fw-semibold">(<?= $fully_used_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="inactive" href="#">
                <span>Inactive </span>
                <span class="text-body-tertiary fw-semibold">(<?= $inactive_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="deleted" href="#">
                <span>Deleted </span>
                <span class="text-body-tertiary fw-semibold">(<?= $deleted_count ?>)</span>
            </a>
        </li>
    </ul>

    <?php if ($db_error): ?>
    <!-- Database Error Message -->
    <div class="alert alert-danger" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Database Error</h4>
        <p><?= htmlspecialchars($db_error) ?></p>
        <hr>
        <p class="mb-0">
            <strong>Please check:</strong>
            <ol class="mb-0 mt-2">
                <li>Database connection settings in config.php</li>
                <li>Database server is running</li>
                <li>Database credentials are correct</li>
            </ol>
        </p>
        <div class="mt-3">
            <button class="btn btn-outline-secondary" onclick="location.reload()">
                <i class="fas fa-sync-alt me-2"></i>Refresh Page
            </button>
        </div>
    </div>
    <?php elseif (!$tables_exist): ?>
    <!-- Setup Required Message -->
    <div class="alert alert-warning" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Setup Required</h4>
        <p>The promotions system has not been set up yet. You need to create the promotions table first.</p>
        <hr>
        <p class="mb-0">
            <strong>To set up the promotions system:</strong>
            <ol class="mb-0 mt-2">
                <li>Create the promotions table in your database</li>
                <li>Or run the SQL commands manually in phpMyAdmin</li>
                <li>Refresh this page after setup is complete</li>
            </ol>
        </p>
        <div class="mt-3">
            <button class="btn btn-outline-secondary" onclick="location.reload()">
                <i class="fas fa-sync-alt me-2"></i>Refresh Page
            </button>
        </div>
    </div>
    <?php else: ?>

    <!-- Search and Actions Section -->
    <div id="promotions" data-list='{"valueNames":["name","description","discount","dates","status"],"page":10,"pagination":true}'>
        <div class="mb-4">
            <div class="d-flex flex-wrap gap-3">
                <div class="search-box">
                    <form class="position-relative">
                        <input class="form-control search-input search" type="search" placeholder="Search promotions..." aria-label="Search" />
                        <span class="fas fa-search search-box-icon"></span>
                    </form>
                </div>
                <div class="ms-xxl-auto">
                    <button class="btn btn-link text-body me-4 px-0">
                        <span class="fa-solid fa-file-export fs-9 me-2"></span>Export
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPromotionModal">
                        <span class="fas fa-plus me-2"></span>Add Promotion
                    </button>
            </div>
        </div>
    </div>

    <!-- Promotions Table -->
        <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis border-top border-bottom border-translucent position-relative top-1">
            <div class="table-responsive scrollbar mx-n1 px-1">
                <table class="table fs-9 mb-0">
            <thead>
                <tr>
                            <th class="white-space-nowrap fs-9 align-middle ps-0" style="max-width:20px; width:18px;">
                                <div class="form-check mb-0 fs-8">
                                    <input class="form-check-input" id="checkbox-bulk-promotions-select" type="checkbox" data-bulk-select='{"body":"promotions-table-body"}' />
                                </div>
                            </th>
                            <th class="sort white-space-nowrap align-middle ps-4" scope="col" style="width:200px;" data-sort="name">PROMOTION NAME</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="description" style="width:250px;">DESCRIPTION</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="discount" style="width:120px;">DISCOUNT</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="dates" style="width:180px;">DATES</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="usage" style="width:120px;">USAGE</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="status" style="width:120px;">STATUS</th>
                            <th class="sort text-end align-middle pe-0 ps-4" scope="col"></th>
                </tr>
            </thead>
                    <tbody class="list" id="promotions-table-body">
                <?php if (!empty($promotions)): ?>
                    <?php foreach ($promotions as $promotion): ?>
                        <?php 
                                // Determine promotion statuses for filtering
                                $promotion_statuses = [$promotion['display_status']];
                                if ($promotion['is_active'] && $promotion['display_status'] === 'active') {
                                    $promotion_statuses[] = 'active';
                                }
                                
                                $promotion_status = implode(' ', $promotion_statuses);
                                
                                // Status badge
                                $status_badge = '';
                                switch ($promotion['display_status']) {
                                    case 'active':
                                        $status_badge = '<span class="badge bg-success-subtle text-success">Active</span>';
                                        break;
                                    case 'upcoming':
                                        $status_badge = '<span class="badge bg-info-subtle text-info">Upcoming</span>';
                                        break;
                                    case 'expired':
                                        $status_badge = '<span class="badge bg-warning-subtle text-warning">Expired</span>';
                                        break;
                                    case 'fully_used':
                                        $status_badge = '<span class="badge bg-secondary-subtle text-secondary">Fully Used</span>';
                                        break;
                                    case 'inactive':
                                        $status_badge = '<span class="badge bg-secondary-subtle text-secondary">Inactive</span>';
                                        break;
                                    case 'deleted':
                                        $status_badge = '<span class="badge bg-danger-subtle text-danger">Deleted</span>';
                                        break;
                                    default:
                                        $status_badge = '<span class="badge bg-secondary-subtle text-secondary">Unknown</span>';
                                }
                        
                        $discount_display = $promotion['discount_type'] === 'percentage' 
                            ? $promotion['discount_value'] . '%' 
                            : '$' . number_format($promotion['discount_value'], 2);
                        
                        $usage_display = $promotion['max_uses'] 
                            ? $promotion['current_uses'] . '/' . $promotion['max_uses']
                            : $promotion['current_uses'] . ' (unlimited)';
                        ?>
                                <tr class="position-static" data-promotion-id="<?= (int)$promotion['promotion_id'] ?>" data-status="<?= $promotion_status ?>">
                                    <td class="fs-9 align-middle">
                                        <div class="form-check mb-0 fs-8">
                                            <input class="form-check-input" type="checkbox" data-bulk-select-row='{"name":"<?= htmlspecialchars($promotion['promotion_name']) ?>"}' />
                                        </div>
                                    </td>
                                    <td class="name align-middle ps-4 fw-semibold">
                                        <?php if ($promotion['deleted_at']): ?>
                                            <span class="text-muted"><?= htmlspecialchars($promotion['promotion_name']) ?></span>
                                        <?php else: ?>
                                            <?= htmlspecialchars($promotion['promotion_name']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="description align-middle ps-4 text-muted">
                                        <?php if ($promotion['deleted_at']): ?>
                                            <small class="text-muted">Deleted: <?= date('Y-m-d H:i', strtotime($promotion['deleted_at'])) ?></small>
                                        <?php else: ?>
                                            <?= htmlspecialchars($promotion['description']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="discount align-middle ps-4 fw-semibold">
                                        <?php if ($promotion['deleted_at']): ?>
                                            -
                                        <?php else: ?>
                                            <?= $discount_display ?>
                                            <?php if ($promotion['minimum_order_amount'] > 0): ?>
                                                <br><small class="text-muted">Min: $<?= number_format($promotion['minimum_order_amount'], 2) ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="dates align-middle ps-4 text-muted">
                                        <?php if ($promotion['deleted_at']): ?>
                                            -
                                        <?php else: ?>
                                            <div class="fw-semibold"><?= date('M d, Y', strtotime($promotion['start_date'])) ?></div>
                                            <small class="text-muted">to <?= date('M d, Y', strtotime($promotion['end_date'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="usage align-middle ps-4">
                                        <?php if ($promotion['deleted_at']): ?>
                                            -
                                        <?php else: ?>
                                            <span class="fw-semibold"><?= $usage_display ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="status align-middle ps-4">
                                        <?= $status_badge ?>
                                    </td>
                                    <td class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                        <?php if ($promotion['deleted_at']): ?>
                                            <form method="POST" action="src/services/utils/restore_item.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to restore this promotion?');">
                                                <input type="hidden" name="table_name" value="promotions">
                                                <input type="hidden" name="id_column" value="promotion_id">
                                                <input type="hidden" name="id_value" value="<?= (int)$promotion['promotion_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Restore">
                                                    <i class="fa fa-undo"></i>
                                    </button>
                                            </form>
                                        <?php else: ?>
                                            <div class="btn-reveal-trigger position-static">
                                                <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs-10" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                                                    <span class="fas fa-ellipsis-h fs-10"></span>
                                    </button>
                                                <div class="dropdown-menu dropdown-menu-end py-2">
                                                    <a class="dropdown-item" href="#" onclick="editPromotion(<?= $promotion['promotion_id'] ?>)">Edit</a>
                                                    <a class="dropdown-item" href="#" onclick="togglePromotionStatus(<?= $promotion['promotion_id'] ?>, <?= $promotion['is_active'] ? 0 : 1 ?>)">
                                                        <?= $promotion['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                    </a>
                                                    <a class="dropdown-item text-danger" href="#" onclick="deletePromotion(<?= $promotion['promotion_id'] ?>)">Delete</a>
                                                </div>
                                </div>
                                        <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                                <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-gift fa-3x mb-3"></i>
                                <h5 class="mb-2">No Promotions Yet</h5>
                                <p class="mb-3">Get started by creating your first promotion to attract customers!</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPromotionModal">
                                            <i class="fas fa-plus me-2"></i>Create First Promotion
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
            </div>
        </div>
    </div>

    <!-- Add Promotion Modal -->
    <div class="modal fade" id="addPromotionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="src/services/promotions/add_promotion.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Promotion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Promotion Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="promotion_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Discount Type <span class="text-danger">*</span></label>
                                <select class="form-control" name="discount_type" required>
                                    <option value="">Select Type</option>
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed_amount">Fixed Amount</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Discount Value <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="discount_value" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Minimum Order Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="minimum_order_amount" step="0.01" min="0" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="start_date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="end_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Maximum Uses</label>
                                <input type="number" class="form-control" name="max_uses" min="1" placeholder="Leave empty for unlimited">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                                    <label class="form-check-label" for="isActive">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Promotion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Promotion Form -->
    <form id="deletePromotionForm" method="POST" action="src/services/promotions/delete_promotion.php" style="display: none;">
        <input type="hidden" name="promotion_id" id="deletePromotionId">
    </form>

    <?php endif; ?>
</div>

<style>
.card {
    border: 1px solid rgba(0, 0, 0, 0.125);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-body {
    padding: 1.25rem;
}

.bg-body-emphasis {
    background-color: #f8f9fa !important;
}

.border-translucent {
    border-color: rgba(0, 0, 0, 0.125) !important;
}

.table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
}

.table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.btn-group .btn {
    border-radius: 4px;
    transition: all 0.2s ease;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.nav-links .nav-link {
    color: #6c757d;
    border-bottom: 2px solid transparent;
    transition: all 0.2s ease;
}

.nav-links .nav-link:hover {
    color: #495057;
    border-bottom-color: #dee2e6;
}

.nav-links .nav-link.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    font-weight: 600;
}

.search-box {
    position: relative;
    min-width: 300px;
}

.search-input {
    padding-left: 2.5rem;
}

.search-box-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    z-index: 10;
}
</style>

<script>
// Filter logic for navigation tabs
document.querySelectorAll('#promotions-filters .nav-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('#promotions-filters .nav-link').forEach(function(l) { 
            l.classList.remove('active'); 
        });
        link.classList.add('active');
        var filter = link.getAttribute('data-filter');
        document.querySelectorAll('#promotions-table-body tr').forEach(function(row) {
            if (filter === 'all') {
                row.style.display = '';
            } else if (row.getAttribute('data-status') && row.getAttribute('data-status').split(' ').includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});

// Search functionality
document.querySelector('.search-input').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const table = document.getElementById('promotions-table-body');
    const rows = table.getElementsByTagName('tr');

    for (let row of rows) {
        if (row.cells.length < 8) continue;
        
        const name = row.cells[1].textContent.toLowerCase();
        const description = row.cells[2].textContent.toLowerCase();
        
        const matchesSearch = name.includes(searchTerm) || description.includes(searchTerm);
        row.style.display = matchesSearch ? '' : 'none';
    }
});

function editPromotion(promotionId) {
    alert('Edit functionality will be implemented soon for promotion ID: ' + promotionId);
}

function togglePromotionStatus(promotionId, newStatus) {
    const action = newStatus ? 'activate' : 'deactivate';
    if (confirm(`Are you sure you want to ${action} this promotion?`)) {
        // Implement status toggle functionality
        alert(`Status toggle functionality will be implemented soon for promotion ID: ${promotionId}`);
    }
}

function deletePromotion(promotionId) {
    if (confirm('Are you sure you want to delete this promotion? This action cannot be undone.')) {
        document.getElementById('deletePromotionId').value = promotionId;
        document.getElementById('deletePromotionForm').submit();
    }
}
</script>
