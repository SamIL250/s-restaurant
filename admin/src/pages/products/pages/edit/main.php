<?php
$categories = array();
$tags = array();
$brands = array();
$attribute_types = array();
$attribute_values = array();

// Get the product ID from URL
$product_id = isset($_GET['product']) ? $_GET['product'] : null;

if (!$product_id) {
    echo "<div class='alert alert-danger'>No product specified</div>";
    exit;
}

// Fetch the product details
$get_product = mysqli_query(
    $conn,
    "SELECT 
    p.*,
    c.category_id,
    pt.tag_id,
    b.brand_id,
    GROUP_CONCAT(DISTINCT pi.image_url ORDER BY pi.display_order SEPARATOR '||') AS image_urls,
    GROUP_CONCAT(DISTINCT CONCAT(pa.attribute_type_id, ':', pa.attribute_value_id, ':', av.value) SEPARATOR '||') AS attributes
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN product_tag_map ptm ON p.product_id = ptm.product_id
    LEFT JOIN product_tags pt ON ptm.tag_id = pt.tag_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    LEFT JOIN product_images pi ON p.product_id = pi.product_id
    LEFT JOIN product_attributes pa ON p.product_id = pa.product_id
    LEFT JOIN attribute_value av ON pa.attribute_value_id = av.attribute_value_id
    WHERE p.product_id = '$product_id'
    GROUP BY p.product_id"
);

$product = mysqli_fetch_assoc($get_product);

if (!$product) {
    echo "<div class='alert alert-danger'>Product not found</div>";
    exit;
}

// Get all categories
$get_categories = mysqli_query($conn, "SELECT * FROM categories");
foreach ($get_categories as $category) {
    $categories[] = $category;
}

// Get all tags
$get_tags = mysqli_query($conn, "SELECT * FROM product_tags");
foreach ($get_tags as $tag) {
    $tags[] = $tag;
}

// Get all brands
$get_brands = mysqli_query($conn, "SELECT * FROM brands");
foreach ($get_brands as $brand) {
    $brands[] = $brand;
}

// Get all attribute types
$get_attribute_types = mysqli_query($conn, "SELECT * FROM attribute_type");
foreach ($get_attribute_types as $attribute_type) {
    $attribute_types[] = $attribute_type;
}

// Get all attribute values
$get_attribute_values = mysqli_query($conn, "SELECT * FROM attribute_value");
foreach ($get_attribute_values as $attribute_value) {
    $attribute_values[] = $attribute_value;
}

// Parse product images
$product_images = $product['image_urls'] ? explode('||', $product['image_urls']) : [];

// Parse product attributes
$product_attributes = [];
$attribute_values = [];
if ($product['attributes']) {
    foreach (explode('||', $product['attributes']) as $attr) {
        list($type_id, $value_id, $value) = explode(':', $attr);
        $product_attributes[$type_id] = $value_id;
        $attribute_values[$type_id] = $value;
    }
}
?>

<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="products">Products</a></li>
        <li class="breadcrumb-item active">Edit Product</li>
    </ol>
</nav>

<form action="./src/services/products/update_product.php" method="POST" class="mb-9">
    <input type="hidden" name="product_id" value="<?= $product_id ?>" />

    <div class="row g-3 flex-between-end mb-5">
        <div class="col-auto">
            <h2 class="mb-2">Edit product</h2>
            <h5 class="text-body-tertiary fw-semibold">Update product details</h5>
        </div>
        <div class="col-auto">
            <a href="products" class="btn btn-phoenix-secondary me-2 mb-2 mb-sm-0">Cancel</a>
            <input type="submit" name="update" class="btn btn-primary mb-2 mb-sm-0" value="Update Product" />
        </div>
    </div>

    <div class="row g-5">
        <div class="col-12 col-xl-8">
            <h4 class="mb-3">Product Title</h4>
            <input class="form-control mb-5" type="text" placeholder="Write product name here..." name="product_name" required value="<?= htmlspecialchars($product['name']) ?>" />

            <div class="mb-6">
                <h4 class="mb-3">Product Description</h4>
                <textarea class="tinymce form-control" name="product_description" placeholder="Write a description here..." required><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="mb-6">
                <h4 class="mb-3">Product SKU</h4>
                <input class="form-control mb-5" type="text" placeholder="Write SKU here..." name="product_sku" required value="<?= htmlspecialchars($product['sku']) ?>" />
            </div>

            <div class="mb-6">
                <h4 class="mb-3">Product Stock Quantity</h4>
                <input class="form-control mb-5" type="number" min="0" placeholder="Write stock quantity here..." name="stock_quantity" required value="<?= htmlspecialchars($product['stock_quantity']) ?>" />
            </div>

            <h4 class="mb-3">Inventory</h4>
            <div class="row g-0 border-top border-bottom">
                <div class="col-sm-4">
                    <div class="nav flex-sm-column border-bottom border-bottom-sm-0 border-end-sm fs-9 vertical-tab h-100 justify-content-between" role="tablist" aria-orientation="vertical">
                        <a class="nav-link border-end border-end-sm-0 border-bottom-sm text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center active" id="pricingTab" data-bs-toggle="tab" data-bs-target="#pricingTabContent" role="tab" aria-controls="pricingTabContent" aria-selected="true">
                            <span class="me-sm-2 fs-4 nav-icons" data-feather="tag"></span>
                            <span class="d-none d-sm-inline">Pricing</span>
                        </a>
                        <a class="nav-link border-end border-end-sm-0 border-bottom-sm text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center" id="attributesTab" data-bs-toggle="tab" data-bs-target="#attributesTabContent" role="tab" aria-controls="attributesTabContent" aria-selected="false">
                            <span class="me-sm-2 fs-4 nav-icons" data-feather="sliders"></span>
                            <span class="d-none d-sm-inline">Attributes</span>
                        </a>
                        <a class="nav-link text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center" id="advancedTab" data-bs-toggle="tab" data-bs-target="#advancedTabContent" role="tab" aria-controls="advancedTabContent" aria-selected="false">
                            <span class="me-sm-2 fs-4 nav-icons" data-feather="lock"></span>
                            <span class="d-none d-sm-inline">Product Images</span>
                        </a>
                    </div>
                </div>

                <div class="col-sm-8">
                    <div class="tab-content py-3 ps-sm-4 h-100">
                        <div class="tab-pane fade show active" id="pricingTabContent" role="tabpanel">
                            <h4 class="mb-3 d-sm-none">Pricing</h4>
                            <div class="row g-3">
                                <div class="col-12 col-lg-6">
                                    <h5 class="mb-2 text-body-highlight">Base price</h5>
                                    <input class="form-control" type="number" step="0.01" min="0" name="base_price" required value="<?= htmlspecialchars($product['base_price']) ?>" />
                                </div>
                                <div class="col-12 col-lg-6">
                                    <h5 class="mb-2 text-body-highlight">Discount price</h5>
                                    <input class="form-control" type="number" step="0.01" min="0" name="discount_price" required value="<?= htmlspecialchars($product['discount_price']) ?>" />
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="attributesTabContent" role="tabpanel" aria-labelledby="attributesTab">
                            <h5 class="mb-3 text-body-highlight">Attributes</h5>
                            <?php foreach ($attribute_types as $attribute_type):
                                $attribute_id = $attribute_type['attribute_type_id'];
                                $attribute_name_safe = strtolower(preg_replace('/\s+/', '-', $attribute_type['name']));
                                $is_selected = isset($product_attributes[$attribute_id]);
                            ?>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input"
                                            id="attribute-checkbox-<?= $attribute_name_safe ?>"
                                            type="checkbox"
                                            name="selected_attributes[]"
                                            value="<?= $attribute_id ?>"
                                            <?= $is_selected ? 'checked' : '' ?> />

                                        <label class="form-check-label text-body fs-8" for="attribute-checkbox-<?= $attribute_name_safe ?>">
                                            <?= htmlspecialchars($attribute_type['name']) ?>
                                        </label>

                                        <div class="product-variant-select-menu mt-2">
                                            <select class="form-select mb-3"
                                                name="attribute_values[<?= $attribute_id ?>]"
                                                data-choices
                                                data-options='{"placeholder":true}'>
                                                <?php
                                                $get_attribute_by_values = mysqli_query(
                                                    $conn,
                                                    "SELECT * FROM attribute_value WHERE attribute_type_id = '{$attribute_id}'"
                                                );

                                                if (isset($product_attributes[$attribute_id])) {
                                                    // Show the selected value first
                                                    echo '<option value="' . $product_attributes[$attribute_id] . '" selected>' .
                                                        htmlspecialchars($attribute_values[$attribute_id]) . '</option>';
                                                }

                                                // Show other values
                                                foreach ($get_attribute_by_values as $value):
                                                    if (
                                                        !isset($product_attributes[$attribute_id]) ||
                                                        $product_attributes[$attribute_id] != $value['attribute_value_id']
                                                    ):
                                                ?>
                                                        <option value="<?= $value['attribute_value_id'] ?>">
                                                            <?= htmlspecialchars($value['value']) ?>
                                                        </option>
                                                <?php
                                                    endif;
                                                endforeach;
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="tab-pane fade" id="advancedTabContent" role="tabpanel" aria-labelledby="advancedTab">
                            <h5 class="mb-3 text-body-highlight">Product images (min 3, max 5)</h5>
                            <div class="row g-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="col-12 col-lg-6">
                                        <h5 class="mb-2 text-body-highlight">Product Image <?= $i ?></h5>
                                        <input class="form-control"
                                            name="product_image_<?= $i ?>"
                                            type="url"
                                            placeholder="https://product-image-url.com"
                                            value="<?= isset($product_images[$i - 1]) ? htmlspecialchars($product_images[$i - 1]) : '' ?>"
                                            <?= ($i <= 3) ? 'required' : '' ?> />
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="row g-2">
                <div class="col-12 col-xl-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Organize</h4>
                            <div class="row gx-3">
                                <div class="col-12 col-sm-6 col-xl-12">
                                    <div class="mb-4">
                                        <div class="d-flex flex-wrap mb-2">
                                            <h5 class="mb-0 text-body-highlight me-2">Category</h5>
                                        </div>
                                        <select class="form-select mb-3" aria-label="category" required name="product_category">
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['category_id'] ?>" <?= ($cat['category_id'] == $product['category_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-12">
                                    <div class="d-flex flex-wrap mb-2">
                                        <h5 class="mb-0 text-body-highlight me-2">Tags</h5>
                                    </div>
                                    <select class="form-select" aria-label="category" required name="product_tag">
                                        <?php foreach ($tags as $tag): ?>
                                            <option value="<?= $tag['tag_id'] ?>" <?= ($tag['tag_id'] == $product['tag_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($tag['tag_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-12 my-4">
                                    <div class="d-flex flex-wrap mb-2">
                                        <h5 class="mb-0 text-body-highlight me-2">Brand</h5>
                                    </div>
                                    <select class="form-select" aria-label="category" required name="product_brand">
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?= $brand['brand_id'] ?>" <?= ($brand['brand_id'] == $product['brand_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($brand['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-12">
                                    <div class="d-flex flex-wrap mb-2">
                                        <h5 class="mb-0 text-body-highlight me-2">Is Featured</h5>
                                    </div>
                                    <select class="form-select" aria-label="category" required name="is_featured">
                                        <option value="1" <?= ($product['is_featured'] == 1) ? 'selected' : '' ?>>Is featured</option>
                                        <option value="0" <?= ($product['is_featured'] == 0) ? 'selected' : '' ?>>Not featured</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>