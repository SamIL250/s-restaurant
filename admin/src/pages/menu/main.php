<?php
// Menu Items Management Page
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Menu Items</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Menu Items</h2>
        </div>
        <div class="col-auto ms-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuItemModal"><span class="fas fa-plus me-2"></span>Add Menu Item</button>
        </div>
    </div>
    <?php
    $all_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM menu_items WHERE deleted_at IS NULL"))['cnt'];
    $available_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM menu_items WHERE is_available = 1 AND deleted_at IS NULL"))['cnt'];
    $unavailable_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM menu_items WHERE is_available = 0 AND deleted_at IS NULL"))['cnt'];
    $deleted_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM menu_items WHERE deleted_at IS NOT NULL"))['cnt'];
    ?>
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3" id="menu-filters">
        <li class="nav-item">
            <a class="nav-link active" data-filter="all" aria-current="page" href="#">
                <span>All Items </span>
                <span class="text-body-tertiary fw-semibold">(<?= $all_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="available" href="#">
                <span>Available </span>
                <span class="text-body-tertiary fw-semibold">(<?= $available_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="unavailable" href="#">
                <span>Unavailable </span>
                <span class="text-body-tertiary fw-semibold">(<?= $unavailable_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="deleted" href="#">
                <span>Deleted </span>
                <span class="text-body-tertiary fw-semibold">(<?= $deleted_count ?>)</span>
            </a>
        </li>
    </ul>
    <div class="mb-4">
        <div class="d-flex flex-wrap gap-3">
            <div class="search-box">
                <form class="position-relative"><input class="form-control search-input search" type="search" placeholder="Search menu items" aria-label="Search" id="menu-search-input" />
                    <span class="fas fa-search search-box-icon"></span>
                </form>
            </div>
        </div>
    </div>
    <div class="table-responsive bg-body-emphasis border-top border-bottom border-translucent position-relative top-1 p-3">
        <table class="table fs-9 mb-0">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price (Frw)</th>
                    <th>Cost (Frw)</th>
                    <th>Prep Time (min)</th>
                    <th>Calories</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="menu-table-body">
                <?php
                $menu_query = mysqli_query($conn, "SELECT mi.*, c.category_name, CASE WHEN mi.deleted_at IS NOT NULL THEN 'deleted' WHEN mi.is_available = 1 THEN 'available' ELSE 'unavailable' END as display_status FROM menu_items mi LEFT JOIN categories c ON mi.category_id = c.category_id ORDER BY mi.deleted_at IS NOT NULL DESC, mi.is_available DESC, mi.item_name ASC");
                $has_menu = false;
                while ($row = mysqli_fetch_assoc($menu_query)) {
                    $has_menu = true;
                    $status_badge = $row['is_available'] ? '<span class=\'badge bg-success-subtle text-success\'>Available</span>' : '<span class=\'badge bg-secondary-subtle text-secondary\'>Unavailable</span>';
                ?>
                <tr data-menu-id="<?= (int)$row['menu_item_id'] ?>" data-status="<?= $row['display_status'] ?>">
                    <td>
                        <?php if (!empty($row['image_url'])): ?>
                            <img src="<?= htmlspecialchars($row['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($row['item_name']) ?>" 
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'60\' height=\'60\'%3E%3Crect fill=\'%23ddd\' width=\'60\' height=\'60\'/%3E%3Ctext fill=\'%23999\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                        <?php else: ?>
                            <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 10px;">No Image</div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['item_name']) ?></td>
                    <td><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
                    <td><?= number_format($row['price'], 2) ?></td>
                    <td><?= number_format($row['cost'], 2) ?></td>
                    <td><?= htmlspecialchars($row['preparation_time'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['calories'] ?? '-') ?></td>
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
                            <form method="POST" action="src/services/utils/restore_item.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to restore this menu item?');">
                                <input type="hidden" name="table_name" value="menu_items">
                                <input type="hidden" name="id_column" value="menu_item_id">
                                <input type="hidden" name="id_value" value="<?= (int)$row['menu_item_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Restore"><i class="fa fa-undo"></i></button>
                            </form>
                        <?php else: ?>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary me-1" title="Edit" onclick="editMenuItem(<?= (int)$row['menu_item_id'] ?>)"><i class="fa fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteMenuItem(<?= (int)$row['menu_item_id'] ?>)"><i class="fa fa-trash"></i></button>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php }
                if (!$has_menu): ?>
                <tr><td colspan="10"><div class='alert alert-warning text-center p-2 rounded-2 mt-2 mb-2'>No menu items found</div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.menu-image-cell {
    width: 80px;
}
.menu-image-cell img {
    cursor: pointer;
    transition: transform 0.2s;
}
.menu-image-cell img:hover {
    transform: scale(1.1);
}
</style>

<!-- Add Menu Item Modal -->
<div class="modal fade" id="addMenuItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="./src/services/menu/add_menu_item.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Add Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Item Name</label>
                                <input type="text" class="form-control" name="item_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php
                                    $categories_query = mysqli_query($conn, "SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name");
                                    while ($cat = mysqli_fetch_assoc($categories_query)) {
                                        echo "<option value='{$cat['category_id']}'>{$cat['category_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Price (Frw)</label>
                                <input type="number" step="0.01" class="form-control" name="price" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Cost (Frw)</label>
                                <input type="number" step="0.01" class="form-control" name="cost">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Preparation Time (min)</label>
                                <input type="number" class="form-control" name="preparation_time">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Calories</label>
                                <input type="number" class="form-control" name="calories">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="is_available">
                                    <option value="1">Available</option>
                                    <option value="0">Unavailable</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item Image</label>
                        <ul class="nav nav-tabs mb-2" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload-pane" type="button" role="tab">Upload File</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="url-tab" data-bs-toggle="tab" data-bs-target="#url-pane" type="button" role="tab">Image URL</button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="upload-pane" role="tabpanel">
                                <input type="file" class="form-control" name="image_file" id="addImageFile" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                <small class="text-muted">Max size: 5MB. Supported formats: JPEG, PNG, GIF, WebP</small>
                                <div id="addImagePreview" class="mt-2" style="display: none;">
                                    <img id="addImagePreviewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 4px;">
                                </div>
                            </div>
                            <div class="tab-pane fade" id="url-pane" role="tabpanel">
                                <input type="url" class="form-control" name="image_url" id="addImageUrl" placeholder="https://example.com/image.jpg">
                                <small class="text-muted">Enter a valid image URL</small>
                                <div id="addUrlPreview" class="mt-2" style="display: none;">
                                    <img id="addUrlPreviewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 4px;" onerror="this.style.display='none';">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Menu Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Menu Item Modal -->
<div class="modal fade" id="editMenuItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editMenuItemForm" action="./src/services/menu/edit_menu_item.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="menu_item_id" id="editMenuItemId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Item Name</label>
                                <input type="text" class="form-control" name="item_name" id="editItemName" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" id="editCategoryId">
                                    <option value="">Select Category</option>
                                    <?php
                                    $categories_query2 = mysqli_query($conn, "SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name");
                                    while ($cat = mysqli_fetch_assoc($categories_query2)) {
                                        echo "<option value='{$cat['category_id']}'>{$cat['category_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Price (Frw)</label>
                                <input type="number" step="0.01" class="form-control" name="price" id="editPrice" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Cost (Frw)</label>
                                <input type="number" step="0.01" class="form-control" name="cost" id="editCost">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Preparation Time (min)</label>
                                <input type="number" class="form-control" name="preparation_time" id="editPreparationTime">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Calories</label>
                                <input type="number" class="form-control" name="calories" id="editCalories">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="is_available" id="editIsAvailable">
                                    <option value="1">Available</option>
                                    <option value="0">Unavailable</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="editDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item Image</label>
                        <div id="editCurrentImage" class="mb-2" style="display: none;">
                            <p class="text-muted small">Current Image:</p>
                            <img id="editCurrentImageImg" src="" alt="Current" style="max-width: 200px; max-height: 200px; border-radius: 4px;">
                        </div>
                        <ul class="nav nav-tabs mb-2" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="edit-upload-tab" data-bs-toggle="tab" data-bs-target="#edit-upload-pane" type="button" role="tab">Upload File</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-url-tab" data-bs-toggle="tab" data-bs-target="#edit-url-pane" type="button" role="tab">Image URL</button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="edit-upload-pane" role="tabpanel">
                                <input type="file" class="form-control" name="image_file" id="editImageFile" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                <small class="text-muted">Max size: 5MB. Supported formats: JPEG, PNG, GIF, WebP. Leave empty to keep current image.</small>
                                <div id="editImagePreview" class="mt-2" style="display: none;">
                                    <img id="editImagePreviewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 4px;">
                                </div>
                            </div>
                            <div class="tab-pane fade" id="edit-url-pane" role="tabpanel">
                                <input type="url" class="form-control" name="image_url" id="editImageUrl" placeholder="https://example.com/image.jpg">
                                <small class="text-muted">Enter a valid image URL. Leave empty to keep current image.</small>
                                <div id="editUrlPreview" class="mt-2" style="display: none;">
                                    <img id="editUrlPreviewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 4px;" onerror="this.style.display='none';">
                                </div>
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

<!-- Hidden form for delete -->
<form id="deleteMenuItemForm" action="./src/services/menu/delete_menu_item.php" method="POST" style="display:none;">
    <input type="hidden" name="menu_item_id" id="deleteMenuItemId">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter logic for menu items
    document.querySelectorAll('#menu-filters .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#menu-filters .nav-link').forEach(function(l) { l.classList.remove('active'); });
            link.classList.add('active');
            var filter = link.getAttribute('data-filter');
            document.querySelectorAll('#menu-table-body tr').forEach(function(row) {
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
    // Simple search filter
    document.getElementById('menu-search-input').addEventListener('input', function() {
        var val = this.value.toLowerCase();
        document.querySelectorAll('#menu-table-body tr').forEach(function(row) {
            var text = row.textContent.toLowerCase();
            row.style.display = text.indexOf(val) > -1 ? '' : 'none';
        });
    });
});

function editMenuItem(menuItemId) {
    fetch('./src/services/menu/get_menu_item.php?id=' + menuItemId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('editMenuItemId').value = data.menu_item_id;
            document.getElementById('editItemName').value = data.item_name;
            document.getElementById('editCategoryId').value = data.category_id || '';
            document.getElementById('editPrice').value = data.price;
            document.getElementById('editCost').value = data.cost;
            document.getElementById('editPreparationTime').value = data.preparation_time;
            document.getElementById('editCalories').value = data.calories;
            document.getElementById('editIsAvailable').value = data.is_available;
            document.getElementById('editDescription').value = data.description || '';
            
            // Handle image
            const currentImageDiv = document.getElementById('editCurrentImage');
            const currentImageImg = document.getElementById('editCurrentImageImg');
            if (data.image_url) {
                currentImageImg.src = data.image_url;
                currentImageDiv.style.display = 'block';
            } else {
                currentImageDiv.style.display = 'none';
            }
            document.getElementById('editImageUrl').value = data.image_url || '';
            document.getElementById('editImageFile').value = '';
            
            var modal = new bootstrap.Modal(document.getElementById('editMenuItemModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading menu item data');
        });
}

// Image preview handlers for add modal
document.getElementById('addImageFile')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('addImagePreviewImg').src = e.target.result;
            document.getElementById('addImagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('addImagePreview').style.display = 'none';
    }
});

document.getElementById('addImageUrl')?.addEventListener('input', function(e) {
    const url = e.target.value;
    if (url) {
        document.getElementById('addUrlPreviewImg').src = url;
        document.getElementById('addUrlPreview').style.display = 'block';
    } else {
        document.getElementById('addUrlPreview').style.display = 'none';
    }
});

// Image preview handlers for edit modal
document.getElementById('editImageFile')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('editImagePreviewImg').src = e.target.result;
            document.getElementById('editImagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('editImagePreview').style.display = 'none';
    }
});

document.getElementById('editImageUrl')?.addEventListener('input', function(e) {
    const url = e.target.value;
    if (url) {
        document.getElementById('editUrlPreviewImg').src = url;
        document.getElementById('editUrlPreview').style.display = 'block';
    } else {
        document.getElementById('editUrlPreview').style.display = 'none';
    }
});

function deleteMenuItem(menuItemId) {
    if (confirm('Are you sure you want to delete this menu item? This action cannot be undone.')) {
        document.getElementById('deleteMenuItemId').value = menuItemId;
        document.getElementById('deleteMenuItemForm').submit();
    }
}
</script>
