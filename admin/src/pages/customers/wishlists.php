
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="customers">Customers</a></li>
        <li class="breadcrumb-item active">Wishlists</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Customer Wishlists</h2>
        </div>
    </div>
    <?php
    // Get all users who have wishlists
    $users = mysqli_query($conn, "SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.email FROM users u JOIN wishlists w ON u.user_id = w.user_id ORDER BY u.first_name, u.last_name");
    $has_wishlists = false;
    while ($user = mysqli_fetch_assoc($users)) {
        $user_id = $user['user_id'];
        // Get all wishlists for this user
        $wishlists = mysqli_query($conn, "SELECT * FROM wishlists WHERE user_id = $user_id ORDER BY wishlist_created_at DESC");
        if (mysqli_num_rows($wishlists) === 0) continue;
        $has_wishlists = true;
    ?>
    <div class="card mb-5">
        <div class="card-header bg-light d-flex flex-between-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h5>
                <div class="text-muted small">Email: <?= htmlspecialchars($user['email']) ?></div>
            </div>
            <div>
                <span class="badge bg-primary">Wishlists: <?= mysqli_num_rows($wishlists) ?></span>
            </div>
        </div>
        <div class="card-body">
            <?php while ($wishlist = mysqli_fetch_assoc($wishlists)) {
                $wishlist_id = $wishlist['wishlist_id'];
                $wishlist_items = mysqli_query($conn, "SELECT wi.*, p.name AS product_name, p.base_price, pi.image_url FROM wishlist_items wi JOIN products p ON wi.product_id = p.product_id LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1 WHERE wi.wishlist_id = '$wishlist_id'");
            ?>
            <div class="card mb-4 border border-primary-subtle">
                <div class="card-header d-flex flex-between-center flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0">Wishlist: <?= htmlspecialchars($wishlist['name']) ?></h6>
                        <div class="text-muted small">Created: <?= date('M d, Y H:i', strtotime($wishlist['wishlist_created_at'])) ?></div>
                    </div>
                    <div>
                        <span class="badge bg-info">Items: <?= mysqli_num_rows($wishlist_items) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($wishlist_items) > 0): ?>
                    <div class="row g-3">
                        <?php while ($item = mysqli_fetch_assoc($wishlist_items)) { ?>
                        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                            <div class="card h-100">
                                <div class="card-body d-flex flex-column align-items-center">
                                    <a href="../products/product?product=<?= htmlspecialchars($item['product_id']) ?>">
                                        <img src="<?= htmlspecialchars($item['image_url'] ?? 'src/assets/img/generic/default-light.png') ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="img-fluid mb-2" style="max-height:100px;max-width:100px;object-fit:contain;">
                                    </a>
                                    <h6 class="fw-semibold text-center mb-1">
                                        <a href="../products/product?product=<?= htmlspecialchars($item['product_id']) ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($item['product_name']) ?>
                                        </a>
                                    </h6>
                                    <div class="text-muted small mb-1">Added: <?= date('M d, Y', strtotime($item['added_at'])) ?></div>
                                    <div class="fw-bold text-primary mb-2">
                                        <?= number_format($item['base_price'], 2) ?> RWF
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info text-center p-2 rounded-2 mt-2 mb-2">No items in this wishlist.</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php }
    if (!$has_wishlists): ?>
    <div class="alert alert-info text-center p-2 rounded-2 mt-2 mb-2">No wishlists found.</div>
    <?php endif; ?>
</div>
