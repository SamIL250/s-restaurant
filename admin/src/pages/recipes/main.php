<?php
// Recipes Management Page
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Recipes</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Recipes</h2>
        </div>
    </div>
    <div class="mb-4">
        <div class="d-flex flex-wrap gap-3">
            <div class="search-box">
                <form class="position-relative"><input class="form-control search-input search" type="search" placeholder="Search menu items" aria-label="Search" id="recipe-search-input" />
                    <span class="fas fa-search search-box-icon"></span>
                </form>
            </div>
        </div>
    </div>
    <div class="table-responsive bg-body-emphasis border-top border-bottom border-translucent position-relative top-1 p-3">
        <table class="table fs-9 mb-0">
            <thead>
                <tr>
                    <th>Menu Item</th>
                    <th>Ingredients</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="recipe-table-body">
                <?php
                $menu_query = mysqli_query($conn, "SELECT menu_item_id, item_name FROM menu_items ORDER BY item_name ASC");
                $has_recipes = false;
                while ($menu = mysqli_fetch_assoc($menu_query)) {
                    $has_recipes = true;
                    $ingredients_query = mysqli_query($conn, "SELECT ri.*, ii.item_name FROM recipe_ingredients ri LEFT JOIN inventory_items ii ON ri.item_id = ii.item_id WHERE ri.menu_item_id = " . (int)$menu['menu_item_id']);
                    $ingredients = [];
                    while ($ing = mysqli_fetch_assoc($ingredients_query)) {
                        $ingredients[] = $ing;
                    }
                ?>
                <tr data-menu-id="<?= (int)$menu['menu_item_id'] ?>">
                    <td class="fw-semibold align-middle" style="min-width:180px;">
                        <?= htmlspecialchars($menu['item_name']) ?>
                    </td>
                    <td class="align-middle">
                        <?php if (count($ingredients) > 0): ?>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($ingredients as $ing): ?>
                                    <li>
                                        <span class="fw-semibold"><?= htmlspecialchars($ing['item_name']) ?></span>
                                        <span class="text-body-tertiary">(<?= $ing['quantity_needed'] ?> <?= htmlspecialchars($ing['unit']) ?>)</span>
                                        <button class="btn btn-xs btn-link text-danger ms-2 p-0 align-baseline" title="Delete" onclick="deleteIngredient(<?= (int)$ing['recipe_id'] ?>)"><i class="fa fa-trash"></i></button>
                                        <button class="btn btn-xs btn-link text-primary ms-1 p-0 align-baseline" title="Edit" onclick="editIngredient(<?= (int)$ing['recipe_id'] ?>)"><i class="fa fa-edit"></i></button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <span class="text-body-tertiary">No ingredients</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end align-middle">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addRecipeModal" onclick="setAddRecipeMenuId(<?= (int)$menu['menu_item_id'] ?>)"><i class="fa fa-plus"></i> Add Ingredient</button>
                    </td>
                </tr>
                <?php }
                if (!$has_recipes): ?>
                <tr><td colspan="3"><div class='alert alert-warning text-center p-2 rounded-2 mt-2 mb-2'>No recipes found</div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Recipe Modal (hidden menu_item_id) -->
<div class="modal fade" id="addRecipeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="./src/services/recipes/add_ingredient.php" method="POST">
                <input type="hidden" name="menu_item_id" id="addRecipeMenuId">
                <div class="modal-header">
                    <h5 class="modal-title">Add Ingredient to Recipe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ingredient</label>
                        <select class="form-select" name="item_id" required>
                            <option value="">Select Ingredient</option>
                            <?php
                            $inv_query = mysqli_query($conn, "SELECT item_id, item_name, unit_of_measure FROM inventory_items WHERE is_active = 1 ORDER BY item_name");
                            while ($inv = mysqli_fetch_assoc($inv_query)) {
                                echo "<option value='{$inv['item_id']}'>{$inv['item_name']} ({$inv['unit_of_measure']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity Needed</label>
                        <input type="number" step="0.01" class="form-control" name="quantity_needed" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit</label>
                        <input type="text" class="form-control" name="unit" placeholder="e.g. grams, pieces" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Ingredient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden form for delete ingredient -->
<form id="deleteIngredientForm" action="./src/services/recipes/delete_ingredient.php" method="POST" style="display:none;">
    <input type="hidden" name="recipe_id" id="deleteRecipeId">
</form>

<!-- Edit Ingredient Modal -->
<div class="modal fade" id="editIngredientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editIngredientForm" action="./src/services/recipes/edit_ingredient.php" method="POST">
                <input type="hidden" name="recipe_id" id="editRecipeId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Ingredient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Quantity Needed</label>
                        <input type="number" step="0.01" class="form-control" name="quantity_needed" id="editQuantityNeeded" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit</label>
                        <input type="text" class="form-control" name="unit" id="editUnit" required>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple search filter for menu items
    document.getElementById('recipe-search-input').addEventListener('input', function() {
        var val = this.value.toLowerCase();
        document.querySelectorAll('#recipe-table-body tr').forEach(function(row) {
            var text = row.children[0].textContent.toLowerCase();
            row.style.display = text.indexOf(val) > -1 ? '' : 'none';
        });
    });
});
function setAddRecipeMenuId(menuId) {
    var select = document.getElementById('addRecipeMenuId');
    if (select) {
        select.value = menuId;
    }
}
function deleteIngredient(recipeId) {
    if (confirm('Are you sure you want to delete this ingredient from the recipe?')) {
        document.getElementById('deleteRecipeId').value = recipeId;
        document.getElementById('deleteIngredientForm').submit();
    }
}
function editIngredient(recipeId) {
    fetch('./src/services/recipes/get_ingredient.php?id=' + recipeId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('editRecipeId').value = data.recipe_id;
            document.getElementById('editQuantityNeeded').value = data.quantity_needed;
            document.getElementById('editUnit').value = data.unit;
            var modal = new bootstrap.Modal(document.getElementById('editIngredientModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading ingredient data');
        });
}
</script>
