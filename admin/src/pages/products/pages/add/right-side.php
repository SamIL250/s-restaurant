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
                                    <h5 class="mb-0 text-body-highlight me-2">Category</h5><a class="fw-bold fs-9" href="#!">View all categories</a>
                                </div>
                                <select class="form-select mb-3" aria-label="category" required name="product_category">
                                    <?php
                                    foreach ($categories as $cat) {
                                    ?>
                                        <option value="" selected hidden>Select product category</option>
                                        <option value="<?= $cat['category_id'] ?>"><?= $cat['name'] ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-xl-12">
                            <div class="d-flex flex-wrap mb-2">
                                <h5 class="mb-0 text-body-highlight me-2">Tags</h5><a class="fw-bold fs-9 lh-sm" href="#!">View all tags</a>
                            </div>
                            <select class="form-select" aria-label="category" required name="product_tag">
                                <?php
                                foreach ($tags as $tag) {
                                ?>
                                    <option value="" selected hidden>Select product tag</option>
                                    <option value="<?= $tag['tag_id'] ?>"><?= $tag['tag_name'] ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-xl-12 my-4">
                            <div class="d-flex flex-wrap mb-2">
                                <h5 class="mb-0 text-body-highlight me-2">Brand</h5><a class="fw-bold fs-9 lh-sm" href="#!">View all brands</a>
                            </div>
                            <select class="form-select" aria-label="category" required name="product_brand">
                                <?php
                                foreach ($brands as $brand) {
                                ?>
                                    <option value="" selected hidden>Select product brand</option>
                                    <option value="<?= $brand['brand_id'] ?>"><?= $brand['name'] ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-12">
                            <div class="d-flex flex-wrap mb-2">
                                <h5 class="mb-0 text-body-highlight me-2">Is Featured</h5>
                            </div>
                            <select class="form-select" aria-label="category" required name="is_featured"> 
                                <option value="1">Is featured</option>
                                <option value="0">Not featured</option> 
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            <!-- <div class="col-12 col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Variants</h4>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6 col-xl-12">
                                <div class="border-bottom border-translucent border-dashed border-sm-0 border-bottom-xl pb-4">
                                    <div class="d-flex flex-wrap mb-2">
                                        <h5 class="text-body-highlight me-2">Option 1</h5><a class="fw-bold fs-9" href="#!">Remove</a>
                                    </div><select class="form-select mb-3">
                                        <option value="size">Size</option>
                                        <option value="color">Color</option>
                                        <option value="weight">Weight</option>
                                        <option value="smell">Smell</option>
                                    </select>
                                    <div class="product-variant-select-menu"><select class="form-select mb-3" data-choices="data-choices" multiple="multiple" data-options='{"removeItemButton":true,"placeholder":true}'>
                                            <option value="size">4x6 in</option>
                                            <option value="color">9x6 in</option>
                                            <option value="weight">11x8 in</option>
                                        </select></div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-xl-12">
                                <div class="d-flex flex-wrap mb-2">
                                    <h5 class="text-body-highlight me-2">Option 2</h5><a class="fw-bold fs-9" href="#!">Remove</a>
                                </div><select class="form-select mb-3">
                                    <option value="size">Size</option>
                                    <option value="color">Color</option>
                                    <option value="weight">Weight</option>
                                    <option value="smell">Smell</option>
                                </select>
                                <div class="product-variant-select-menu mb-3"><select class="form-select mb-3" data-choices="data-choices" multiple="multiple" data-options='{"removeItemButton":true,"placeholder":true}'>
                                        <option value="size">4x6 in</option>
                                        <option value="color">9x6 in</option>
                                        <option value="weight">11x8 in</option>
                                    </select></div>
                            </div>
                        </div><button class="btn btn-phoenix-primary w-100" type="button">Add another option</button>
                    </div>
                </div>
            </div> -->
    </div>
</div>