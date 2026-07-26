<h2><?= $title ?></h2>

<div class="section">
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th>Loss Type</th>
                <th>Quantity Lost</th>
                <th>Value Lost</th>
                <th>Date</th>
                <th>Reason</th>
                <th>Reported By</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($losses as $loss): ?>
            <tr>
                <td><?= htmlspecialchars($loss['item_name']) ?></td>
                <td><?= htmlspecialchars($loss['category_name']) ?></td>
                <td><?= ucfirst($loss['loss_type']) ?></td>
                <td><?= $loss['quantity_lost'] ?> <?= $loss['unit_of_measure'] ?></td>
                <td>$<?= number_format($loss['value_lost'], 2) ?></td>
                <td><?= date('M d, Y', strtotime($loss['movement_date'])) ?></td>
                <td><?= htmlspecialchars($loss['reason']) ?></td>
                <td><?= htmlspecialchars($loss['reported_by'] ?? 'System') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
