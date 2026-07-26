<?php
// Product Details for Admin Panel
?>

<div class="row g-4">
    <!-- Product Images and Basic Info -->
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-auto">
                        <h5 class="mb-0">Product Images</h5>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addImageModal">
                            <span class="fas fa-plus me-2"></span>Add Image
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php
                    $images = $product_data['image_urls'] ? explode(',', $product_data['image_urls']) : [];
                    foreach ($images as $index => $url) {
                        $url = trim($url);
                        if (!empty($url)) {
                    ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="position-relative">
                                <img src="<?= htmlspecialchars($url) ?>" class="img-fluid rounded" alt="Product Image">
                                <button class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" 
                                        onclick="deleteImage('<?= htmlspecialchars($url) ?>')">
                                    <span class="fas fa-times"></span>
                                </button>
                            </div>
                        </div>
                    <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Statistics -->
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-auto">
                        <h5 class="mb-0">Product Statistics</h5>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#updateStockModal">
                            <span class="fas fa-boxes me-2"></span>Update Stock
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-primary mb-1"><?= number_format($order_stats['total_orders'] ?? 0) ?></h4>
                            <p class="text-muted mb-0 fs--1">Total Orders</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-success mb-1"><?= number_format($order_stats['total_quantity_sold'] ?? 0) ?></h4>
                            <p class="text-muted mb-0 fs--1">Units Sold</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-warning mb-1"><?= number_format($order_stats['total_revenue'] ?? 0) ?></h4>
                            <p class="text-muted mb-0 fs--1">Total Revenue</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-info mb-1"><?= number_format($product_data['stock_quantity']) ?></h4>
                            <p class="text-muted mb-0 fs--1">In Stock</p>
                        </div>
                    </div>
                </div>
                
                <!-- Stock Status Alert -->
                <div class="mt-3">
                    <?php if ($product_data['stock_quantity'] <= 10 && $product_data['stock_quantity'] > 0): ?>
                        <div class="alert alert-warning py-2 mb-0">
                            <div class="d-flex align-items-center">
                                <span class="fas fa-exclamation-triangle me-2"></span>
                                <small class="mb-0">Low stock alert: Only <?= $product_data['stock_quantity'] ?> units remaining</small>
                            </div>
                        </div>
                    <?php elseif ($product_data['stock_quantity'] == 0): ?>
                        <div class="alert alert-danger py-2 mb-0">
                            <div class="d-flex align-items-center">
                                <span class="fas fa-times-circle me-2"></span>
                                <small class="mb-0">Out of stock</small>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success py-2 mb-0">
                            <div class="d-flex align-items-center">
                                <span class="fas fa-check-circle me-2"></span>
                                <small class="mb-0">Stock level is good</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Information -->
<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-auto">
                        <h5 class="mb-0">Product Information</h5>
                    </div>
                    <div class="col-auto">
                        <a href="products-edit?product=<?= $product_data['product_id'] ?>" class="btn btn-warning btn-sm">
                            <span class="fas fa-edit me-2"></span>Edit Product
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold" style="width: 40%">Product Name:</td>
                                <td><?= htmlspecialchars($product_data['name']) ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">SKU:</td>
                                <td><?= htmlspecialchars($product_data['sku']) ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Category:</td>
                                <td><?= htmlspecialchars($product_data['category_name'] ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Brand:</td>
                                <td><?= htmlspecialchars($product_data['brand_name'] ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Base Price:</td>
                                <td><?= number_format($product_data['base_price'], 2) ?> RWF</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Discount Price:</td>
                                <td><?= $product_data['discount_price'] ? number_format($product_data['discount_price'], 2) . ' RWF' : 'N/A' ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold" style="width: 40%">Stock Quantity:</td>
                                <td>
                                    <span class="badge badge-phoenix badge-phoenix-<?= $product_data['stock_quantity'] > 0 ? 'success' : 'danger' ?>">
                                        <?= number_format($product_data['stock_quantity']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Status:</td>
                                <td>
                                    <span class="badge badge-phoenix badge-phoenix-<?= $product_data['is_active'] ? 'success' : 'danger' ?>">
                                        <?= $product_data['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Featured:</td>
                                <td>
                                    <span class="badge badge-phoenix badge-phoenix-<?= $product_data['is_featured'] ? 'warning' : 'secondary' ?>">
                                        <?= $product_data['is_featured'] ? 'Featured' : 'Not Featured' ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Published:</td>
                                <td>
                                    <span class="badge badge-phoenix badge-phoenix-<?= $product_data['published'] == 'true' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($product_data['published']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Created:</td>
                                <td><?= date('M d, Y H:i', strtotime($product_data['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Updated:</td>
                                <td><?= date('M d, Y H:i', strtotime($product_data['updated_at'])) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if (!empty($product_data['description'])): ?>
                <div class="mt-4">
                    <h6 class="fw-bold">Description:</h6>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($product_data['description'])) ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($product_data['attributes'])): ?>
                <div class="mt-4">
                    <h6 class="fw-bold">Attributes:</h6>
                    <div class="row g-2">
                        <?php
                        $attributes = explode(',', $product_data['attributes']);
                        foreach ($attributes as $attribute) {
                            $attribute = trim($attribute);
                            if (!empty($attribute)) {
                        ?>
                            <div class="col-auto">
                                <span class="badge badge-phoenix badge-phoenix-info"><?= htmlspecialchars($attribute) ?></span>
                            </div>
                        <?php
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($product_data['tags'])): ?>
                <div class="mt-4">
                    <h6 class="fw-bold">Tags:</h6>
                    <div class="row g-2">
                            <?php
                        $tags = explode(',', $product_data['tags']);
                        foreach ($tags as $tag) {
                            $tag = trim($tag);
                            if (!empty($tag)) {
                            ?>
                            <div class="col-auto">
                                <span class="badge badge-phoenix badge-phoenix-secondary"><?= htmlspecialchars($tag) ?></span>
                        </div>
                        <?php
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders for this Product -->
<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Orders</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recent_orders)): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>
                                    <a href="order-details?order=<?= $order['order_id'] ?>" class="fw-bold">
                                        <?= $order['order_id'] ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td><?= $order['quantity'] ?></td>
                                <td><?= number_format($order['unit_price'], 2) ?> RWF</td>
                                <td><?= number_format($order['quantity'] * $order['unit_price'], 2) ?> RWF</td>
                                <td>
                                    <span class="badge badge-phoenix badge-phoenix-<?= 
                                        match($order['status']) {
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'shipped' => 'secondary',
                                            'delivered' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'primary'
                                        };
                                    ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-muted mb-0">No orders found for this product.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Image Modal -->
<div class="modal fade" id="addImageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Product Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="src/services/products/add_image.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="product_id" value="<?= $product_data['product_id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Image URL</label>
                        <input type="url" class="form-control" name="image_url" placeholder="https://example.com/image.jpg">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alt Text</label>
                        <input type="text" class="form-control" name="alt_text" placeholder="Product image description">
                </div>
                    <div class="mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" class="form-control" name="display_order" value="0" min="0">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_primary" id="isPrimary">
                        <label class="form-check-label" for="isPrimary">
                            Set as primary image
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Image</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Stock Modal -->
<div class="modal fade" id="updateStockModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <span class="fas fa-boxes me-2 text-success"></span>
                    Update Stock - <?= htmlspecialchars($product_data['name']) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updateStockForm" action="src/services/products/update_stock.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="product_id" value="<?= $product_data['product_id'] ?>">
                    
                    <!-- Current Stock Display -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light border-0">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-1">Current Stock</h6>
                                    <h3 class="text-primary mb-0"><?= number_format($product_data['stock_quantity']) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light border-0">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-1">SKU</h6>
                                    <h6 class="text-dark mb-0"><?= htmlspecialchars($product_data['sku']) ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Change Section -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <span class="fas fa-plus-minus me-2 text-success"></span>
                                Stock Change
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="fas fa-boxes"></span>
                                </span>
                                <input type="number" 
                                       class="form-control" 
                                       name="stock_change" 
                                       id="stockChange"
                                       placeholder="Enter quantity (use + for add, - for remove)"
                                       required>
                                <span class="input-group-text">units</span>
                            </div>
                            <div class="form-text">
                                <small class="text-muted">
                                    <span class="fas fa-info-circle me-1"></span>
                                    Use positive numbers to add stock, negative to remove
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <span class="fas fa-tag me-2 text-warning"></span>
                                Reason for Change
                            </label>
                            <select class="form-select" name="reason" required>
                                <option value="">Select a reason</option>
                                <option value="restock">🔄 Restock</option>
                                <option value="damage">💥 Damage/Loss</option>
                                <option value="return">↩️ Customer Return</option>
                                <option value="adjustment">⚖️ Inventory Adjustment</option>
                                <option value="quality">🔍 Quality Control</option>
                                <option value="other">📝 Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">
                                <span class="fas fa-bolt me-2 text-info"></span>
                                Quick Actions
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="setQuickAction(10)">
                                    <span class="fas fa-plus me-1"></span>Add 10
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="setQuickAction(50)">
                                    <span class="fas fa-plus me-1"></span>Add 50
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="setQuickAction(100)">
                                    <span class="fas fa-plus me-1"></span>Add 100
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="setQuickAction(-5)">
                                    <span class="fas fa-minus me-1"></span>Remove 5
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="clearStockChange()">
                                    <span class="fas fa-eraser me-1"></span>Clear
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="mt-4">
                        <label class="form-label fw-bold">
                            <span class="fas fa-sticky-note me-2 text-secondary"></span>
                            Additional Notes
                        </label>
                        <textarea class="form-control" 
                                  name="notes" 
                                  rows="3" 
                                  placeholder="Add any additional notes about this stock change..."></textarea>
                    </div>

                    <!-- Preview Section -->
                    <div class="mt-4" id="stockPreview" style="display: none;">
                        <div class="alert alert-info">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <strong>Preview:</strong>
                                    <span id="previewText"></span>
                                </div>
                                <div class="col-md-6 text-end">
                                    <span class="badge bg-primary" id="previewBadge"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <span class="fas fa-times me-2"></span>Cancel
                    </button>
                    <button type="submit" class="btn btn-success" id="submitStockBtn">
                        <span class="fas fa-save me-2"></span>Update Stock
                    </button>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteImage(imageUrl) {
    if (confirm('Are you sure you want to delete this image?')) {
        fetch('src/services/products/delete_image.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: '<?= $product_data['product_id'] ?>',
                image_url: imageUrl
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting image: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting image');
        });
    }
}

// Stock update functions
function setQuickAction(amount) {
    document.getElementById('stockChange').value = amount;
    updateStockPreview();
}

function clearStockChange() {
    document.getElementById('stockChange').value = '';
    document.getElementById('stockPreview').style.display = 'none';
}

function updateStockPreview() {
    const stockChange = parseInt(document.getElementById('stockChange').value) || 0;
    const currentStock = <?= $product_data['stock_quantity'] ?>;
    const newStock = currentStock + stockChange;
    const previewDiv = document.getElementById('stockPreview');
    const previewText = document.getElementById('previewText');
    const previewBadge = document.getElementById('previewBadge');
    
    if (stockChange !== 0) {
        previewDiv.style.display = 'block';
        previewText.textContent = `${currentStock} + ${stockChange} = ${newStock} units`;
        
        if (newStock < 0) {
            previewBadge.textContent = 'Invalid: Negative Stock';
            previewBadge.className = 'badge bg-danger';
            document.getElementById('submitStockBtn').disabled = true;
        } else if (newStock === 0) {
            previewBadge.textContent = 'Out of Stock';
            previewBadge.className = 'badge bg-warning';
            document.getElementById('submitStockBtn').disabled = false;
        } else if (newStock <= 10) {
            previewBadge.textContent = 'Low Stock';
            previewBadge.className = 'badge bg-warning';
            document.getElementById('submitStockBtn').disabled = false;
        } else {
            previewBadge.textContent = 'Good Stock Level';
            previewBadge.className = 'badge bg-success';
            document.getElementById('submitStockBtn').disabled = false;
        }
    } else {
        previewDiv.style.display = 'none';
        document.getElementById('submitStockBtn').disabled = true;
    }
}

// Add event listeners
    document.addEventListener('DOMContentLoaded', function() {
    const stockChangeInput = document.getElementById('stockChange');
    if (stockChangeInput) {
        stockChangeInput.addEventListener('input', updateStockPreview);
    }
    
    // Form submission handling
    const updateStockForm = document.getElementById('updateStockForm');
    if (updateStockForm) {
        updateStockForm.addEventListener('submit', function(e) {
            const stockChange = parseInt(document.getElementById('stockChange').value) || 0;
            const reason = document.querySelector('select[name="reason"]').value;
            
            if (stockChange === 0) {
                e.preventDefault();
                alert('Please enter a valid stock change amount.');
                return;
            }
            
            if (!reason) {
                e.preventDefault();
                alert('Please select a reason for the stock change.');
                return;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitStockBtn');
            submitBtn.innerHTML = '<span class="fas fa-spinner fa-spin me-2"></span>Updating...';
            submitBtn.disabled = true;
        });
    }
    });
</script>