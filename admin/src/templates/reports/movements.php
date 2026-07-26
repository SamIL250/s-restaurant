<h2><?= $title ?></h2>

<div class="section">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Item</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Unit Cost</th>
                <th>Total Value</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movements as $movement): ?>
            <tr>
                <td><?= date('M d, Y', strtotime($movement['movement_date'])) ?></td>
                <td><?= htmlspecialchars($movement['item_name']) ?></td>
                <td><?= ucfirst($movement['movement_type']) ?></td>
                <td><?= $movement['quantity'] ?> <?= $movement['unit_of_measure'] ?></td>
                <td>$<?= number_format($movement['unit_cost'], 2) ?></td>
                <td>$<?= number_format($movement['total_cost'], 2) ?></td>
                <td><?= htmlspecialchars($movement['reason']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
