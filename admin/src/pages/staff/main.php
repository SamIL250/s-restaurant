<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Staff</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Staff Management</h2>
        </div>
        <div class="col-auto ms-auto">
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal"><span class="fas fa-plus me-2"></span>Add Staff</button>
            <?php endif; ?>
        </div>
    </div>
    <?php
    if (!empty($_SESSION['notification'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['notification']) . '</div>';
        unset($_SESSION['notification']);
    }
    // Count staff by role (excluding deleted)
    $role_counts = [
        'all' => 0,
        'admin' => 0,
        'manager' => 0,
        'cashier' => 0,
        'kitchen' => 0,
        'waiter' => 0
    ];
    $staff_count_query = mysqli_query($conn, "SELECT role, COUNT(*) as cnt FROM users WHERE deleted_at IS NULL GROUP BY role");
    while ($row = mysqli_fetch_assoc($staff_count_query)) {
        $role = $row['role'];
        $cnt = (int)$row['cnt'];
        if (isset($role_counts[$role])) $role_counts[$role] = $cnt;
        $role_counts['all'] += $cnt;
    }
    
    // Count deleted staff
    $deleted_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE deleted_at IS NOT NULL"))['cnt'];
    ?>
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3" id="staff-filters">
        <li class="nav-item">
            <a class="nav-link active" data-filter="all" aria-current="page" href="#">
                All <span class="text-body-tertiary fw-semibold">(<?= $role_counts['all'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="admin" href="#">
                Admin <span class="text-body-tertiary fw-semibold">(<?= $role_counts['admin'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="manager" href="#">
                Manager <span class="text-body-tertiary fw-semibold">(<?= $role_counts['manager'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="cashier" href="#">
                Cashier <span class="text-body-tertiary fw-semibold">(<?= $role_counts['cashier'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="kitchen" href="#">
                Kitchen <span class="text-body-tertiary fw-semibold">(<?= $role_counts['kitchen'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="waiter" href="#">
                Waiter <span class="text-body-tertiary fw-semibold">(<?= $role_counts['waiter'] ?>)</span>
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
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="staff-table-body">
                <?php
                $staff_query = mysqli_query($conn, "SELECT *, CASE WHEN deleted_at IS NOT NULL THEN 'deleted' ELSE role END as display_status FROM users ORDER BY deleted_at IS NOT NULL DESC, first_name, last_name");
                $has_staff = false;
                while ($row = mysqli_fetch_assoc($staff_query)) {
                    $has_staff = true;
                    $full_name = trim($row['first_name'] . ' ' . $row['last_name']);
                    $role_badge = '<span class="badge badge-soft-' . 
                        ($row['role'] == 'admin' ? 'danger' : 
                        ($row['role'] == 'manager' ? 'warning' : 
                        ($row['role'] == 'cashier' ? 'info' : 
                        ($row['role'] == 'kitchen' ? 'secondary' : 'primary')))) . '">' . 
                        ucfirst($row['role']) . '</span>';
                    $status_badge = $row['is_active'] ? 
                        '<span class="badge bg-success-subtle text-success">Active</span>' : 
                        '<span class="badge bg-secondary-subtle text-secondary">Inactive</span>';
                ?>
                <tr data-role="<?= $row['role'] ?>" data-status="<?= $row['display_status'] ?>">
                    <td><?= htmlspecialchars($full_name) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <?php if ($row['deleted_at']): ?>
                            <span class='badge bg-danger-subtle text-danger'>Deleted</span>
                        <?php else: ?>
                            <?= $role_badge ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                    <td>
                        <?php if ($row['deleted_at']): ?>
                            <span class='badge bg-danger-subtle text-danger'>Deleted</span>
                        <?php else: ?>
                            <?= $status_badge ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['deleted_at']): ?>
                            <small class="text-muted">Deleted: <?= date('Y-m-d H:i', strtotime($row['deleted_at'])) ?></small>
                        <?php else: ?>
                            <?= date('Y-m-d', strtotime($row['created_at'])) ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if ($row['deleted_at']): ?>
                            <form method="POST" action="src/services/utils/restore_item.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to restore this staff member?');">
                                <input type="hidden" name="table_name" value="users">
                                <input type="hidden" name="id_column" value="user_id">
                                <input type="hidden" name="id_value" value="<?= (int)$row['user_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Restore"><i class="fa fa-undo"></i></button>
                            </form>
                        <?php else: ?>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary me-1" title="Edit" data-bs-toggle="modal" data-bs-target="#editStaffModal<?= (int)$row['user_id'] ?>"><i class="fa fa-edit"></i></button>
                                <?php if ($row['user_id'] != ($_SESSION['user_id'] ?? 0)): ?>
                                <button class="btn btn-sm btn-outline-<?= $row['is_active'] ? 'warning' : 'success' ?> me-1" title="<?= $row['is_active'] ? 'Deactivate' : 'Activate' ?>" onclick="toggleStaffStatus(<?= (int)$row['user_id'] ?>)"><i class="fa fa-<?= $row['is_active'] ? 'ban' : 'check' ?>"></i></button>
                                <form method="POST" action="src/services/staff/delete_staff.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this staff member?');">
                                    <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fa fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <span class="text-muted small">No actions available</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <!-- Edit Modal for this staff member -->
                <div class="modal fade" id="editStaffModal<?= (int)$row['user_id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="src/services/staff/edit_staff.php" method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Staff Member</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
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
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($row['username']) ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($row['email']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Role</label>
                                            <select class="form-select" name="role" required>
                                                <option value="admin" <?= $row['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                                <option value="cashier" <?= $row['role'] == 'cashier' ? 'selected' : '' ?>>Cashier</option>
                                                <option value="stock_clerk" <?= $row['role'] == 'stock_clerk' ? 'selected' : '' ?>>Stock Clerk</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone</label>
                                            <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($row['phone']) ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">New Password (leave blank to keep current)</label>
                                        <input type="password" class="form-control" name="password">
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
                if (!$has_staff): ?>
                <tr><td colspan="8"><div class='alert alert-warning text-center p-2 rounded-2 mt-2 mb-2'>No staff members found</div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="src/services/staff/add_staff.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Staff Member</h5>
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
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="cashier">Cashier</option>
                                <option value="stock_clerk">Stock Clerk</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleStaffStatus(userId) {
    if (confirm('Are you sure you want to change this staff member\'s status?')) {
        const formData = new FormData();
        formData.append('user_id', userId);
        fetch('src/services/staff/toggle_staff_status.php', {
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
    // Filter logic for staff (client-side only)
    document.querySelectorAll('#staff-filters .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#staff-filters .nav-link').forEach(function(l) { l.classList.remove('active'); });
            link.classList.add('active');
            var filter = link.getAttribute('data-filter');
            document.querySelectorAll('#staff-table-body tr').forEach(function(row) {
                if (filter === 'all') {
                    row.style.display = '';
                } else if (row.getAttribute('data-role') === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});
</script> 