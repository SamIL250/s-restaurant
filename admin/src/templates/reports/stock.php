<h2><?= $title ?></h2>

<div class="section">
    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Category</th>
                <th>Current Stock</th>
                <th>Min Stock</th>
                <th>Unit Cost</th>
                <th>Total Value</th>
                <th>Status</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stockItems as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['item_name']) ?></td>
                <td><?= htmlspecialchars($item['category_name']) ?></td>
                <td><?= $item['current_stock'] ?> <?= $item['unit_of_measure'] ?></td>
                <td><?= $item['minimum_stock'] ?> <?= $item['unit_of_measure'] ?></td>
                <td>$<?= number_format($item['unit_cost'], 2) ?></td>
                <td>$<?= number_format($item['total_value'], 2) ?></td>
                <td><?= $item['status'] ?></td>
                <td><?= date('M d, Y', strtotime($item['last_updated'] ?? 'now')) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
