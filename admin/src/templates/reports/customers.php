<h2><?= $title ?></h2>

<div class="section">
    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Email</th>
                <th>Subscription Type</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Days Remaining</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subscriptions as $sub): ?>
            <tr>
                <td><?= htmlspecialchars($sub['customer_name']) ?></td>
                <td><?= htmlspecialchars($sub['email']) ?></td>
                <td><?= htmlspecialchars($sub['subscription_type']) ?></td>
                <td><?= $sub['start_date'] ?></td>
                <td><?= $sub['end_date'] ?></td>
                <td><?= ucfirst($sub['status']) ?></td>
                <td><?= $sub['days_remaining'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
