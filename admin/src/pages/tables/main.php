<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Restaurant Tables</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Restaurant Tables</h2>
        </div>
        <div class="col-auto ms-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTableModal"><span class="fas fa-plus me-2"></span>Add Table</button>
        </div>
    </div>
    <?php
    if (!empty($_SESSION['notification'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['notification']) . '</div>';
        unset($_SESSION['notification']);
    }
    // Count tables by status (excluding deleted)
    $status_counts = [
        'all' => 0,
        'available' => 0,
        'unavailable' => 0
    ];
    $tables_count_query = mysqli_query($conn, "SELECT is_available, COUNT(*) as cnt FROM restaurant_tables WHERE deleted_at IS NULL GROUP BY is_available");
    while ($row = mysqli_fetch_assoc($tables_count_query)) {
        $status = $row['is_available'] ? 'available' : 'unavailable';
        $cnt = (int)$row['cnt'];
        $status_counts[$status] = $cnt;
        $status_counts['all'] += $cnt;
    }
    
    // Count deleted tables
    $deleted_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM restaurant_tables WHERE deleted_at IS NOT NULL"))['cnt'];
    ?>
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3" id="table-filters">
        <li class="nav-item">
            <a class="nav-link active" data-filter="all" aria-current="page" href="#">
                All <span class="text-body-tertiary fw-semibold">(<?= $status_counts['all'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="available" href="#">
                Available <span class="text-body-tertiary fw-semibold">(<?= $status_counts['available'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="unavailable" href="#">
                Unavailable <span class="text-body-tertiary fw-semibold">(<?= $status_counts['unavailable'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="deleted" href="#">
                Deleted <span class="text-body-tertiary fw-semibold">(<?= $deleted_count ?>)</span>
            </a>
        </li>
    </ul>
    <div class="table-responsive bg-body-emphasis border-top border-bottom border-translucent position-relative top-1 p-3">
        <table class="table fs-9 mb-0">
            <thead>
                <tr>
                    <th>Table #</th>
                    <th>Capacity</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Current Order</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tables-table-body">
                <?php
                $tables_query = mysqli_query($conn, "
                    SELECT t.*, o.order_id, o.order_number, o.order_status,
                           CASE WHEN t.deleted_at IS NOT NULL THEN 'deleted' 
                                WHEN t.is_available = 1 THEN 'available' 
                                ELSE 'unavailable' END as display_status
                    FROM restaurant_tables t
                    LEFT JOIN orders o ON t.table_id = o.table_id AND o.order_status IN ('pending', 'confirmed', 'preparing', 'ready')
                    ORDER BY t.deleted_at IS NOT NULL DESC, t.table_number
                ");
                $has_tables = false;
                while ($row = mysqli_fetch_assoc($tables_query)) {
                    $has_tables = true;
                    $status_badge = $row['is_available'] ? 
                        '<span class="badge bg-success-subtle text-success">Available</span>' : 
                        '<span class="badge bg-secondary-subtle text-secondary">Unavailable</span>';
                    $current_order = $row['order_id'] ? 
                        '<a href="#" class="text-primary">#' . htmlspecialchars($row['order_number']) . '</a> (' . ucfirst($row['order_status']) . ')' : 
                        '-';
                ?>
                <tr data-status="<?= $row['display_status'] ?>">
                    <td class="fw-bold"><?= htmlspecialchars($row['table_number']) ?></td>
                    <td><?= (int)$row['capacity'] ?> seats</td>
                    <td><?= htmlspecialchars($row['location'] ?? 'Main Area') ?></td>
                    <td>
                        <?php if ($row['deleted_at']): ?>
                            <span class='badge bg-danger-subtle text-danger'>Deleted</span>
                        <?php else: ?>
                            <?= $status_badge ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $current_order ?></td>
                    <td>
                        <?php if ($row['deleted_at']): ?>
                            <small class="text-muted">Deleted: <?= date('Y-m-d H:i', strtotime($row['deleted_at'])) ?></small>
                        <?php else: ?>
                            <?= date('Y-m-d', strtotime($row['created_at'])) ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if ($row['deleted_at']): ?>
                            <form method="POST" action="src/services/utils/restore_item.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to restore this table?');">
                                <input type="hidden" name="table_name" value="restaurant_tables">
                                <input type="hidden" name="id_column" value="table_id">
                                <input type="hidden" name="id_value" value="<?= (int)$row['table_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Restore"><i class="fa fa-undo"></i></button>
                            </form>
                        <?php else: ?>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary me-1" title="Edit" data-bs-toggle="modal" data-bs-target="#editTableModal<?= (int)$row['table_id'] ?>"><i class="fa fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-<?= $row['is_available'] ? 'warning' : 'success' ?> me-1" title="<?= $row['is_available'] ? 'Mark Unavailable' : 'Mark Available' ?>" onclick="toggleTableStatus(<?= (int)$row['table_id'] ?>)"><i class="fa fa-<?= $row['is_available'] ? 'ban' : 'check' ?>"></i></button>
                                <form method="POST" action="src/services/tables/delete_table.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this table?');">
                                    <input type="hidden" name="table_id" value="<?= (int)$row['table_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fa fa-trash"></i></button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <!-- Edit Modal for this table -->
                <div class="modal fade" id="editTableModal<?= (int)$row['table_id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="src/services/tables/edit_table.php" method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Table</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="table_id" value="<?= (int)$row['table_id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Table Number</label>
                                        <input type="text" class="form-control" name="table_number" value="<?= htmlspecialchars($row['table_number']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Capacity</label>
                                        <input type="number" class="form-control" name="capacity" value="<?= (int)$row['capacity'] ?>" min="1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Location</label>
                                        <select class="form-select" name="location">
                                            <option value="">Main Area</option>
                                            <option value="indoor" <?= $row['location'] == 'indoor' ? 'selected' : '' ?>>Indoor</option>
                                            <option value="outdoor" <?= $row['location'] == 'outdoor' ? 'selected' : '' ?>>Outdoor</option>
                                            <option value="patio" <?= $row['location'] == 'patio' ? 'selected' : '' ?>>Patio</option>
                                            <option value="bar" <?= $row['location'] == 'bar' ? 'selected' : '' ?>>Bar</option>
                                        </select>
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
                <?php }
                if (!$has_tables): ?>
                <tr><td colspan="7"><div class='alert alert-warning text-center p-2 rounded-2 mt-2 mb-2'>No tables found</div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Table Modal -->
<div class="modal fade" id="addTableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="src/services/tables/add_table.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Table</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Table Number</label>
                        <input type="text" class="form-control" name="table_number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" class="form-control" name="capacity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <select class="form-select" name="location">
                            <option value="">Main Area</option>
                            <option value="indoor">Indoor</option>
                            <option value="outdoor">Outdoor</option>
                            <option value="patio">Patio</option>
                            <option value="bar">Bar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Table</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleTableStatus(tableId) {
    if (confirm('Are you sure you want to change this table\'s availability status?')) {
        const formData = new FormData();
        formData.append('table_id', tableId);
        fetch('src/services/tables/toggle_table_status.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to update status.');
            }
        })
        .catch(() => alert('An error occurred.'));
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Filter logic for tables (client-side only)
    document.querySelectorAll('#table-filters .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#table-filters .nav-link').forEach(function(l) { l.classList.remove('active'); });
            link.classList.add('active');
            var filter = link.getAttribute('data-filter');
            document.querySelectorAll('#tables-table-body tr').forEach(function(row) {
                if (filter === 'all') {
                    row.style.display = '';
                } else if (row.getAttribute('data-status') === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});
</script> 