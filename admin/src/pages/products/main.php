    <nav class="mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
            <li class="breadcrumb-item active">Products</li>
        </ol>
    </nav>
    <div class="mb-9">
        <div class="row g-3 mb-4">
            <div class="col-auto">
                <h2 class="mb-0">Products</h2>
            </div>
        </div>
        <ul class="nav nav-links mb-3 mb-lg-2 mx-n3">
            <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="#">
                    <span>All </span>
                    <span class="text-body-tertiary fw-semibold">
                        (<?php echo mysqli_fetch_array(
                        mysqli_query($conn, "SELECT COUNT(product_id) as product_num FROM products")
                        )['product_num'] ?>)
                    </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <span>Published </span>
                    <span class="text-body-tertiary fw-semibold">
                        (<?php echo mysqli_fetch_array(
                        mysqli_query($conn, "SELECT COUNT(product_id) as product_num FROM products WHERE published = 'true'")
                        )['product_num'] ?>) 
                    </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <span>Drafts </span>
                    <span class="text-body-tertiary fw-semibold">
                        (<?php echo mysqli_fetch_array(
                        mysqli_query($conn, "SELECT COUNT(product_id) as product_num FROM products WHERE published = 'false'")
                        )['product_num'] ?>) 
                    </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <span>On discount </span>
                    <span class="text-body-tertiary fw-semibold">
                        (<?php echo mysqli_fetch_array(
                        mysqli_query($conn, "SELECT COUNT(product_id) as product_num FROM products WHERE discount_price  > 0")
                        )['product_num'] ?>) 
                    </span>
                </a>
            </li>
        </ul>
        <div id="products" data-list='{"valueNames":["product","price","category","tags","vendor","time"],"page":10,"pagination":true}'>
            <div class="mb-4">
                <div class="d-flex flex-wrap gap-3">
                    <div class="search-box">
                        <form class="position-relative"><input class="form-control search-input search" type="search" placeholder="Search products" aria-label="Search" />
                            <span class="fas fa-search search-box-icon"></span>
                        </form>
                    </div>
                    <div class="scrollbar overflow-hidden-y">
                        <div class="btn-group position-static" role="group">
                        </div>
                    </div>
                    <div class="ms-xxl-auto"><button class="btn btn-link text-body me-4 px-0"><span class="fa-solid fa-file-export fs-9 me-2"></span>Export</button><button onclick="window.location.replace('products-add')" class="btn btn-primary" id="addBtn"><span class="fas fa-plus me-2"></span>Add product</button></div>
                </div>
            </div>
            <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis border-top border-bottom border-translucent position-relative top-1">
                <div class="table-responsive scrollbar mx-n1 px-1">
                    <?php
                    include 'utils/table.php'
                    ?>
                </div>
                <div class="row align-items-center justify-content-between py-2 pe-0 fs-9">
                    <div class="col-auto d-flex">
                        <p class="mb-0 d-none d-sm-block me-3 fw-semibold text-body" data-list-info="data-list-info"></p><a class="fw-semibold" href="#!" data-list-view="*">View all<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a><a class="fw-semibold d-none" href="#!" data-list-view="less">View Less<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                    </div>
                    <div class="col-auto d-flex"><button class="page-link" data-list-pagination="prev"><span class="fas fa-chevron-left"></span></button>
                        <ul class="mb-0 pagination"></ul><button class="page-link pe-0" data-list-pagination="next"><span class="fas fa-chevron-right"></span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>