<?php
require_once 'config/config.php';

$product = array();
$product_id = $_GET['product'] ?? '';

if (empty($product_id)) {
    header('Location: products');
    exit;
}

// Sanitize the product_id to prevent SQL injection
$product_id = mysqli_real_escape_string($conn, $product_id);

$get_product_details = mysqli_query(
    $conn,
    "SELECT 
        p.*,  -- All product columns

        -- Category info
        c.name AS category_name,
        c.description AS category_description,
        c.slug AS category_slug,

        -- Brand info
        b.name AS brand_name,
        b.description AS brand_description,

        -- Aggregated attributes
        GROUP_CONCAT(DISTINCT CONCAT(at.name, ': ', av.value) SEPARATOR ', ') AS attributes,

        -- All images (not just primary)
        GROUP_CONCAT(DISTINCT pi.image_url ORDER BY pi.display_order SEPARATOR ', ') AS image_urls,
        
        -- Aggregated tags
        GROUP_CONCAT(DISTINCT pt.tag_name SEPARATOR ', ') AS tags

        FROM products p

        -- Join categories
        LEFT JOIN categories c ON p.category_id = c.category_id
        
        -- Join brands
        LEFT JOIN brands b ON p.brand_id = b.brand_id

        -- Join attributes
        LEFT JOIN product_attributes pa ON p.product_id = pa.product_id
        LEFT JOIN attribute_type at ON pa.attribute_type_id = at.attribute_type_id
        LEFT JOIN attribute_value av ON pa.attribute_value_id = av.attribute_value_id

        -- Join all images (no filter on is_primary)
        LEFT JOIN product_images pi ON p.product_id = pi.product_id

        -- Join tags
        LEFT JOIN product_tag_map ptm ON p.product_id = ptm.product_id
        LEFT JOIN product_tags pt ON ptm.tag_id = pt.tag_id

        WHERE p.product_id = '$product_id'  

        GROUP BY p.product_id
        ORDER BY p.created_at DESC"
);

if (!$get_product_details) {
    die("Error fetching product details: " . mysqli_error($conn));
}

$product_data = mysqli_fetch_assoc($get_product_details);

if (!$product_data) {
    header('Location: products');
    exit;
}

// Get order statistics for this product
$order_stats_query = mysqli_query(
    $conn, 
    "SELECT 
        COUNT(DISTINCT o.order_id) as total_orders,
        SUM(oi.quantity) as total_quantity_sold,
        SUM(oi.subtotal) as total_revenue
    FROM order_items oi
    LEFT JOIN orders o ON oi.order_id = o.order_id
    WHERE oi.product_id = '$product_id' AND o.status != 'cancelled'
");

$order_stats = mysqli_fetch_assoc($order_stats_query);

// Get recent orders for this product
$recent_orders_query = mysqli_query($conn, "
    SELECT 
        o.order_id,
        o.order_date,
        o.total_amount,
        o.status,
        oi.quantity,
        oi.unit_price,
        COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'Guest') as customer_name
    FROM order_items oi
    LEFT JOIN orders o ON oi.order_id = o.order_id
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE oi.product_id = '$product_id'
    ORDER BY o.order_date DESC
    LIMIT 5
");

$recent_orders = [];
while ($row = mysqli_fetch_assoc($recent_orders_query)) {
    $recent_orders[] = $row;
}
?>

<section class="py-0">
    <div class="container-small">
        <nav class="mb-3" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="products">Products</a></li> 
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product_data['name']); ?></li>
            </ol>
        </nav>
        <?php
            include 'utils/product.php';
        ?>
    </div> 
</section>

<?php
    include 'utils/more.php';
?>