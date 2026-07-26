<?php
$products = array();

$get_products = mysqli_query(
    $conn,
    "SELECT 
    p.product_id,
    p.name AS product_name,
    p.base_price,
    p.discount_price,
    p.created_at as created_at,
    c.name AS category_name,
    
    GROUP_CONCAT(DISTINCT CONCAT(at.name, ': ', av.value) SEPARATOR ', ') AS attributes,
    GROUP_CONCAT(DISTINCT pi.image_url ORDER BY pi.display_order SEPARATOR ', ') AS image_urls,
    GROUP_CONCAT(DISTINCT pt.tag_name SEPARATOR ', ') AS tags

    FROM products p

    -- Join for category
    LEFT JOIN categories c ON p.category_id = c.category_id

    -- Join for attributes
    LEFT JOIN product_attributes pa ON p.product_id = pa.product_id
    LEFT JOIN attribute_type at ON pa.attribute_type_id = at.attribute_type_id
    LEFT JOIN attribute_value av ON pa.attribute_value_id = av.attribute_value_id

    -- Join for images
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1

    -- Join for tags
    LEFT JOIN product_tag_map ptm ON p.product_id = ptm.product_id
    LEFT JOIN product_tags pt ON ptm.tag_id = pt.tag_id
    WHERE p.is_active = 1
    GROUP BY p.product_id
    ORDER BY p.created_at DESC
    "
);

// ---query for all fields

// SELECT 
//     p.*,  -- All product columns

//     -- Category info
//     c.name AS category_name,
//     c.description AS category_description,
//     c.slug AS category_slug,

//     -- Aggregated attributes
//     GROUP_CONCAT(DISTINCT CONCAT(at.name, ': ', av.value) SEPARATOR ', ') AS attributes,

//     -- All images (not just primary)
//     GROUP_CONCAT(DISTINCT pi.image_url ORDER BY pi.display_order SEPARATOR ', ') AS image_urls,

//     -- Aggregated tags
//     GROUP_CONCAT(DISTINCT pt.tag_name SEPARATOR ', ') AS tags

// FROM products p

// -- Join categories
// LEFT JOIN categories c ON p.category_id = c.category_id

// -- Join attributes
// LEFT JOIN product_attributes pa ON p.product_id = pa.product_id
// LEFT JOIN attribute_type at ON pa.attribute_type_id = at.attribute_type_id
// LEFT JOIN attribute_value av ON pa.attribute_value_id = av.attribute_value_id

// -- Join all images (no filter on is_primary)
// LEFT JOIN product_images pi ON p.product_id = pi.product_id

// -- Join tags
// LEFT JOIN product_tag_map ptm ON p.product_id = ptm.product_id
// LEFT JOIN product_tags pt ON ptm.tag_id = pt.tag_id

// GROUP BY p.product_id
// ORDER BY p.created_at DESC;


foreach ($get_products as $product) {
    $products[] = $product;
}
?>

<table class="table fs-9 mb-0">
    <thead>
        <tr>
            <th class="white-space-nowrap fs-9 align-middle ps-0" style="max-width:20px; width:18px;">
                <div class="form-check mb-0 fs-8"><input class="form-check-input" id="checkbox-bulk-products-select" type="checkbox" data-bulk-select='{"body":"products-table-body"}' /></div>
            </th>
            <th class="sort white-space-nowrap align-middle fs-10" scope="col" style="width:70px;"></th>
            <th class="sort white-space-nowrap align-middle ps-4" scope="col" style="width:350px;" data-sort="product">PRODUCT NAME</th>
            <th class="sort align-middle text-end ps-4" scope="col" data-sort="price" style="width:150px;">PRICE-FRW</th>
            <th class="sort align-middle ps-4" scope="col" data-sort="vendor" style="width:200px;">DISCOUNT-FRW</th>
            <th class="sort align-middle ps-4" scope="col" data-sort="category" style="width:150px;">CATEGORY</th>
            <th class="sort align-middle ps-3" scope="col" data-sort="tags" style="width:250px;">TAGS</th>
            <th class="sort align-middle ps-4" scope="col" data-sort="time" style="width:50px;">PUBLISHED ON</th>
            <th class="sort text-end align-middle pe-0 ps-4" scope="col"></th>
        </tr>
    </thead>
    <tbody class="list" id="products-table-body">
        <?php
        foreach ($products as $data) {
            // echo 

        ?>
            <tr class="position-static">
                <td class="fs-9 align-middle">
                    <div class="form-check mb-0 fs-8">
                        <input class="form-check-input" type="checkbox" data-bulk-select-row='{"product":"Fitbit Sense Advanced Smartwatch with Tools for Heart Health, Stress Management & Skin Temperature Trends, Carbon/Graphite, One Size (S & L Bands...","productImage":"/products/1.png","price":"$39","category":"Plants","tags":["Health","Exercise","Discipline","Lifestyle","Fitness"],"star":false,"vendor":"Blue Olive Plant sellers. Inc","publishedOn":"Nov 12, 10:45 PM"}' />
                    </div>
                </td>
                <td class="align-middle white-space-nowrap py-0">
                    <a class="d-block border border-translucent rounded-2" href="product?product=<?= $data['product_id'] ?>"><img src="<?= $data['image_urls'] ?>" alt="" width="53" /></a>
                </td>
                <td class="product align-middle ps-4">
                    <a class="fw-semibold line-clamp-3 mb-0" href="product?product=<?= $data['product_id'] ?>"><?= $data['product_name'] ?> ...</a>
                </td>
                <td class="price align-middle white-space-nowrap text-end fw-bold text-body-tertiary ps-4"><?= number_format($data['base_price'], 2) ?></td>
                <td class="vendor align-middle text-start fw-semibold ps-4"><?= number_format($data['discount_price'], 2) ?></td>
                <td class="category align-middle white-space-nowrap text-body-quaternary fs-9 ps-4 fw-semibold"><?= $data['category_name'] ?></td>
                <td class="tags align-middle review pb-2 ps-3" style="min-width:225px;"><a class="text-decoration-none"><span class="badge badge-tag me-2 mb-2"><?= $data['tags'] ?></span></a></td>
                <td class="time align-middle white-space-nowrap text-body-tertiary text-opacity-85 ps-4"><?= $data['created_at'] ?></td>
                <td class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                    <div class="btn-reveal-trigger position-static"><button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs-10" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent"><span class="fas fa-ellipsis-h fs-10"></span></button>
                        <div class="dropdown-menu dropdown-menu-end py-2">
                            <a class="dropdown-item" href="product?product=<?= $data['product_id'] ?>">View</a>
                            <a class="dropdown-item" href="products-edit?product=<?= $data['product_id'] ?>">Update</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="./src/services/products/product_status?product_id=<?= $data['product_id'] ?>">Remove</a>
                        </div>
                    </div>
                </td>
            </tr>
        <?php
        }
        ?>

    </tbody>
</table>

<?php
if (empty($products)) {
    echo "<div class='alert alert-info text-center p-2 rounded-2 mt-2 mb-2'>No products found</div>";
}
?>