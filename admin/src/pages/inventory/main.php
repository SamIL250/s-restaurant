<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Inventory Items</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Inventory Items</h2>
        </div>
    </div>
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3" id="inventory-filters">
        <li class="nav-item">
            <a class="nav-link active" data-filter="all" aria-current="page" href="#">
                <span>All Items </span>
                <span class="text-body-tertiary fw-semibold">
                    (<?php
                    $total_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inventory_items WHERE is_active = 1 AND deleted_at IS NULL"))['cnt'];
                    echo $total_count;
                    ?>)
                </span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="low-stock" href="#">
                <span>Low Stock </span>
                <span class="text-body-tertiary fw-semibold">
                    (<?php
                    $low_stock_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inventory_items WHERE current_stock <= minimum_stock AND is_active = 1 AND deleted_at IS NULL"))['cnt'];
                    echo $low_stock_count;
                    ?>)
                </span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="out-of-stock" href="#">
                <span>Out of Stock </span>
                <span class="text-body-tertiary fw-semibold">
                    (<?php
                    $out_of_stock_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inventory_items WHERE current_stock = 0 AND is_active = 1 AND deleted_at IS NULL"))['cnt'];
                    echo $out_of_stock_count;
                    ?>)
                </span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="expiring" href="#">
                <span>Expiring Soon </span>
                <span class="text-body-tertiary fw-semibold">
                    (<?php
                    $expiring_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inventory_items WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE() AND is_active = 1 AND deleted_at IS NULL"))['cnt'];
                    echo $expiring_count;
                    ?>)
                </span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="deleted" href="#">
                <span>Deleted </span>
                <span class="text-body-tertiary fw-semibold">
                    (<?php
                    $deleted_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inventory_items WHERE deleted_at IS NOT NULL"))['cnt'];
                    echo $deleted_count;
                    ?>)
                </span>
            </a>
        </li>
    </ul>
    <div id="inventory" data-list='{"valueNames":["item","category","supplier","stock","cost"],"page":10,"pagination":true}'>
        <div class="mb-4">
            <div class="d-flex flex-wrap gap-3">
                <div class="search-box">
                    <form class="position-relative"><input class="form-control search-input search" type="search" placeholder="Search inventory items" aria-label="Search" />
                        <span class="fas fa-search search-box-icon"></span>
                    </form>
                </div>
                <div class="ms-xxl-auto">
                    <button class="btn btn-link text-body me-4 px-0"><span class="fa-solid fa-file-export fs-9 me-2"></span>Export</button>
                    <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'stock_clerk')): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInventoryModal"><span class="fas fa-plus me-2"></span>Add Item</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis border-top border-bottom border-translucent position-relative top-1">
            <div class="table-responsive scrollbar mx-n1 px-1">
                <table class="table fs-9 mb-0">
                    <thead>
                        <tr>
                            <th class="white-space-nowrap fs-9 align-middle ps-0" style="max-width:20px; width:18px;">
                                <div class="form-check mb-0 fs-8"><input class="form-check-input" id="checkbox-bulk-inventory-select" type="checkbox" data-bulk-select='{"body":"inventory-table-body"}' /></div>
                            </th>
                            <th class="sort white-space-nowrap align-middle ps-4" scope="col" style="width:200px;" data-sort="item">ITEM NAME</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="category" style="width:150px;">CATEGORY</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="supplier" style="width:150px;">SUPPLIER</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="stock" style="width:120px;">STOCK</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="cost" style="width:120px;">UNIT COST</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="total_stock" style="width:140px;">TOTAL STOCK</th>
                            <th class="sort text-end align-middle pe-0 ps-4" scope="col"></th>
                        </tr>
                    </thead>
                    <tbody class="list" id="inventory-table-body">
                        <?php
                        $inventory_query = mysqli_query($conn, "
                            SELECT i.*, c.category_name, s.supplier_name,
                                   CASE WHEN i.deleted_at IS NOT NULL THEN 'deleted' 
                                        WHEN i.current_stock <= i.minimum_stock THEN 'low-stock'
                                        WHEN i.current_stock = 0 THEN 'out-of-stock'
                                        WHEN i.expiry_date IS NOT NULL AND i.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND i.expiry_date >= CURDATE() THEN 'expiring'
                                        ELSE 'normal' END as display_status
                            FROM inventory_items i 
                            LEFT JOIN categories c ON i.category_id = c.category_id 
                            LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id 
                            ORDER BY i.deleted_at IS NOT NULL DESC, i.is_active DESC, i.item_name
                        ");
                        $has_items = false;
                        foreach ($inventory_query as $item) {
                            $has_items = true;
                            
                            // Determine item statuses for filtering
                            $item_statuses = [];
                            if ($item['current_stock'] == 0) {
                                $item_statuses[] = 'out-of-stock';
                            }
                            if ($item['current_stock'] <= $item['minimum_stock']) {
                                $item_statuses[] = 'low-stock';
                            }
                            if ($item['expiry_date'] && $item['expiry_date'] <= date('Y-m-d', strtotime('+30 days')) && $item['expiry_date'] >= date('Y-m-d')) {
                                $item_statuses[] = 'expiring';
                            }
                            if (empty($item_statuses)) {
                                $item_statuses[] = 'normal';
                            }
                            $item_status = implode(' ', $item_statuses);
                            
                            // Stock status badge
                            $stock_badge = '';
                            if ($item['current_stock'] == 0) {
                                $stock_badge = '<span class="badge bg-danger-subtle text-danger">Out of Stock</span>';
                            } elseif ($item['current_stock'] <= $item['minimum_stock']) {
                                $stock_badge = '<span class="badge bg-warning-subtle text-warning">Low Stock</span>';
                            } else {
                                $stock_badge = '<span class="badge bg-success-subtle text-success">In Stock</span>';
                            }
                        ?>
                        <tr class="position-static" data-item-id="<?= (int)$item['item_id'] ?>" data-status="<?= $item['display_status'] ?>">
                            <td class="fs-9 align-middle">
                                <div class="form-check mb-0 fs-8">
                                    <input class="form-check-input" type="checkbox" data-bulk-select-row='{"item":"<?= htmlspecialchars($item['item_name']) ?>"}' />
                                </div>
                            </td>
                            <td class="item align-middle ps-4 fw-semibold">
                                <?= htmlspecialchars($item['item_name']) ?>
                            </td>
                            <td class="category align-middle ps-4 text-muted">
                                <?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?>
                            </td>
                            <td class="supplier align-middle ps-4 text-muted">
                                <?= htmlspecialchars($item['supplier_name'] ?? 'No Supplier') ?>
                            </td>
                            <td class="stock align-middle ps-4">
                                <?php if ($item['deleted_at']): ?>
                                    <span class='badge bg-danger-subtle text-danger'>Deleted</span>
                                <?php else: ?>
                                    <div class="d-flex align-items-center">
                                        <span class="fw-semibold"><?= $item['current_stock'] ?> <?= $item['unit_of_measure'] ?></span>
                                        <div class="ms-2"><?= $stock_badge ?></div>
                                    </div>
                                    <?php if ($item['minimum_stock'] > 0): ?>
                                    <small class="text-muted">Min: <?= $item['minimum_stock'] ?> <?= $item['unit_of_measure'] ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="cost align-middle ps-4 fw-semibold">
                                <?php if ($item['deleted_at']): ?>
                                    <small class="text-muted">Deleted: <?= date('Y-m-d H:i', strtotime($item['deleted_at'])) ?></small>
                                <?php else: ?>
                                    Frw <?= number_format($item['unit_cost'], 2) ?>
                                <?php endif; ?>
                            </td>
                            <td class="total_stock align-middle ps-4 fw-semibold">
                                <?php if ($item['deleted_at']): ?>
                                    -
                                <?php else: ?>
                                    Frw <?= number_format($item['current_stock'] * $item['unit_cost'], 2) ?>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                <?php if ($item['deleted_at']): ?>
                                    <form method="POST" action="src/services/utils/restore_item.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to restore this inventory item?');">
                                        <input type="hidden" name="table_name" value="inventory_items">
                                        <input type="hidden" name="id_column" value="item_id">
                                        <input type="hidden" name="id_value" value="<?= (int)$item['item_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Restore"><i class="fa fa-undo"></i></button>
                                    </form>
                                <?php else: ?>
                                    <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'stock_clerk')): ?>
                                    <div class="btn-reveal-trigger position-static"><button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs-10" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent"><span class="fas fa-ellipsis-h fs-10"></span></button>
                                        <div class="dropdown-menu dropdown-menu-end py-2">
                                            <a class="dropdown-item" href="#" onclick="editItem(<?= $item['item_id'] ?>)">Edit</a>
                                            <a class="dropdown-item" href="#" onclick="updateStock(<?= $item['item_id'] ?>)">Update Stock</a>
                                            <a class="dropdown-item text-danger" href="#" onclick="deleteItem(<?= $item['item_id'] ?>)">Delete</a>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted small">Read only</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php }
                        if (!$has_items): ?>
                        <tr><td colspan="7"><div class='alert alert-info text-center p-2 rounded-2 mt-2 mb-2'>No inventory items found</div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="row align-items-center justify-content-between py-2 pe-0 fs-9">
                <div class="col-auto d-flex">
                    <p class="mb-0 d-none d-sm-block me-3 fw-semibold text-body" data-list-info="data-list-info"></p>
                </div>
                <div class="col-auto d-flex"><button class="page-link" data-list-pagination="prev"><span class="fas fa-chevron-left"></span></button>
                    <ul class="mb-0 pagination"></ul><button class="page-link pe-0" data-list-pagination="next"><span class="fas fa-chevron-right"></span></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Inventory Item Modal -->
<div class="modal fade" id="addInventoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="./src/services/inventory/add_inventory_item.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Inventory Item</h5>
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
                                    while ($category = mysqli_fetch_assoc($categories_query)) {
                                        echo "<option value='{$category['category_id']}'>{$category['category_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Supplier</label>
                                <select class="form-select" name="supplier_id">
                                    <option value="">Select Supplier</option>
                                    <?php
                                    $suppliers_query = mysqli_query($conn, "SELECT * FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
                                    while ($supplier = mysqli_fetch_assoc($suppliers_query)) {
                                        echo "<option value='{$supplier['supplier_id']}'>{$supplier['supplier_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Unit of Measure</label>
                                <select class="form-select" name="unit_of_measure" required>
                                    <option value="">Select Unit</option>
                                    <option value="kg">Kilograms (kg)</option>
                                    <option value="lbs">Pounds (lbs)</option>
                                    <option value="liters">Liters</option>
                                    <option value="pieces">Pieces</option>
                                    <option value="boxes">Boxes</option>
                                    <option value="bottles">Bottles</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Current Stock</label>
                                <input type="number" step="0.01" class="form-control" name="current_stock" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Minimum Stock</label>
                                <input type="number" step="0.01" class="form-control" name="minimum_stock" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Unit Cost</label>
                                <input type="number" step="0.01" class="form-control" name="unit_cost" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" name="expiry_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Maximum Stock</label>
                                <input type="number" step="0.01" class="form-control" name="maximum_stock">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Inventory Item Modal -->
<div class="modal fade" id="editInventoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editInventoryForm" action="./src/services/inventory/edit_inventory_item.php" method="POST">
                <input type="hidden" name="item_id" id="editItemId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Inventory Item</h5>
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
                                    mysqli_data_seek($categories_query, 0);
                                    while ($category = mysqli_fetch_assoc($categories_query)) {
                                        echo "<option value='{$category['category_id']}'>{$category['category_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Supplier</label>
                                <select class="form-select" name="supplier_id" id="editSupplierId">
                                    <option value="">Select Supplier</option>
                                    <?php
                                    mysqli_data_seek($suppliers_query, 0);
                                    while ($supplier = mysqli_fetch_assoc($suppliers_query)) {
                                        echo "<option value='{$supplier['supplier_id']}'>{$supplier['supplier_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Unit of Measure</label>
                                <select class="form-select" name="unit_of_measure" id="editUnitOfMeasure" required>
                                    <option value="">Select Unit</option>
                                    <option value="kg">Kilograms (kg)</option>
                                    <option value="lbs">Pounds (lbs)</option>
                                    <option value="liters">Liters</option>
                                    <option value="pieces">Pieces</option>
                                    <option value="boxes">Boxes</option>
                                    <option value="bottles">Bottles</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Current Stock</label>
                                <input type="number" step="0.01" class="form-control" name="current_stock" id="editCurrentStock" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Minimum Stock</label>
                                <input type="number" step="0.01" class="form-control" name="minimum_stock" id="editMinimumStock" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Unit Cost</label>
                                <input type="number" step="0.01" class="form-control" name="unit_cost" id="editUnitCost" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" name="expiry_date" id="editExpiryDate">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Maximum Stock</label>
                                <input type="number" step="0.01" class="form-control" name="maximum_stock" id="editMaximumStock">
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

<!-- Update Stock Modal -->
<div class="modal fade" id="updateStockModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="updateStockForm" action="./src/services/inventory/update_stock.php" method="POST">
                <input type="hidden" name="item_id" id="updateStockItemId">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Movement Type</label>
                                <select class="form-select" name="movement_type" id="movementType" required>
                                    <option value="in">Stock In</option>
                                    <option value="out">Stock Out</option>
                                    <option value="adjustment">Adjustment</option>
                                    <option value="waste">Waste</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" step="0.01" class="form-control" name="quantity" id="movementQuantity" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Price Management Section -->
                    <div class="row" id="priceManagementSection">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Current Unit Cost (Frw)</label>
                                <input type="number" step="0.01" class="form-control" id="currentUnitCost" readonly>
                                <small class="text-muted">Current cost in database</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Current Stock</label>
                                <input type="number" step="0.01" class="form-control" id="currentStock" readonly>
                                <small class="text-muted">Available stock before this movement</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">New Unit Cost (Frw)</label>
                                <input type="number" step="0.01" class="form-control" name="new_unit_cost" id="newUnitCost" placeholder="Leave empty to use current cost">
                                <small class="text-muted">New market price (optional)</small>
                            </div>
                        </div>

                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Update Inventory Cost?</label>
                                <select class="form-select" name="update_inventory_cost" id="updateInventoryCost">
                                    <option value="0">No - Keep current cost</option>
                                    <option value="1">Yes - Update to new cost</option>
                                </select>
                                <small class="text-muted">Choose whether to update the item's base cost</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cost Update Method</label>
                                <select class="form-select" name="cost_update_method" id="costUpdateMethod" disabled>
                                    <option value="replace">Replace current cost</option>
                                    <option value="weighted_average">Weighted average</option>
                                </select>
                                <small class="text-muted">How to handle the cost update</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Protection Options -->
                    <div class="row" id="protectionOptions" style="display: none;">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Protect Existing Stock Value?</label>
                                <select class="form-select" name="protect_existing_value" id="protectExistingValue">
                                    <option value="1">Yes - Never decrease current cost</option>
                                    <option value="0">No - Allow cost decreases</option>
                                </select>
                                <small class="text-muted">Prevents devaluing existing inventory when new stock is cheaper</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="Enter reason for stock movement (e.g., 'Market price increased to 120 Frw/kg')"></textarea>
                    </div>
                    
                    <!-- Cost Preview -->
                    <div class="alert alert-info" id="costPreview" style="display: none;">
                        <strong>Cost Preview:</strong>
                        <div id="costPreviewContent"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden form for delete -->
<form id="deleteInventoryForm" action="./src/services/inventory/delete_inventory_item.php" method="POST" style="display:none;">
    <input type="hidden" name="item_id" id="deleteItemId">
</form>

<script>
function editItem(itemId) {
    // Fetch item data and populate edit modal
    fetch('./src/services/inventory/get_inventory_item.php?id=' + itemId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('editItemId').value = data.item_id;
            document.getElementById('editItemName').value = data.item_name;
            document.getElementById('editCategoryId').value = data.category_id || '';
            document.getElementById('editSupplierId').value = data.supplier_id || '';
            document.getElementById('editUnitOfMeasure').value = data.unit_of_measure;
            document.getElementById('editCurrentStock').value = data.current_stock;
            document.getElementById('editMinimumStock').value = data.minimum_stock;
            document.getElementById('editUnitCost').value = data.unit_cost;
            document.getElementById('editExpiryDate').value = data.expiry_date || '';
            document.getElementById('editMaximumStock').value = data.maximum_stock || '';
            
            var modal = new bootstrap.Modal(document.getElementById('editInventoryModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading item data');
        });
}

function updateStock(itemId) {
    // Fetch item data to populate the modal
    fetch('./src/services/inventory/get_inventory_item.php?id=' + itemId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('updateStockItemId').value = itemId;
            document.getElementById('currentUnitCost').value = data.unit_cost;
            document.getElementById('currentStock').value = data.current_stock;
            document.getElementById('newUnitCost').value = '';
            document.getElementById('updateInventoryCost').value = '0';
            document.getElementById('costUpdateMethod').disabled = true;
            document.getElementById('costPreview').style.display = 'none';
            
            var modal = new bootstrap.Modal(document.getElementById('updateStockModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading item data');
        });
}

// Add event listeners for price management
document.addEventListener('DOMContentLoaded', function() {
    const updateInventoryCost = document.getElementById('updateInventoryCost');
    const costUpdateMethod = document.getElementById('costUpdateMethod');
    const newUnitCost = document.getElementById('newUnitCost');
    const movementQuantity = document.getElementById('movementQuantity');
    const costPreview = document.getElementById('costPreview');
    const costPreviewContent = document.getElementById('costPreviewContent');
    
    // Enable/disable cost update method based on selection
    if (updateInventoryCost) {
        updateInventoryCost.addEventListener('change', function() {
            costUpdateMethod.disabled = this.value === '0';
            const protectionOptions = document.getElementById('protectionOptions');
            
            if (this.value === '1') {
                protectionOptions.style.display = 'block';
            } else {
                protectionOptions.style.display = 'none';
                costUpdateMethod.value = 'replace';
                costPreview.style.display = 'none';
            }
        });
    }
    
    // Show cost preview when values change
    function updateCostPreview() {
        const currentCost = parseFloat(document.getElementById('currentUnitCost').value) || 0;
        const newCost = parseFloat(newUnitCost.value) || 0;
        const quantity = parseFloat(movementQuantity.value) || 0;
        const updateCost = updateInventoryCost.value === '1';
        const method = costUpdateMethod.value;
        const protectValue = document.getElementById('protectExistingValue')?.value === '1';
        
        if (updateCost && newCost > 0 && quantity > 0) {
            let previewText = '';
            let canUpdate = true;
            
            // Check if new cost would decrease existing value
            if (newCost < currentCost && protectValue) {
                canUpdate = false;
                previewText = `<span class="text-warning">⚠️ New cost (${newCost.toFixed(2)} Frw) is lower than current (${currentCost.toFixed(2)} Frw).</span><br>
                              <span class="text-info">Existing stock value will be protected. New stock will be recorded at ${newCost.toFixed(2)} Frw, but base cost remains ${currentCost.toFixed(2)} Frw.</span>`;
            } else if (method === 'replace') {
                previewText = `New base cost will be: <strong>${newCost.toFixed(2)} Frw</strong>`;
            } else if (method === 'weighted_average') {
                const currentStock = parseFloat(document.getElementById('currentStock').value) || 0;
                if (currentStock > 0) {
                    const totalValue = (currentStock * currentCost) + (quantity * newCost);
                    const totalStock = currentStock + quantity;
                    const weightedAvg = totalValue / totalStock;
                    
                    if (weightedAvg < currentCost && protectValue) {
                        previewText = `<span class="text-warning">⚠️ Weighted average (${weightedAvg.toFixed(2)} Frw) would decrease current cost (${currentCost.toFixed(2)} Frw).</span><br>
                                      <span class="text-info">Existing stock value will be protected. New stock recorded at ${newCost.toFixed(2)} Frw, base cost remains ${currentCost.toFixed(2)} Frw.</span>`;
                    } else {
                        previewText = `Weighted average cost will be: <strong>${weightedAvg.toFixed(2)} Frw</strong><br>
                                      (Current: ${currentStock} × ${currentCost} + New: ${quantity} × ${newCost})`;
                    }
                }
            }
            
            if (previewText) {
                costPreviewContent.innerHTML = previewText;
                costPreview.style.display = 'block';
            }
        } else {
            costPreview.style.display = 'none';
        }
    }
    
    // Add event listeners for cost preview
    if (newUnitCost) newUnitCost.addEventListener('input', updateCostPreview);
    if (movementQuantity) movementQuantity.addEventListener('input', updateCostPreview);
    if (costUpdateMethod) costUpdateMethod.addEventListener('change', updateCostPreview);
    
    // Filter logic
    document.querySelectorAll('#inventory-filters .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#inventory-filters .nav-link').forEach(function(l) { l.classList.remove('active'); });
            link.classList.add('active');
            var filter = link.getAttribute('data-filter');
            document.querySelectorAll('#inventory-table-body tr').forEach(function(row) {
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
});

function deleteItem(itemId) {
    if (confirm('Are you sure you want to delete this inventory item? This action cannot be undone.')) {
        document.getElementById('deleteItemId').value = itemId;
        document.getElementById('deleteInventoryForm').submit();
    }
}
</script>
