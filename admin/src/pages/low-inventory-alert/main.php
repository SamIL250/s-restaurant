<?php
// Low Inventory Alert Page
// Uses the low_stock_items view
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Low Inventory Alert</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Low Inventory Alert</h2>
        </div>
    </div>
    <?php
    $count_query = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM low_stock_items");
    $low_count = mysqli_fetch_assoc($count_query)['cnt'];
    ?>
    <div class="alert alert-warning mb-4 fw-semibold">
        <span class="fa fa-exclamation-triangle me-2"></span>
        There are <span class="text-danger-emphasis"><?= $low_count ?></span> item(s) at or below minimum stock!
    </div>
    <div class="table-responsive bg-body-emphasis border-top border-bottom border-translucent position-relative top-1 p-3">
        <table class="table fs-9 mb-0">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th>Current Stock</th>
                    <th>Minimum Stock</th>
                    <th>Unit</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $alert_query = mysqli_query($conn, "SELECT * FROM low_stock_items ORDER BY current_stock ASC");
                $has_alerts = false;
                while ($row = mysqli_fetch_assoc($alert_query)) {
                    $has_alerts = true;
                    $status_badge = '';
                    if ($row['current_stock'] == 0) {
                        $status_badge = '<span class=\'badge bg-danger-subtle text-danger\'>Out of Stock</span>';
                    } else {
                        $status_badge = '<span class=\'badge bg-warning-subtle text-warning\'>Low Stock</span>';
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['item_name']) ?></td>
                    <td><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></td>
                    <td><?= htmlspecialchars($row['supplier_name'] ?? 'No Supplier') ?></td>
                    <td><?= number_format($row['current_stock'], 2) ?></td>
                    <td><?= number_format($row['minimum_stock'], 2) ?></td>
                    <td><?= htmlspecialchars($row['unit_of_measure']) ?></td>
                    <td><?= $status_badge ?></td>
                </tr>
                <?php }
                if (!$has_alerts): ?>
                <tr><td colspan="7"><div class='alert alert-success text-center p-2 rounded-2 mt-2 mb-2'>No low stock items found</div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
