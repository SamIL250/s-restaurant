<?php
// Admin-focused product details tabs
?>

<section class="py-0 my-5">
    <div class="container-small">
        <ul class="nav nav-underline fs-9 mb-4" id="productTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="inventory-tab" data-bs-toggle="tab" href="#tab-inventory" role="tab">
                    <span class="fas fa-boxes me-2"></span>Inventory
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="analytics-tab" data-bs-toggle="tab" href="#tab-analytics" role="tab">
                    <span class="fas fa-chart-line me-2"></span>Analytics
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="attributes-tab" data-bs-toggle="tab" href="#tab-attributes" role="tab">
                    <span class="fas fa-tags me-2"></span>Attributes
                </a>
            </li>
        </ul>

                <div class="tab-content" id="productTabContent">
            <!-- Inventory Tab -->
            <div class="tab-pane fade show active" id="tab-inventory" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Inventory Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h3 class="text-primary mb-2"><?= number_format($product_data['stock_quantity']) ?></h3>
                                        <p class="text-muted mb-0">Current Stock</p>
                                    </div>
                                </div>
                                                </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h3 class="text-success mb-2"><?= number_format($order_stats['total_quantity_sold'] ?? 0) ?></h3>
                                        <p class="text-muted mb-0">Total Sold</p>
                                    </div>
                                </div>
                            </div>
                                        </div>
                                    </div>
                                </div>
                                </div>

            <!-- Analytics Tab -->
            <div class="tab-pane fade" id="tab-analytics" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Sales Analytics</h5>
                                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-primary"><?= number_format($order_stats['total_orders'] ?? 0) ?></h4>
                                    <p class="text-muted mb-0">Total Orders</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-success"><?= number_format($order_stats['total_quantity_sold'] ?? 0) ?></h4>
                                    <p class="text-muted mb-0">Units Sold</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-warning"><?= number_format($order_stats['total_revenue'] ?? 0) ?></h4>
                                    <p class="text-muted mb-0">Total Revenue</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-info"><?= $order_stats['total_orders'] > 0 ? number_format($order_stats['total_revenue'] / $order_stats['total_orders'], 2) : 0 ?></h4>
                                    <p class="text-muted mb-0">Avg Order Value</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attributes Tab -->
            <div class="tab-pane fade" id="tab-attributes" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Product Attributes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($product_data['attributes'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Attribute</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $attributes = explode(',', $product_data['attributes']);
                                    foreach ($attributes as $attribute) {
                                        $attribute = trim($attribute);
                                        if (!empty($attribute)) {
                                            $parts = explode(':', $attribute);
                                            $attr_name = trim($parts[0] ?? '');
                                            $attr_value = trim($parts[1] ?? '');
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($attr_name) ?></td>
                                        <td><?= htmlspecialchars($attr_value) ?></td>
                                    </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No attributes found for this product.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>