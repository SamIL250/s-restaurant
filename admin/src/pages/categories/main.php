<?php
// Categories Management Page
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Categories</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Categories</h2>
        </div>
        <div class="col-auto ms-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><span class="fas fa-plus me-2"></span>Add Category</button>
        </div>
    </div>
    <?php
    if (!empty($_SESSION['notification'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['notification']) . '</div>';
        unset($_SESSION['notification']);
    }
    $all_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM categories WHERE deleted_at IS NULL"))['cnt'];
    $active_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM categories WHERE is_active = 1 AND deleted_at IS NULL"))['cnt'];
    $inactive_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM categories WHERE is_active = 0 AND deleted_at IS NULL"))['cnt'];
    $deleted_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM categories WHERE deleted_at IS NOT NULL"))['cnt'];
    ?>
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3" id="category-filters">
        <li class="nav-item">
            <a class="nav-link active" data-filter="all" aria-current="page" href="#">
                <span>All Categories </span>
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
                    <th>Description</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="category-table-body">
                <?php
                $categories_query = mysqli_query($conn, "SELECT *, CASE WHEN deleted_at IS NOT NULL THEN 'deleted' WHEN is_active = 1 THEN 'active' ELSE 'inactive' END as display_status FROM categories ORDER BY deleted_at IS NOT NULL DESC, is_active DESC, category_name ASC");
                $has_categories = false;
                while ($row = mysqli_fetch_assoc($categories_query)) {
                    $has_categories = true;
                    $status_badge = $row['is_active'] ? '<span class=\'badge bg-success-subtle text-success\'>Active</span>' : '<span class=\'badge bg-secondary-subtle text-secondary\'>Inactive</span>';
                ?>
                <tr data-category-id="<?= (int)$row['category_id'] ?>" data-status="<?= $row['is_active'] ? 'active' : 'inactive' ?>">
                    <td><?= htmlspecialchars($row['category_name']) ?></td>
                    <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
                    <td><?= $status_badge ?></td>
                    <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                    <td class="text-end">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary me-1" title="Edit" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?= (int)$row['category_id'] ?>"><i class="fa fa-edit"></i></button>
                            <form method="POST" action="src/services/categories/delete_category.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                <input type="hidden" name="category_id" value="<?= (int)$row['category_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fa fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <!-- Edit Modal for this row -->
                <div class="modal fade" id="editCategoryModal<?= (int)$row['category_id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="src/services/categories/edit_category.php" method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Category</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="category_id" value="<?= (int)$row['category_id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Category Name</label>
                                        <input type="text" class="form-control" name="category_name" value="<?= htmlspecialchars($row['category_name']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="2"><?= htmlspecialchars($row['description']) ?></textarea>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="editCategoryActive<?= (int)$row['category_id'] ?>" <?= $row['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="editCategoryActive<?= (int)$row['category_id'] ?>">Active</label>
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
                if (!$has_categories): ?>
                <tr><td colspan="5"><div class='alert alert-warning text-center p-2 rounded-2 mt-2 mb-2'>No categories found</div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Category Modal (structure only) -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="src/services/categories/add_category.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="addCategoryActive" checked>
                        <label class="form-check-label" for="addCategoryActive">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter logic for categories (client-side only)
    document.querySelectorAll('#category-filters .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#category-filters .nav-link').forEach(function(l) { l.classList.remove('active'); });
            link.classList.add('active');
            var filter = link.getAttribute('data-filter');
            document.querySelectorAll('#category-table-body tr').forEach(function(row) {
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
