
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="customers">Customers</a></li>
        <li class="breadcrumb-item active">Reviews</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Customer Reviews</h2>
        </div>
    </div>
    <?php
    // Get all users who have reviews
    $users = mysqli_query($conn, "SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.email FROM users u JOIN product_reviews r ON u.user_id = r.user_id ORDER BY u.first_name, u.last_name");
    $has_reviews = false;
    while ($user = mysqli_fetch_assoc($users)) {
        $user_id = $user['user_id'];
        // Get all reviews for this user
        $reviews = mysqli_query($conn, "SELECT r.*, p.name AS product_name, pi.image_url FROM product_reviews r JOIN products p ON r.product_id = p.product_id LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1 WHERE r.user_id = $user_id ORDER BY r.created_at DESC");
        if (mysqli_num_rows($reviews) === 0) continue;
        $has_reviews = true;
    ?>
    <div class="card mb-5">
        <div class="card-header bg-light d-flex flex-between-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h5>
                <div class="text-muted small">Email: <?= htmlspecialchars($user['email']) ?></div>
            </div>
            <div>
                <span class="badge bg-primary">Reviews: <?= mysqli_num_rows($reviews) ?></span>
            </div>
        </div>
        <div class="card-body">
            <?php while ($review = mysqli_fetch_assoc($reviews)) { ?>
            <div class="card mb-4 border border-primary-subtle">
                <div class="card-body d-flex align-items-center gap-3">
                    <a href="../products/product?product=<?= htmlspecialchars($review['product_id']) ?>">
                        <img src="<?= htmlspecialchars($review['image_url'] ?? 'src/assets/img/generic/default-light.png') ?>" alt="<?= htmlspecialchars($review['product_name']) ?>" class="img-fluid rounded" style="max-height:60px;max-width:60px;object-fit:contain;">
                    </a>
                    <div class="flex-grow-1">
                        <h6 class="fw-semibold mb-1">
                            <a href="../products/product?product=<?= htmlspecialchars($review['product_id']) ?>" class="text-decoration-none">
                                <?= htmlspecialchars($review['product_name']) ?>
                            </a>
                        </h6>
                        <div class="mb-1">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="fa fa-star<?= $i <= $review['rating'] ? ' text-warning' : ' text-secondary' ?>"></span>
                            <?php endfor; ?>
                            <?php if ($review['is_verified_purchase']): ?>
                                <span class="badge bg-success ms-2">Verified</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted small mb-1">Reviewed: <?= date('M d, Y', strtotime($review['created_at'])) ?></div>
                        <div><?= nl2br(htmlspecialchars($review['comment'])) ?></div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php }
    if (!$has_reviews): ?>
    <div class="alert alert-info text-center p-2 rounded-2 mt-2 mb-2">No reviews found.</div>
    <?php endif; ?>
</div>
