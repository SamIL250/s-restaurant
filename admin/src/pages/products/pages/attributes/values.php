<?php

// Get all attribute types for the dropdown
$types = mysqli_query($conn, "SELECT * FROM attribute_type ORDER BY name ASC");

// Get selected attribute type
$selected_type_id = isset($_GET['attribute_type_id']) ? intval($_GET['attribute_type_id']) : 0;
$selected_type = null;
if ($selected_type_id) {
    $type_query = mysqli_query($conn, "SELECT * FROM attribute_type WHERE attribute_type_id = $selected_type_id");
    $selected_type = mysqli_fetch_assoc($type_query);
}

// Get values for the selected type
$values = [];
if ($selected_type_id) {
    $values_query = mysqli_query($conn, "SELECT * FROM attribute_value WHERE attribute_type_id = $selected_type_id ORDER BY value ASC");
    while ($row = mysqli_fetch_assoc($values_query)) {
        $values[] = $row;
    }
}
?>

<div class="container mt-4">
    <nav class="mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="product-attributes">Attributes</a></li>
            <li class="breadcrumb-item active">Values</li>
        </ol>
    </nav>
    <h2>Attribute Values</h2>
    <form method="get" class="mb-4">
        <label for="attribute_type_id" class="form-label">Select Attribute Type:</label>
        <select name="attribute_type_id" id="attribute_type_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- Choose Attribute Type --</option>
            <?php while ($type = mysqli_fetch_assoc($types)): ?>
                <option value="<?= $type['attribute_type_id'] ?>" <?= $selected_type_id == $type['attribute_type_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($type['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>

    <?php if ($selected_type): ?>
        <div class="d-flex justify-content-end mb-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addValueModal">
                <i class="fas fa-plus me-1"></i> Add Value
            </button>
        </div>
        <div class="card mb-3">
            <div class="card-header">
                <strong><?= htmlspecialchars($selected_type['name']) ?></strong>
                <span class="text-muted ms-2"><?= htmlspecialchars($selected_type['description']) ?></span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="padding: 15px 20px;">Value</th>
                            <!-- Add actions column if you want edit/delete -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($values as $val): ?>
                            <tr>
                                <td style="padding: 20px; cursor:pointer; color:#c00;" class="delete-value-cell" data-value-id="<?= (int)$val['attribute_value_id'] ?>" title="Click to delete this value">
                                    <?= htmlspecialchars($val['value']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($values)): ?>
                            <tr><td class="text-center  text-muted">No values found for this attribute type.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Value Modal -->
        <div class="modal fade" id="addValueModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="./src/services/products/add_attribute_value.php" method="POST">
                        <input type="hidden" name="attribute_type_id" value="<?= $selected_type_id ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Value for <?= htmlspecialchars($selected_type['name']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Values <span class="text-muted">(separate by comma, semicolon, or new line)</span></label>
                                <textarea class="form-control" name="values" rows="3" required placeholder="e.g. 64GB, 128GB, 256GB"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Value</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php elseif ($selected_type_id): ?>
        <div class="alert alert-warning">Attribute type not found.</div>
    <?php endif; ?>

    <!-- Hidden form for deleting a value -->
    <form id="deleteValueForm" action="./src/services/products/delete_attribute_value.php" method="POST" style="display:none;">
        <input type="hidden" name="attribute_value_id" id="deleteValueId">
        <input type="hidden" name="attribute_id" value="<?=$selected_type_id?>" id="">
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-value-cell').forEach(function(cell) {
            cell.addEventListener('click', function() {
                var valueId = cell.getAttribute('data-value-id');
                var valueText = cell.textContent.trim();
                if (confirm('Are you sure you want to delete the value: "' + valueText + '"?')) {
                    document.getElementById('deleteValueId').value = valueId;
                    document.getElementById('deleteValueForm').submit();
                }
            });
        });
    });
    </script>
</div>
