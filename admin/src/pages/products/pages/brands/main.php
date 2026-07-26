
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Brands</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Product Brands</h2>
        </div>
    </div>
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3">
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#">
                <span>All </span>
                <span class="text-body-tertiary fw-semibold">
                    (<?php echo mysqli_fetch_array(
                        mysqli_query($conn, "SELECT COUNT(brand_id) as brand_num FROM brands")
                    )['brand_num'] ?>)
                </span>
            </a>
        </li>
    </ul>
    <div id="brands" data-list='{"valueNames":["brand","description","logo"],"page":10,"pagination":true}'>
        <div class="mb-4">
            <div class="d-flex flex-wrap gap-3">
                <div class="search-box">
                    <form class="position-relative"><input class="form-control search-input search" type="search" placeholder="Search brands" aria-label="Search" />
                        <span class="fas fa-search search-box-icon"></span>
                    </form>
                </div>
                <div class="ms-xxl-auto">
                    <button class="btn btn-link text-body me-4 px-0"><span class="fa-solid fa-file-export fs-9 me-2"></span>Export</button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal"><span class="fas fa-plus me-2"></span>Add brand</button>
                </div>
            </div>
        </div>
        <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis border-top border-bottom border-translucent position-relative top-1">
            <div class="table-responsive scrollbar mx-n1 px-1">
                <table class="table fs-9 mb-0">
                    <thead>
                        <tr>
                            <th class="white-space-nowrap fs-9 align-middle ps-0" style="max-width:20px; width:18px;">
                                <div class="form-check mb-0 fs-8"><input class="form-check-input" id="checkbox-bulk-brands-select" type="checkbox" data-bulk-select='{"body":"brands-table-body"}' /></div>
                            </th>
                            <th class="sort white-space-nowrap align-middle ps-4" scope="col" style="width:250px;" data-sort="brand">BRAND NAME</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="description" style="width:350px;">DESCRIPTION</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="logo" style="width:150px;">LOGO</th>
                            <th class="sort text-end align-middle pe-0 ps-4" scope="col"></th>
                        </tr>
                    </thead>
                    <tbody class="list" id="brands-table-body">
                        <?php
                        $brand_query = mysqli_query($conn, "SELECT * FROM brands ORDER BY brand_id DESC");
                        $has_brands = false;
                        foreach ($brand_query as $brand) {
                            $has_brands = true;
                        ?>
                        <tr class="position-static" data-brand-id="<?= (int)$brand['brand_id'] ?>">
                            <td class="fs-9 align-middle">
                                <div class="form-check mb-0 fs-8">
                                    <input class="form-check-input" type="checkbox" data-bulk-select-row='{"brand":"<?= htmlspecialchars($brand['name']) ?>"}' />
                                </div>
                            </td>
                            <td class="brand align-middle ps-4 fw-semibold">
                                <?= htmlspecialchars($brand['name']) ?>
                            </td>
                            <td class="description align-middle ps-4 text-muted">
                                <?= htmlspecialchars($brand['description']) ?>
                            </td>
                            <td class="logo align-middle ps-4">
                                <?php if (!empty($brand['logo_url'])): ?>
                                    <img src="<?= htmlspecialchars($brand['logo_url']) ?>" alt="Logo" style="max-height:32px; max-width:80px;">
                                <?php else: ?>
                                    <span class="text-muted">No logo</span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle white-space-nowrap text-end pe-0 ps-4 btn-reveal-trigger">
                                <div class="btn-reveal-trigger position-static"><button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs-10" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent"><span class="fas fa-ellipsis-h fs-10"></span></button>
                                    <div class="dropdown-menu dropdown-menu-end py-2">
                                        <a class="dropdown-item" href="#">Edit</a>
                                        <a class="dropdown-item text-danger" href="#">Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php if (!$has_brands): ?>
                        <tr><td colspan="5"><div class='alert alert-info text-center p-2 rounded-2 mt-2 mb-2'>No brands found</div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="row align-items-center justify-content-between py-2 pe-0 fs-9">
                <div class="col-auto d-flex">
                    <p class="mb-0 d-none d-sm-block me-3 fw-semibold text-body" data-list-info="data-list-info"></p>
                </div>
                <div class="col-auto d-flex"><button class="page-link" data-list-pagination="prev"><span class="fas fa-chevron-left"></span></button>
                    <ul class="mb-0 pagination"></ul><button class="page-link pe-0" data-list-pagination="next"><span class="fas fa-chevron-right"></span></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="./src/services/products/add_brand.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Brand Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo URL</label>
                        <input type="text" class="form-control" name="logo_url">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editBrandForm" action="./src/services/products/edit_brand.php" method="POST">
                <input type="hidden" name="brand_id" id="editBrandId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Brand Name</label>
                        <input type="text" class="form-control" name="name" id="editBrandName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="editBrandDescription" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo URL</label>
                        <input type="text" class="form-control" name="logo_url" id="editBrandLogo">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add a single hidden form for delete -->
<form id="deleteBrandForm" action="./src/services/products/delete_brand.php" method="POST" style="display:none;">
    <input type="hidden" name="brand_id" id="deleteBrandId">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(function(item) {
        if(item.textContent.trim() === 'Edit') {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                var row = item.closest('tr');
                var brandId = row.getAttribute('data-brand-id');
                var name = row.querySelector('.brand').textContent.trim();
                var description = row.querySelector('.description').textContent.trim();
                var logo = row.querySelector('.logo img') ? row.querySelector('.logo img').getAttribute('src') : '';
                document.getElementById('editBrandId').value = brandId;
                document.getElementById('editBrandName').value = name;
                document.getElementById('editBrandDescription').value = description;
                document.getElementById('editBrandLogo').value = logo;
                var modal = new bootstrap.Modal(document.getElementById('editBrandModal'));
                modal.show();
            });
        }
        if(item.textContent.trim() === 'Delete') {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                var row = item.closest('tr');
                var brandId = row.getAttribute('data-brand-id');
                if (confirm('Are you sure you want to delete this brand? This action cannot be undone.')) {
                    document.getElementById('deleteBrandId').value = brandId;
                    document.getElementById('deleteBrandForm').submit();
                }
            });
        }
    });
});
</script>
