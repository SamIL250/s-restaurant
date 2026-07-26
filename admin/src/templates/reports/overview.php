<h2>📊 <?= $title ?></h2>

<?php if (isset($totalItems)): ?>
<div class="metrics-grid">
    <div class="metric-cell info">
        <div class="metric-value"><?= $totalItems ?></div>
        <div class="metric-label">📂 Total Items</div>
    </div>
    <div class="metric-cell warning">
        <div class="metric-value"><?= $lowStockItems ?></div>
        <div class="metric-label">⚠️ Low Stock Items</div>
    </div>
    <div class="metric-cell success">
        <div class="metric-value">$<?= number_format($totalValue, 2) ?></div>
        <div class="metric-label">💰 Total Stock Value</div>
    </div>
    <div class="metric-cell danger">
        <div class="metric-value"><?= $expiringItems ?></div>
        <div class="metric-label">⏰ Expiring Soon</div>
    </div>
</div>
<?php else: ?>
<div class="no-data">
    <h3>📂 No Overview Data Available</h3>
    <p>There is no overview data available for the selected period.</p>
    <p>Please ensure:</p>
    <ul style="text-align: left; display: inline-block;">
        <li>Inventory items have been properly set up</li>
        <li>Stock movements have been recorded</li>
        <li>Date range includes relevant data</li>
    </ul>
</div>
<?php endif; ?>

<?php if (isset($recentMovements) && is_array($recentMovements) && !empty($recentMovements)): ?>
<div class="section">
    <h2>📋 Recent Stock Movements</h2>
    <table>
        <thead>
            <tr>
                <th>📂 Item</th>
                <th>🔄 Type</th>
                <th>📊 Quantity</th>
                <th>💰 Value</th>
                <th>📅 Date</th>
                <th>📝 Reason</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentMovements as $index => $movement): ?>
            <?php $rowClass = ($index % 2 == 0) ? 'even-row' : 'odd-row'; ?>
            <tr class="<?= $rowClass ?>">
                <td><strong><?= htmlspecialchars($movement['item_name']) ?></strong></td>
                <td><span class="movement-type <?= $movement['movement_type'] ?>"><?= ucfirst($movement['movement_type']) ?></span></td>
                <td><?= number_format($movement['quantity'], 2) ?></td>
                <td>$<?= number_format($movement['total_cost'], 2) ?></td>
                <td><?= date('M d, Y', strtotime($movement['movement_date'])) ?></td>
                <td><?= htmlspecialchars($movement['reason']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php elseif (isset($recentMovements)): ?>
<div class="no-data">
    <h3>📋 No Recent Movements</h3>
    <p>Recent movements data is empty or not available.</p>
</div>
<?php endif; ?>

<style>
.movement-type.in {
    background: #27ae60;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
}

.movement-type.out {
    background: #e74c3c;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
}

.movement-type.adjustment {
    background: #f39c12;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
}

.movement-type.waste {
    background: #9b59b6;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
}

.even-row {
    background-color: #ffffff;
}

.odd-row {
    background-color: #f8f9fa;
}
</style>
