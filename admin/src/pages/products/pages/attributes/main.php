
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Attributes</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Product Attributes</h2>
        </div>
    </div>
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3">
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#">
                <span>All </span>
                <span class="text-body-tertiary fw-semibold">
                    (<?php echo mysqli_fetch_array(
                        mysqli_query($conn, "SELECT COUNT(attribute_type_id) as attr_num FROM attribute_type")
                    )['attr_num'] ?>)
                </span>
            </a>
        </li>
    </ul>
    <div id="attributes" data-list='{"valueNames":["attrname","description","created","updated"],"page":10,"pagination":true}'>
        <div class="mb-4">
            <div class="d-flex flex-wrap gap-3">
                <div class="search-box">
                    <form class="position-relative"><input class="form-control search-input search" type="search" placeholder="Search attributes" aria-label="Search" />
                        <span class="fas fa-search search-box-icon"></span>
                    </form>
                </div>
                <div class="ms-xxl-auto">
                    <button class="btn btn-link text-body me-4 px-0"><span class="fa-solid fa-file-export fs-9 me-2"></span>Export</button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAttributeModal"><span class="fas fa-plus me-2"></span>Add attribute</button>
                </div>
            </div>
        </div>
        <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis border-top border-bottom border-translucent position-relative top-1">
            <div class="table-responsive scrollbar mx-n1 px-1">
                <table class="table fs-9 mb-0">
                    <thead>
                        <tr>
                            <th class="white-space-nowrap fs-9 align-middle ps-0" style="max-width:20px; width:18px;">
                                <div class="form-check mb-0 fs-8"><input class="form-check-input" id="checkbox-bulk-attributes-select" type="checkbox" data-bulk-select='{"body":"attributes-table-body"}' /></div>
                            </th>
                            <th class="sort white-space-nowrap align-middle ps-4" scope="col" style="width:220px;" data-sort="attrname">ATTRIBUTE NAME</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="description" style="width:300px;">DESCRIPTION</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="created" style="width:120px;">CREATED</th>
                            <th class="sort align-middle ps-4" scope="col" data-sort="updated" style="width:120px;">UPDATED</th>
                            <th class="align-middle ps-4" scope="col" style="width:120px;">VALUES</th>
                            <th class="sort text-end align-middle pe-0 ps-4" scope="col"></th>
                        </tr>
                    </thead>
                    <tbody class="list" id="attributes-table-body">
                        <?php
                        $attr_query = mysqli_query($conn, "SELECT * FROM attribute_type ORDER BY attribute_type_id DESC");
                        $has_attrs = false;
                        foreach ($attr_query as $attr) {
                            $has_attrs = true;
                        ?>
                        <tr class="position-static" data-attribute-type-id="<?= (int)$attr['attribute_type_id'] ?>">
                            <td class="fs-9 align-middle">
                                <div class="form-check mb-0 fs-8">
                                    <input class="form-check-input" type="checkbox" data-bulk-select-row='{"attrname":"<?= htmlspecialchars($attr['name']) ?>"}' />
                                </div>
                            </td>
                            <td class="attrname align-middle ps-4 fw-semibold">
                                <?= htmlspecialchars($attr['name']) ?>
                            </td>
                            <td class="description align-middle ps-4 text-muted">
                                <?= htmlspecialchars($attr['description']) ?>
                            </td>
                            <td class="created align-middle ps-4 text-muted">
                                <?= htmlspecialchars($attr['created_at']) ?>
                            </td>
                            <td class="updated align-middle ps-4 text-muted">
                                <?= htmlspecialchars($attr['updated_at']) ?>
                            </td>
                            <td class="align-middle ps-4">
                                <a class="btn btn-sm btn-info" 
                                    href="product-attributes-values?attribute_type_id=<?= (int)$attr['attribute_type_id'] ?>"
                                    title="Manage Values for <?= htmlspecialchars($attr['name']) ?>">
                                    Values
                                </a>
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
                        <?php if (!$has_attrs): ?>
                        <tr><td colspan="7"><div class='alert alert-info text-center p-2 rounded-2 mt-2 mb-2'>No attributes found</div></td></tr>
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

<!-- Add Attribute Modal -->
<div class="modal fade" id="addAttributeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="./src/services/products/add_attribute.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Attribute</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Attribute Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Attribute</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Attribute Modal -->
<div class="modal fade" id="editAttributeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editAttributeForm" action="./src/services/products/edit_attribute.php" method="POST">
                <input type="hidden" name="attribute_type_id" id="editAttributeId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Attribute</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Attribute Name</label>
                        <input type="text" class="form-control" name="name" id="editAttributeName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="editAttributeDescription" rows="2"></textarea>
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

<!-- Attribute Values Modal -->
<div class="modal fade" id="attributeValuesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attribute Values for <span id="valuesAttributeName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="attributeValuesBody">
                <!-- Values table will be loaded here via JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add a single hidden form for delete -->
<form id="deleteAttributeForm" action="./src/services/products/delete_attribute.php" method="POST" style="display:none;">
    <input type="hidden" name="attribute_type_id" id="deleteAttributeId">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit/Delete attribute type
    document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(function(item) {
        if(item.textContent.trim() === 'Edit') {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                var row = item.closest('tr');
                var attrId = row.getAttribute('data-attribute-type-id');
                var name = row.querySelector('.attrname').textContent.trim();
                var description = row.querySelector('.description').textContent.trim();
                document.getElementById('editAttributeId').value = attrId;
                document.getElementById('editAttributeName').value = name;
                document.getElementById('editAttributeDescription').value = description;
                var modal = new bootstrap.Modal(document.getElementById('editAttributeModal'));
                modal.show();
            });
        }
        if(item.textContent.trim() === 'Delete') {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                var row = item.closest('tr');
                var attrId = row.getAttribute('data-attribute-type-id');
                if (confirm('Are you sure you want to delete this attribute type? This action cannot be undone.')) {
                    document.getElementById('deleteAttributeId').value = attrId;
                    document.getElementById('deleteAttributeForm').submit();
                }
            });
        }
    });
    // Manage Values
    document.querySelectorAll('.manage-values-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var attrTypeId = btn.getAttribute('data-attribute-type-id');
            var attrName = btn.getAttribute('data-attribute-name');
            document.getElementById('valuesAttributeName').textContent = attrName;
            fetch('./src/services/products/get_attribute_values.php?attribute_type_id=' + attrTypeId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('attributeValuesBody').innerHTML = html;
                    var modal = new bootstrap.Modal(document.getElementById('attributeValuesModal'));
                    modal.show();
                });
        });
    });
});
</script>
