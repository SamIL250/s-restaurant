<?php
$categories = array();
$tags = array();
$brands = array();
$attribute_types = array();
$attribute_values = array();

$get_categories = mysqli_query(
    $conn,
    "SELECT * FROM categories"
);

$get_tags = mysqli_query(
    $conn,
    "SELECT * FROM product_tags"
);

$get_brands = mysqli_query(
    $conn,
    "SELECT * FROM brands"
);

$get_attribute_types = mysqli_query(
    $conn,
    "SELECT * FROM attribute_type"
);

// $get_attribute_values = mysqli_query(
//     $conn,
//     "SELECT * FROM attribute_value 
//     INNER JOIN attribute_type ON attribute_value.attribute_type_id = attribute_type.attribute_type_id"
// );

$get_attribute_values = mysqli_query(
    $conn,
    "SELECT * FROM attribute_value"
);

foreach ($get_attribute_types as $attribute_type) {
    $attribute_types[] = $attribute_type;
}

foreach ($get_attribute_values as $attribute_value) {
    $attribute_values[] = $attribute_value;
}

foreach ($get_brands as $brand) {
    $brands[] = $brand;
}

foreach ($get_tags as $tag) {
    $tags[] = $tag;
}

foreach ($get_categories as $category) {
    $categories[] = $category;
}
?>


<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="#!">Page 1</a></li>
        <li class="breadcrumb-item"><a href="#!">Page 2</a></li>
        <li class="breadcrumb-item active">Default</li>
    </ol>
</nav>
<form action="./src/services/products/new_product.php" method="POST" class="mb-9">
    <div class="row g-3 flex-between-end mb-5">
        <div class="col-auto">
            <h2 class="mb-2">Add a product</h2>
            <h5 class="text-body-tertiary fw-semibold">Orders placed across your store</h5>
        </div>
        <div class="col-auto">
            <input type="reset" class="btn btn-phoenix-secondary me-2 mb-2 mb-sm-0" type="button" value="Discard" />
            <input type="submit" name="draft" class="btn btn-phoenix-primary me-2 mb-2 mb-sm-0" type="button" value="Save draft" />
            <input type="submit" name="publish" class="btn btn-primary mb-2 mb-sm-0" type="submit" />
        </div>
    </div>
    <div class="row g-5">
        <div class="col-12 col-xl-8">
            <h4 class="mb-3">Product Title</h4>
            <input class="form-control mb-5" type="text" placeholder="Write product name here..." name="product_name" required />
            <div class="mb-6">
                <h4 class="mb-3"> Product Description</h4>
                <textarea class="tinymce form-control" name="product_description" placeholder="Write a description here..." required></textarea>
            </div>
            <div class="mb-6">
                <h4 class="mb-3"> Product SKU</h4>
                <input class="form-control mb-5" type="text" placeholder="Write SKU here..." name="product_sku" required />
            </div>
            <div class="mb-6">
                <h4 class="mb-3"> Product Stock Quantity</h4>
                <input class="form-control mb-5" type="number" min="0" placeholder="Write stock quantity here..." name="stock_quantity" required />
            </div>

            <h4 class="mb-3">Inventory</h4>
            <?php
            include 'inventory.php';
            ?>
        </div>
        <?php
        include 'right-side.php';
        ?>
    </div>
</form>