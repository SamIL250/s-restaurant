<?php
// Suppliers Page
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Suppliers</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Suppliers</h2>
        </div>
        <div class="col-auto ms-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal"><span class="fas fa-plus me-2"></span>Add Supplier</button>
        </div>
    </div>
    <?php
    $all_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM suppliers WHERE deleted_at IS NULL"))['cnt'];
    $active_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM suppliers WHERE is_active = 1 AND deleted_at IS NULL"))['cnt'];
    $inactive_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM suppliers WHERE is_active = 0 AND deleted_at IS NULL"))['cnt'];
    $deleted_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM suppliers WHERE deleted_at IS NOT NULL"))['cnt'];
    ?>
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3" id="supplier-filters">
        <li class="nav-item">
            <a class="nav-link active" data-filter="all" aria-current="page" href="#">
                <span>All Suppliers </span>
                <span class="text-body-tertiary fw-semibold">(<?= $all_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="active" href="#">
                <span>Active </span>
                <span class="text-body-tertiary fw-semibold">(<?= $active_count ?>)</span>
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
    <div class="table-responsive bg-body-emphasis border-top border-bottom border-translucent position-relative top-1 p-3">
        <table class="table fs-9 mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact Person</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="supplier-table-body">
                <?php
                $suppliers_query = mysqli_query($conn, "SELECT *, CASE WHEN deleted_at IS NOT NULL THEN 'deleted' WHEN is_active = 1 THEN 'active' ELSE 'inactive' END as display_status FROM suppliers ORDER BY deleted_at IS NOT NULL DESC, is_active DESC, supplier_name ASC");
                $has_suppliers = false;
                while ($row = mysqli_fetch_assoc($suppliers_query)) {
                    $has_suppliers = true;
                    $status_badge = $row['is_active'] ? '<span class=\'badge bg-success-subtle text-success\'>Active</span>' : '<span class=\'badge bg-secondary-subtle text-secondary\'>Inactive</span>';
                ?>
                <tr data-supplier-id="<?= (int)$row['supplier_id'] ?>" data-status="<?= $row['display_status'] ?>">
                    <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                    <td><?= htmlspecialchars($row['contact_person'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                    <td>
                        <?php if ($row['deleted_at']): ?>
                            <span class='badge bg-danger-subtle text-danger'>Deleted</span>
                        <?php else: ?>
                            <?= $row['is_active'] ? '<span class=\'badge bg-success-subtle text-success\'>Active</span>' : '<span class=\'badge bg-secondary-subtle text-secondary\'>Inactive</span>' ?>
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
                            <form method="POST" action="src/services/utils/restore_item.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to restore this supplier?');">
                                <input type="hidden" name="table_name" value="suppliers">
                                <input type="hidden" name="id_column" value="supplier_id">
                                <input type="hidden" name="id_value" value="<?= (int)$row['supplier_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Restore"><i class="fa fa-undo"></i></button>
                            </form>
                        <?php else: ?>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editSupplier(<?= (int)$row['supplier_id'] ?>)"><i class="fa fa-edit"></i></button>
                                <form method="POST" action="./src/services/suppliers/toggle_supplier_status.php" class="d-inline">
                                    <input type="hidden" name="supplier_id" value="<?= (int)$row['supplier_id'] ?>">
                                    <input type="hidden" name="is_active" value="<?= $row['is_active'] ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?= $row['is_active'] ? 'secondary' : 'success' ?> me-1" title="<?= $row['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                        <i class="fa fa-<?= $row['is_active'] ? 'ban' : 'check' ?>"></i>
                                    </button>
                                </form>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteSupplier(<?= (int)$row['supplier_id'] ?>)"><i class="fa fa-trash"></i></button>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php }
                if (!$has_suppliers): ?>
                <tr><td colspan="7"><div class='alert alert-warning text-center p-2 rounded-2 mt-2 mb-2'>No suppliers found</div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="./src/services/suppliers/add_supplier.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Supplier Name</label>
                        <input type="text" class="form-control" name="supplier_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" class="form-control" name="contact_person">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="addSupplierActive" checked>
                        <label class="form-check-label" for="addSupplierActive">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editSupplierForm" action="./src/services/suppliers/edit_supplier.php" method="POST">
                <input type="hidden" name="supplier_id" id="editSupplierId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Supplier Name</label>
                        <input type="text" class="form-control" name="supplier_name" id="editSupplierName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" class="form-control" name="contact_person" id="editContactPerson">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="editEmail">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" id="editPhone">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="editSupplierActive">
                        <label class="form-check-label" for="editSupplierActive">Active</label>
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

<!-- Hidden form for delete -->
<form id="deleteSupplierForm" action="./src/services/suppliers/delete_supplier.php" method="POST" style="display:none;">
    <input type="hidden" name="supplier_id" id="deleteSupplierId">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter logic for suppliers
    document.querySelectorAll('#supplier-filters .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#supplier-filters .nav-link').forEach(function(l) { l.classList.remove('active'); });
            link.classList.add('active');
            var filter = link.getAttribute('data-filter');
            document.querySelectorAll('#supplier-table-body tr').forEach(function(row) {
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

function editSupplier(supplierId) {
    // Fetch supplier data and populate edit modal
    fetch('./src/services/suppliers/get_supplier.php?id=' + supplierId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('editSupplierId').value = data.supplier_id;
            document.getElementById('editSupplierName').value = data.supplier_name;
            document.getElementById('editContactPerson').value = data.contact_person || '';
            document.getElementById('editEmail').value = data.email || '';
            document.getElementById('editPhone').value = data.phone || '';
            document.getElementById('editSupplierActive').checked = data.is_active == 1;
            var modal = new bootstrap.Modal(document.getElementById('editSupplierModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading supplier data');
        });
}

function deleteSupplier(supplierId) {
    if (confirm('Are you sure you want to delete this supplier? This action cannot be undone.')) {
        document.getElementById('deleteSupplierId').value = supplierId;
        document.getElementById('deleteSupplierForm').submit();
    }
}
</script>
