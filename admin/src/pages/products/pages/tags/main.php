
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Tags</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Product Tags</h2>
        </div>
    </div>
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3">
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#">
                <span>All </span>
                <span class="text-body-tertiary fw-semibold">
                    (<?php echo mysqli_fetch_array(
                        mysqli_query($conn, "SELECT COUNT(tag_id) as tag_num FROM product_tags")
                    )['tag_num'] ?>)
                </span>
            </a>
        </li>
    </ul>
    <div id="tags" data-list='{"valueNames":["tagname"],"page":10,"pagination":true}'>
        <div class="mb-4">
            <div class="d-flex flex-wrap gap-3">
                <div class="search-box">
                    <form class="position-relative"><input class="form-control search-input search" type="search" placeholder="Search tags" aria-label="Search" />
                        <span class="fas fa-search search-box-icon"></span>
                    </form>
                </div>
                <div class="ms-xxl-auto">
                    <button class="btn btn-link text-body me-4 px-0"><span class="fa-solid fa-file-export fs-9 me-2"></span>Export</button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTagModal"><span class="fas fa-plus me-2"></span>Add tag</button>
                </div>
            </div>
        </div>
        <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis border-top border-bottom border-translucent position-relative top-1">
            <div class="table-responsive scrollbar mx-n1 px-1">
                <table class="table fs-9 mb-0">
                    <thead>
                        <tr>
                            <th class="white-space-nowrap fs-9 align-middle ps-0" style="max-width:20px; width:18px;">
                                <div class="form-check mb-0 fs-8"><input class="form-check-input" id="checkbox-bulk-tags-select" type="checkbox" data-bulk-select='{"body":"tags-table-body"}' /></div>
                            </th>
                            <th class="sort white-space-nowrap align-middle ps-4" scope="col" style="width:250px;" data-sort="tagname">TAG NAME</th>
                            <th class="sort text-end align-middle pe-0 ps-4" scope="col"></th>
                        </tr>
                    </thead>
                    <tbody class="list" id="tags-table-body">
                        <?php
                        $tag_query = mysqli_query($conn, "SELECT * FROM product_tags ORDER BY tag_id DESC");
                        $has_tags = false;
                        foreach ($tag_query as $tag) {
                            $has_tags = true;
                        ?>
                        <tr class="position-static" data-tag-id="<?= (int)$tag['tag_id'] ?>">
                            <td class="fs-9 align-middle">
                                <div class="form-check mb-0 fs-8">
                                    <input class="form-check-input" type="checkbox" data-bulk-select-row='{"tagname":"<?= htmlspecialchars($tag['tag_name']) ?>"}' />
                                </div>
                            </td>
                            <td class="tagname align-middle ps-4 fw-semibold">
                                <?= htmlspecialchars($tag['tag_name']) ?>
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
                        <?php if (!$has_tags): ?>
                        <tr><td colspan="3"><div class='alert alert-info text-center p-2 rounded-2 mt-2 mb-2'>No tags found</div></td></tr>
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

<!-- Add Tag Modal -->
<div class="modal fade" id="addTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="./src/services/products/add_tag.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tag Name</label>
                        <input type="text" class="form-control" name="tag_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Tag</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Tag Modal -->
<div class="modal fade" id="editTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editTagForm" action="./src/services/products/edit_tag.php" method="POST">
                <input type="hidden" name="tag_id" id="editTagId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tag Name</label>
                        <input type="text" class="form-control" name="tag_name" id="editTagName" required>
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
<form id="deleteTagForm" action="./src/services/products/delete_tag.php" method="POST" style="display:none;">
    <input type="hidden" name="tag_id" id="deleteTagId">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(function(item) {
        if(item.textContent.trim() === 'Edit') {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                var row = item.closest('tr');
                var tagId = row.getAttribute('data-tag-id');
                var name = row.querySelector('.tagname').textContent.trim();
                document.getElementById('editTagId').value = tagId;
                document.getElementById('editTagName').value = name;
                var modal = new bootstrap.Modal(document.getElementById('editTagModal'));
                modal.show();
            });
        }
        if(item.textContent.trim() === 'Delete') {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                var row = item.closest('tr');
                var tagId = row.getAttribute('data-tag-id');
                if (confirm('Are you sure you want to delete this tag? This action cannot be undone.')) {
                    document.getElementById('deleteTagId').value = tagId;
                    document.getElementById('deleteTagForm').submit();
                }
            });
        }
    });
});
</script>
