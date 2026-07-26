<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<style>
/* Custom Status Badge Styles */
.status-badge {
    display: inline-block;
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    color: white;
    min-width: 80px;
    text-align: center;
}

.status-pending {
    background-color: #fbbf24;
    color: #92400e;
}

.status-confirmed {
    background-color: #34d399;
    color: #065f46;
}

.status-completed {
    background-color: #60a5fa;
    color: #1e40af;
}

.status-cancelled {
    background-color: #f87171;
    color: #991b1b;
}

.status-unknown {
    background-color: #9ca3af;
    color: #374151;
}

/* Hover effects */
.status-badge:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

/* Dashboard metrics styling */
.metric-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.metric-card h3 {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.metric-card p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}
</style>

<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Reservations</li>
    </ol>
</nav>

<!-- Dashboard Metrics -->

<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Reservations</h2>
        </div>
        <div class="col-auto ms-auto">
            <a href="new-reservation" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Reservation
            </a>
        </div>
    </div>
    
    <?php
    if (!empty($_SESSION['notification'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['notification']) . '</div>';
        unset($_SESSION['notification']);
    }
    
    // Count reservations by status (including deleted)
    $status_counts = [
        'all' => 0,
        'pending' => 0,
        'confirmed' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'deleted' => 0
    ];
    
    // Active reservations count
    $reservations_count_query = mysqli_query($conn, "SELECT status, COUNT(*) as cnt FROM reservations WHERE deleted_at IS NULL GROUP BY status");
    while ($row = mysqli_fetch_assoc($reservations_count_query)) {
        $status = $row['status'];
        $cnt = (int)$row['cnt'];
        if (isset($status_counts[$status])) $status_counts[$status] = $cnt;
        $status_counts['all'] += $cnt;
    }
    
    // Deleted reservations count
    $deleted_count_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM reservations WHERE deleted_at IS NOT NULL");
    $deleted_total = mysqli_fetch_assoc($deleted_count_query)['cnt'];
    $status_counts['deleted'] = $deleted_total;
    ?>
    
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3" id="reservation-filters">
        <li class="nav-item">
            <a class="nav-link active" data-filter="all" aria-current="page" href="#">
                All <span class="text-body-tertiary fw-semibold">(<?= $status_counts['all'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="pending" href="#">
                Pending <span class="text-body-tertiary fw-semibold">(<?= $status_counts['pending'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="confirmed" href="#">
                Confirmed <span class="text-body-tertiary fw-semibold">(<?= $status_counts['confirmed'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="completed" href="#">
                Completed <span class="text-body-tertiary fw-semibold">(<?= $status_counts['completed'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="cancelled" href="#">
                Cancelled <span class="text-body-tertiary fw-semibold">(<?= $status_counts['cancelled'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="deleted" href="#">
                <i class="fas fa-trash me-1"></i>Deleted <span class="text-body-tertiary fw-semibold">(<?= $status_counts['deleted'] ?>)</span>
            </a>
        </li>
    </ul>
    
    <!-- Search Bar -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" id="reservationSearch" placeholder="Search by customer name, email, phone, or reservation ID...">
                <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <small class="text-muted">Search results will update as you type</small>
        </div>
    </div>
    
    <div class="table-responsive bg-body-emphasis border-top border-bottom border-translucent position-relative top-1 p-3">
        <table class="table fs-9 mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Date & Time</th>
                    <th>Guests</th>
                    <th>Table</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="reservations-table-body">
                <?php
                // Query to get both active and deleted reservations
                $reservations_query = mysqli_query($conn, "
                    SELECT r.*, rt.table_number, rt.capacity, rt.location,
                           COALESCE(SUM(ri.total_price), 0) as calculated_total,
                           CASE WHEN r.deleted_at IS NOT NULL THEN 'deleted' ELSE r.status END as display_status
                    FROM reservations r
                    LEFT JOIN restaurant_tables rt ON r.table_id = rt.table_id
                    LEFT JOIN reservation_items ri ON r.reservation_id = ri.reservation_id
                    GROUP BY r.reservation_id
                    ORDER BY r.deleted_at DESC, r.reservation_date DESC, r.reservation_time DESC
                ");
                
                $has_reservations = false;
                while ($reservation = mysqli_fetch_assoc($reservations_query)) {
                    $has_reservations = true;
                    $table_info = $reservation['table_number'] ? "Table {$reservation['table_number']} ({$reservation['capacity']} seats)" : 'Not assigned';
                    $location_badge = $reservation['location'] ? '<span class="badge badge-soft-info">' . ucfirst($reservation['location']) . '</span>' : '';
                    
                    $status_class = '';
                    $status_text = $reservation['display_status'] ?? 'unknown';
                    $is_deleted = $reservation['deleted_at'] !== null;
                    
                    switch ($status_text) {
                        case 'pending': $status_class = 'badge badge-soft-warning'; break;
                        case 'confirmed': $status_class = 'badge badge-soft-success'; break;
                        case 'completed': $status_class = 'badge badge-soft-primary'; break;
                        case 'cancelled': $status_class = 'badge badge-soft-danger'; break;
                        case 'deleted': $status_class = 'badge badge-soft-secondary'; break;
                        default: $status_class = 'badge badge-soft-secondary'; break;
                    }
                    
                    $datetime = date('M d, Y H:i', strtotime($reservation['reservation_date'] . ' ' . $reservation['reservation_time']));
                    $row_class = $is_deleted ? 'table-secondary opacity-75' : '';
                ?>
                <tr data-status="<?= $status_text ?>" class="<?= $row_class ?>">
                    <td class="white-space-nowrap">
                        <a href="#" class="fw-bold text-900" data-bs-toggle="modal" data-bs-target="#reservationDetailsModal<?= $reservation['reservation_id'] ?>">#<?= $reservation['reservation_id'] ?></a>
                        <?php if ($is_deleted): ?>
                            <br><small class="text-muted">Deleted: <?= date('M d, Y', strtotime($reservation['deleted_at'])) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="white-space-nowrap">
                        <div><?= htmlspecialchars($reservation['customer_name']) ?></div>
                        <small class="text-body-tertiary"><?= htmlspecialchars($reservation['customer_email']) ?></small>
                    </td>
                    <td class="white-space-nowrap"><?= $datetime ?></td>
                    <td class="white-space-nowrap"><?= $reservation['number_of_guests'] ?> guests</td>
                    <td class="white-space-nowrap">
                        <div><?= $table_info ?></div>
                        <?= $location_badge ?>
                    </td>
                    <td class="white-space-nowrap">
                        <span class="status-badge status-<?= $status_text ?>" style="
                            display: inline-block;
                            font-size: 0.75rem;
                            padding: 0.25rem 0.75rem;
                            border-radius: 0.375rem;
                            font-weight: 500;
                            text-transform: uppercase;
                            letter-spacing: 0.025em;
                        ">
                            <?= ucfirst($status_text) ?>
                        </span>
                    </td>
                    <td class="white-space-nowrap fw-bold">Frw <?= number_format($reservation['calculated_total'], 2) ?></td>
                    <td class="text-end">
                        <div class="btn-group" role="group">
                            <a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reservationDetailsModal<?= $reservation['reservation_id'] ?>" title="View">
                                <i class="fa fa-eye"></i>
                            </a>
                            <?php if (!$is_deleted): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit" onclick="editReservation(<?= $reservation['reservation_id'] ?>)">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteReservation(<?= $reservation['reservation_id'] ?>)">
                                    <i class="fa fa-trash"></i>
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-success" title="Restore" onclick="restoreReservation(<?= $reservation['reservation_id'] ?>)">
                                    <i class="fa fa-undo"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php } ?>
                <?php if (!$has_reservations): ?>
                <tr><td colspan="8"><div class='alert alert-info text-center p-2 rounded-2 mt-2 mb-2'>No reservations found</div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reservation Details Modals -->
<?php
// Reset the query to show modals
$reservations_for_modals = mysqli_query($conn, "
    SELECT r.*, rt.table_number, rt.capacity, rt.location,
           COALESCE(SUM(ri.total_price), 0) as calculated_total
    FROM reservations r
    LEFT JOIN restaurant_tables rt ON r.table_id = rt.table_id
    LEFT JOIN reservation_items ri ON r.reservation_id = ri.reservation_id
    GROUP BY r.reservation_id
    ORDER BY r.deleted_at DESC, r.reservation_date DESC, r.reservation_time DESC
");

while ($reservation = mysqli_fetch_assoc($reservations_for_modals)) {
    $table_info = $reservation['table_number'] ? "Table {$reservation['table_number']} ({$reservation['capacity']} seats)" : 'Not assigned';
    $is_deleted = $reservation['deleted_at'] !== null;
?>
<div class="modal fade" id="reservationDetailsModal<?= $reservation['reservation_id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Reservation #<?= $reservation['reservation_id'] ?> Details
                    <?php if ($is_deleted): ?>
                        <span class="badge badge-soft-secondary ms-2">Deleted</span>
                    <?php endif; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p><strong>Name:</strong> <?= htmlspecialchars($reservation['customer_name']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($reservation['customer_phone']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($reservation['customer_email']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Reservation Details</h6>
                        <p><strong>Date:</strong> <?= date('M d, Y', strtotime($reservation['reservation_date'])) ?></p>
                        <p><strong>Time:</strong> <?= date('H:i', strtotime($reservation['reservation_time'])) ?></p>
                        <p><strong>Guests:</strong> <?= $reservation['number_of_guests'] ?></p>
                        <p><strong>Table:</strong> <?= $table_info ?></p>
                        <p><strong>Total Amount:</strong> <strong>Frw <?= number_format($reservation['calculated_total'], 2) ?></strong></p>
                        <?php if ($is_deleted): ?>
                            <p><strong>Deleted On:</strong> <span class="text-danger"><?= date('M d, Y H:i', strtotime($reservation['deleted_at'])) ?></span></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($reservation['special_requests'])): ?>
                <div class="mt-3">
                    <h6>Special Requests</h6>
                    <p><?= htmlspecialchars($reservation['special_requests']) ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Reservation Items -->
                <div class="mt-3">
                    <h6>Reservation Items</h6>
                    <?php
                    $items_query = mysqli_query($conn, "
                        SELECT ri.*, mi.item_name, mi.description
                        FROM reservation_items ri
                        JOIN menu_items mi ON ri.menu_item_id = mi.menu_item_id
                        WHERE ri.reservation_id = {$reservation['reservation_id']} AND ri.deleted_at IS NULL
                        ORDER BY ri.reservation_item_id
                    ");
                    if (mysqli_num_rows($items_query) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th>Special Instructions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = mysqli_fetch_assoc($items_query)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>Frw <?= number_format($item['unit_price'], 2) ?></td>
                                        <td>Frw <?= number_format($item['total_price'], 2) ?></td>
                                        <td><?= htmlspecialchars($item['special_instructions'] ?: '-') ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No items selected for this reservation.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Status Update Form (only for non-deleted reservations) -->
                <?php if (!$is_deleted): ?>
                <div class="mt-4">
                    <h6>Update Status</h6>
                    <form action="src/services/reservations/update_reservation_status.php" method="POST" class="d-flex gap-2">
                        <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                        <select name="status" class="form-select form-select-sm" style="width: auto;">
                            <option value="pending" <?= $reservation['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $reservation['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="completed" <?= $reservation['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $reservation['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteReservationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this reservation? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteReservationForm" action="src/services/reservations/delete_reservation.php" method="POST" style="display: inline;">
                    <input type="hidden" name="reservation_id" id="deleteReservationId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreReservationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Restore</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to restore this deleted reservation?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="restoreReservationForm" action="src/services/reservations/restore_reservation.php" method="POST" style="display: inline;">
                    <input type="hidden" name="reservation_id" id="restoreReservationId">
                    <button type="submit" class="btn btn-success">Restore</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Reservation Modal -->
<div class="modal fade" id="editReservationModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Reservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editReservationForm" action="src/services/reservations/edit_reservation.php" method="POST">
                    <input type="hidden" name="reservation_id" id="editReservationId">
                    
                    <div class="row g-3">
                        <!-- Customer Information -->
                        <div class="col-12">
                            <h6 class="mb-3 text-primary">Customer Information</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="editCustomerName" class="form-label">Customer Name *</label>
                            <input type="text" class="form-control" id="editCustomerName" name="customer_name" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="editCustomerPhone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="editCustomerPhone" name="customer_phone" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="editCustomerEmail" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="editCustomerEmail" name="customer_email" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="editNumberOfGuests" class="form-label">Number of Guests *</label>
                            <input type="number" class="form-control" id="editNumberOfGuests" name="number_of_guests" min="1" max="20" required>
                        </div>
                        
                        <!-- Reservation Details -->
                        <div class="col-12">
                            <h6 class="mb-3 mt-4 text-primary">Reservation Details</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="editReservationDate" class="form-label">Reservation Date *</label>
                            <input type="date" class="form-control" id="editReservationDate" name="reservation_date" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="editReservationTime" class="form-label">Reservation Time *</label>
                            <input type="time" class="form-control" id="editReservationTime" name="reservation_time" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="editTableId" class="form-label">Table Assignment</label>
                            <select class="form-select" id="editTableId" name="table_id">
                                <option value="">No table assigned</option>
                                <?php
                                $tables_query = mysqli_query($conn, "
                                    SELECT table_id, table_number, capacity, location, is_available 
                                    FROM restaurant_tables 
                                    WHERE deleted_at IS NULL 
                                    ORDER BY table_number
                                ");
                                while ($table = mysqli_fetch_assoc($tables_query)) {
                                    echo "<option value='{$table['table_id']}'>
                                        Table {$table['table_number']} ({$table['capacity']} seats) - {$table['location']}
                                    </option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">Frw</span>
                                <input type="text" class="form-control" id="editTotalAmountDisplay" readonly value="0.00">
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label for="editSpecialRequests" class="form-label">Special Requests</label>
                            <textarea class="form-control" id="editSpecialRequests" name="special_requests" rows="3" placeholder="Any special requests or notes..."></textarea>
                        </div>
                        
                        <!-- Menu Items -->
                        <div class="col-12">
                            <h6 class="mb-3 mt-4 text-primary">Menu Items</h6>
                            <div id="editMenuItemsContainer">
                                <!-- Menu items will be populated here -->
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="editAddMenuItem">
                                <i class="fas fa-plus me-1"></i>Add Menu Item
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateReservation()">
                    <i class="fas fa-save me-2"></i>Update Reservation
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter logic for reservations
    document.querySelectorAll('#reservation-filters .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#reservation-filters .nav-link').forEach(function(l) { l.classList.remove('active'); });
            link.classList.add('active');
            var filter = link.getAttribute('data-filter');
            applyFiltersAndSearch();
        });
    });
    
    // Search functionality
    const searchInput = document.getElementById('reservationSearch');
    const clearSearchBtn = document.getElementById('clearSearch');
    
    searchInput.addEventListener('input', function() {
        applyFiltersAndSearch();
    });
    
    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        applyFiltersAndSearch();
        searchInput.focus();
    });
    
    // Combined filter and search function
    function applyFiltersAndSearch() {
        const activeFilter = document.querySelector('#reservation-filters .nav-link.active').getAttribute('data-filter');
        const searchTerm = searchInput.value.toLowerCase().trim();
        
        document.querySelectorAll('#reservations-table-body tr').forEach(function(row) {
            if (row.cells.length < 8) return; // Skip rows that don't have all columns
            
            let showRow = true;
            
            // Apply status filter
            if (activeFilter !== 'all') {
                const rowStatus = row.getAttribute('data-status');
                if (rowStatus !== activeFilter) {
                    showRow = false;
                }
            }
            
            // Apply search filter
            if (showRow && searchTerm !== '') {
                const searchableText = [
                    row.cells[0].textContent, // ID
                    row.cells[1].textContent, // Customer name and email
                    row.cells[1].querySelector('small')?.textContent || '' // Email specifically
                ].join(' ').toLowerCase();
                
                if (!searchableText.includes(searchTerm)) {
                    showRow = false;
                }
            }
            
            row.style.display = showRow ? '' : 'none';
        });
        
        // Update search results count
        updateSearchResultsCount();
    }
    
    // Update search results count
    function updateSearchResultsCount() {
        const visibleRows = document.querySelectorAll('#reservations-table-body tr[style=""]').length;
        const totalRows = document.querySelectorAll('#reservations-table-body tr').length;
        
        if (searchInput.value.trim() !== '') {
            const searchInfo = document.querySelector('.text-muted');
            if (searchInfo) {
                searchInfo.textContent = `Showing ${visibleRows} of ${totalRows} reservations`;
            }
        } else {
            const searchInfo = document.querySelector('.text-muted');
            if (searchInfo) {
                searchInfo.textContent = 'Search results will update as you type';
            }
        }
    }
    
    // Initial count update
    updateSearchResultsCount();
});

function deleteReservation(reservationId) {
    document.getElementById('deleteReservationId').value = reservationId;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteReservationModal'));
    deleteModal.show();
}

function restoreReservation(reservationId) {
    if (confirm('Are you sure you want to restore this deleted reservation?')) {
        // Send AJAX request to restore the reservation
        fetch('src/services/reservations/restore_reservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                reservation_id: reservationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showToast('success', data.message);
                // Reload the page to refresh the data
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                // Show error message
                showToast('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'An error occurred while restoring the reservation');
        });
    }
}

function editReservation(reservationId) {
    // Fetch reservation details
    fetch('src/services/reservations/get_reservation_details.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reservation_id: reservationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Populate the form with reservation data
            document.getElementById('editReservationId').value = data.reservation.reservation_id;
            document.getElementById('editCustomerName').value = data.reservation.customer_name;
            document.getElementById('editCustomerPhone').value = data.reservation.customer_phone;
            document.getElementById('editCustomerEmail').value = data.reservation.customer_email;
            document.getElementById('editReservationDate').value = data.reservation.reservation_date;
            document.getElementById('editReservationTime').value = data.reservation.reservation_time;
            document.getElementById('editNumberOfGuests').value = data.reservation.number_of_guests;
            document.getElementById('editTableId').value = data.reservation.table_id || '';
            document.getElementById('editSpecialRequests').value = data.reservation.special_requests || '';
            
            // Fetch and populate menu items
            fetchReservationItems(reservationId);
            
            // Show the modal
            var editModal = new bootstrap.Modal(document.getElementById('editReservationModal'));
            editModal.show();
        } else {
            showToast('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'An error occurred while fetching reservation details');
    });
}

function fetchReservationItems(reservationId) {
    fetch('src/services/reservations/get_reservation_items.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reservation_id: reservationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateMenuItems(data.items);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function populateMenuItems(items) {
    const container = document.getElementById('editMenuItemsContainer');
    container.innerHTML = '';
    
    if (items.length === 0) {
        addMenuItemRow(container, 0);
    } else {
        items.forEach((item, index) => {
            addMenuItemRow(container, index, item);
        });
    }
    
    updateEditSummary();
}

function addMenuItemRow(container, index, item = null) {
    const newRow = document.createElement('div');
    newRow.className = 'menu-item-row row g-2 mb-2';
    newRow.innerHTML = `
        <div class="col-md-4">
            <select class="form-select edit-menu-item-select" name="menu_items[${index}][menu_item_id]">
                <option value="">Select menu item</option>
                ${getMenuItemsOptions()}
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" name="menu_items[${index}][quantity]" placeholder="Qty" min="1" value="${item ? item.quantity : 1}">
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control edit-menu-item-price" name="menu_items[${index}][unit_price]" placeholder="Price" step="0.01" min="0" readonly>
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" name="menu_items[${index}][special_instructions]" placeholder="Special instructions" value="${item ? item.special_instructions || '' : ''}">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger btn-sm remove-edit-menu-item" ${index === 0 ? 'style="display: none;"' : ''}>
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
    
    // Set selected menu item and price if editing
    if (item) {
        const select = newRow.querySelector('.edit-menu-item-select');
        const priceInput = newRow.querySelector('.edit-menu-item-price');
        select.value = item.menu_item_id;
        priceInput.value = item.unit_price;
    }
    
    // Show remove buttons for all rows except the first
    if (index > 0) {
        document.querySelectorAll('.remove-edit-menu-item').forEach(btn => btn.style.display = 'block');
    }
}

function getMenuItemsOptions() {
    // Get the actual menu items from the database
    // We'll need to pass this data from PHP to JavaScript
    const menuItems = <?php
        $menu_items = [];
        $menu_query = mysqli_query($conn, "
            SELECT menu_item_id, item_name, price, description 
            FROM menu_items 
            WHERE deleted_at IS NULL AND is_available = 1
            ORDER BY item_name
        ");
        while ($item = mysqli_fetch_assoc($menu_query)) {
            $menu_items[] = [
                'id' => $item['menu_item_id'],
                'name' => $item['item_name'],
                'price' => $item['price']
            ];
        }
        echo json_encode($menu_items);
    ?>;
    
    let options = '<option value="">Select menu item</option>';
    menuItems.forEach(item => {
        options += `<option value="${item.id}" data-price="${item.price}">${item.name} - Frw ${item.price}</option>`;
    });
    
    return options;
}

function updateEditSummary() {
    const summary = document.getElementById('editTotalAmountDisplay');
    let total = 0;
    
    document.querySelectorAll('#editMenuItemsContainer .menu-item-row').forEach(row => {
        const select = row.querySelector('.edit-menu-item-select');
        const quantity = row.querySelector('input[name*="[quantity]"]');
        const price = row.querySelector('.edit-menu-item-price');
        
        if (select.value && quantity.value && price.value) {
            const itemTotal = parseFloat(quantity.value) * parseFloat(price.value);
            total += itemTotal;
        }
    });
    
    summary.value = total.toFixed(2);
}

function updateReservation() {
    const form = document.getElementById('editReservationForm');
    const formData = new FormData(form);
    
    // Convert FormData to regular object for form submission
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });
    
    // Submit the form
    form.submit();
}

// Add event listeners for edit modal
document.addEventListener('DOMContentLoaded', function() {
    // ... existing code ...
    
    // Edit menu item functionality
    let editMenuItemCounter = 1;
    
    document.getElementById('editAddMenuItem').addEventListener('click', function() {
        const container = document.getElementById('editMenuItemsContainer');
        addMenuItemRow(container, editMenuItemCounter);
        editMenuItemCounter++;
        
        // Show remove buttons for all rows except the first
        document.querySelectorAll('.remove-edit-menu-item').forEach(btn => btn.style.display = 'block');
    });
    
    // Handle edit menu item selection and price updates
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('edit-menu-item-select')) {
            const row = e.target.closest('.menu-item-row');
            const priceInput = row.querySelector('.edit-menu-item-price');
            const selectedOption = e.target.options[e.target.selectedIndex];
            const price = selectedOption.dataset.price || '';
            priceInput.value = price;
            updateEditSummary();
        }
    });
    
    // Handle edit quantity changes
    document.addEventListener('input', function(e) {
        if (e.target.name && e.target.name.includes('[quantity]')) {
            updateEditSummary();
        }
    });
    
    // Remove edit menu item
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-edit-menu-item') || e.target.closest('.remove-edit-menu-item')) {
            const row = e.target.closest('.menu-item-row');
            row.remove();
            updateEditSummary();
            
            // Hide remove buttons if only one row remains
            if (document.querySelectorAll('#editMenuItemsContainer .menu-item-row').length === 1) {
                document.querySelectorAll('.remove-edit-menu-item').forEach(btn => btn.style.display = 'none');
            }
        }
    });
});

// Add this function if it doesn't exist
function showToast(type, message) {
    // You can implement this based on your existing toast system
    // For now, using simple alert
    if (type === 'success') {
        alert('Success: ' + message);
    } else {
        alert('Error: ' + message);
    }
}
</script>
