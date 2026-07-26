<?php
// Stock Movements Log Page
// Assumes $conn is available from layout include

// Count movements for each type
$all_count = 0;
$type_counts = [
    'in' => 0,
    'out' => 0,
    'adjustment' => 0,
    'waste' => 0
];
$movements_count_query = mysqli_query($conn, "
    SELECT movement_type, COUNT(*) as cnt
    FROM stock_movements
    GROUP BY movement_type
");
while ($row = mysqli_fetch_assoc($movements_count_query)) {
    $type = $row['movement_type'];
    $cnt = (int)$row['cnt'];
    $type_counts[$type] = $cnt;
    $all_count += $cnt;
}
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Inventory Movements</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Inventory Movements</h2>
        </div>
    </div>
    
    <!-- Search and Date Filters -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="search-box">
                <form class="position-relative">
                    <input class="form-control search-input" type="search" placeholder="Search movements..." aria-label="Search" id="movement-search-input" />
                    <span class="fas fa-search search-box-icon"></span>
                </form>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">From Date</label>
            <input type="date" class="form-control" id="from-date" />
        </div>
        <div class="col-md-3">
            <label class="form-label">To Date</label>
            <input type="date" class="form-control" id="to-date" />
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-outline-secondary" id="clear-filters" type="button">
                <i class="fa fa-times me-1"></i>Clear
            </button>
        </div>
    </div>
    
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3" id="movement-filters">
        <li class="nav-item">
            <a class="nav-link active" data-filter="all" aria-current="page" href="#">
                All <span class="text-body-tertiary fw-semibold">(<?= $all_count ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="in" href="#">
                Stock In <span class="text-body-tertiary fw-semibold">(<?= $type_counts['in'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="out" href="#">
                Stock Out <span class="text-body-tertiary fw-semibold">(<?= $type_counts['out'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="adjustment" href="#">
                Adjustment <span class="text-body-tertiary fw-semibold">(<?= $type_counts['adjustment'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="waste" href="#">
                Waste <span class="text-body-tertiary fw-semibold">(<?= $type_counts['waste'] ?>)</span>
            </a>
        </li>
    </ul>
    
    <div class="table-responsive bg-body-emphasis border-top border-bottom border-translucent position-relative top-1 p-3">
        <table class="table fs-9 mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Reason</th>
                    <th>User</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody id="movements-table-body">
                <?php
                $movements_query = mysqli_query($conn, "
                    SELECT m.*, i.item_name, u.first_name, u.last_name
                    FROM stock_movements m
                    LEFT JOIN inventory_items i ON m.item_id = i.item_id
                    LEFT JOIN users u ON m.user_id = u.user_id
                    ORDER BY m.movement_date DESC
                ");
                $has_movements = false;
                while ($row = mysqli_fetch_assoc($movements_query)) {
                    $has_movements = true;
                    $user_name = $row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : 'Unknown';
                    $type_badge = '';
                    switch ($row['movement_type']) {
                        case 'in': $type_badge = '<span class="badge bg-success-subtle text-success">In</span>'; break;
                        case 'out': $type_badge = '<span class="badge bg-danger-subtle text-danger">Out</span>'; break;
                        case 'adjustment': $type_badge = '<span class="badge bg-warning-subtle text-warning">Adjustment</span>'; break;
                        case 'waste': $type_badge = '<span class="badge bg-secondary-subtle text-secondary">Waste</span>'; break;
                    }
                ?>
                <tr data-type="<?= $row['movement_type'] ?>" data-date="<?= date('Y-m-d', strtotime($row['movement_date'])) ?>">
                    <td><?= date('Y-m-d H:i', strtotime($row['movement_date'])) ?></td>
                    <td><?= htmlspecialchars($row['item_name'] ?? 'Unknown') ?></td>
                    <td><?= $type_badge ?></td>
                    <td><?= number_format($row['quantity'], 2) ?></td>
                    <td>Frw <?= number_format($row['unit_cost'], 2) ?></td>
                    <td>Frw <?= number_format($row['total_cost'], 2) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td><?= htmlspecialchars($user_name) ?></td>
                    <td><?= $row['reference_id'] ? htmlspecialchars($row['reference_id']) : '-' ?></td>
                </tr>
                <?php }
                if (!$has_movements): ?>
                <tr><td colspan="9"><div class='alert alert-info text-center p-2 rounded-2 mt-2 mb-2'>No inventory movements found</div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('movement-search-input');
    const fromDate = document.getElementById('from-date');
    const toDate = document.getElementById('to-date');
    const clearFiltersBtn = document.getElementById('clear-filters');
    const tableBody = document.getElementById('movements-table-body');
    const rows = tableBody.querySelectorAll('tr');
    
    // Date inputs start empty - no default filtering
    
    // Filter function
    function filterMovements() {
        const searchTerm = searchInput.value.toLowerCase();
        const fromDateValue = fromDate.value;
        const toDateValue = toDate.value;
        const activeFilter = document.querySelector('#movement-filters .nav-link.active').dataset.filter;
        
        let visibleCount = 0;
        
        rows.forEach(function(row) {
            if (row.querySelector('td')) { // Skip if it's not a data row
                const text = row.textContent.toLowerCase();
                const type = row.dataset.type;
                const date = row.dataset.date;
                
                // Check if row matches search term
                const matchesSearch = text.includes(searchTerm);
                
                // Check if row matches date range
                const matchesDate = (!fromDateValue || date >= fromDateValue) && 
                                  (!toDateValue || date <= toDateValue);
                
                // Check if row matches type filter
                const matchesType = (activeFilter === 'all' || type === activeFilter);
                
                // Show row if it matches all criteria
                if (matchesSearch && matchesDate && matchesType) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
        });
        
        // Update "no results" message
        updateNoResultsMessage(visibleCount);
    }
    
    // Update "no results" message
    function updateNoResultsMessage(visibleCount) {
        let noResultsRow = tableBody.querySelector('tr[data-no-results]');
        
        if (visibleCount === 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.setAttribute('data-no-results', 'true');
                noResultsRow.innerHTML = '<td colspan="9"><div class="alert alert-warning text-center p-2 rounded-2 mt-2 mb-2">No movements match your search criteria</div></td>';
                tableBody.appendChild(noResultsRow);
            }
        } else if (noResultsRow) {
            noResultsRow.remove();
        }
    }
    
    // Event listeners
    searchInput.addEventListener('input', filterMovements);
    fromDate.addEventListener('change', filterMovements);
    toDate.addEventListener('change', filterMovements);
    
    // Clear filters
    clearFiltersBtn.addEventListener('click', function() {
        searchInput.value = '';
        fromDate.value = '';
        toDate.value = '';
        filterMovements();
    });
    
    // Filter logic for movement types
    document.querySelectorAll('#movement-filters .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#movement-filters .nav-link').forEach(function(l) { l.classList.remove('active'); });
            link.classList.add('active');
            filterMovements();
        });
    });
    
    // Initial filter
    filterMovements();
});
</script>
