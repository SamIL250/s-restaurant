<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="reservations">Reservations</a></li>
        <li class="breadcrumb-item active">New Reservation</li>
    </ol>
</nav>

<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">New Reservation</h2>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="src/services/reservations/add_reservation.php" method="POST" id="reservationForm">
                        <div class="row g-3">
                            <!-- Customer Information -->
                            <div class="col-12">
                                <h5 class="mb-3">Customer Information</h5>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="customer_name" class="form-label">Customer Name *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="customer_phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="customer_phone" name="customer_phone" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="customer_email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="customer_email" name="customer_email" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="number_of_guests" class="form-label">Number of Guests *</label>
                                <input type="number" class="form-control" id="number_of_guests" name="number_of_guests" min="1" max="20" required>
                            </div>
                            
                            <!-- Reservation Details -->
                            <div class="col-12">
                                <h5 class="mb-3 mt-4">Reservation Details</h5>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="reservation_date" class="form-label">Reservation Date *</label>
                                <input type="date" class="form-control" id="reservation_date" name="reservation_date" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="reservation_time" class="form-label">Reservation Time *</label>
                                <input type="time" class="form-control" id="reservation_time" name="reservation_time" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="table_id" class="form-label">Table Assignment</label>
                                <select class="form-select" id="table_id" name="table_id">
                                    <option value="">No table assigned</option>
                                    <?php
                                    $tables_query = mysqli_query($conn, "
                                        SELECT table_id, table_number, capacity, location, is_available 
                                        FROM restaurant_tables 
                                        WHERE is_available = 1 
                                        ORDER BY table_number
                                    ");
                                    while ($table = mysqli_fetch_assoc($tables_query)) {
                                        $selected = '';
                                        echo "<option value='{$table['table_id']}' {$selected}>
                                            Table {$table['table_number']} ({$table['capacity']} seats) - {$table['location']}
                                        </option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Total Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">Frw</span>
                                    <input type="text" class="form-control" id="total_amount_display" readonly value="0.00">
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label for="special_requests" class="form-label">Special Requests</label>
                                <textarea class="form-control" id="special_requests" name="special_requests" rows="3" placeholder="Any special requests or notes..."></textarea>
                            </div>
                            
                            <!-- Menu Items -->
                            <div class="col-12">
                                <h5 class="mb-3 mt-4">Menu Items</h5>
                                <div id="menuItemsContainer">
                                    <div class="menu-item-row row g-2 mb-2">
                                        <div class="col-md-4">
                                            <select class="form-select menu-item-select" name="menu_items[0][menu_item_id]">
                                                <option value="">Select menu item</option>
                                                <?php
                                                $menu_query = mysqli_query($conn, "
                                                    SELECT menu_item_id, item_name, price, description 
                                                    FROM menu_items 
                                                    WHERE is_available = 1 
                                                    ORDER BY item_name
                                                ");
                                                while ($item = mysqli_fetch_assoc($menu_query)) {
                                                    echo "<option value='{$item['menu_item_id']}' data-price='{$item['price']}'>
                                                        {$item['item_name']} - Frw {$item['price']}
                                                    </option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" class="form-control" name="menu_items[0][quantity]" placeholder="Qty" min="1" value="1">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" class="form-control menu-item-price" name="menu_items[0][unit_price]" placeholder="Price" step="0.01" min="0" readonly>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" name="menu_items[0][special_instructions]" placeholder="Special instructions">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-menu-item" style="display: none;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="addMenuItem">
                                    <i class="fas fa-plus me-1"></i>Add Menu Item
                                </button>
                            </div>
                            
                            <!-- Submit Buttons -->
                            <div class="col-12 mt-4">
                                <hr>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Create Reservation
                                    </button>
                                    <a href="reservations" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sidebar with Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Reservation Summary</h6>
                </div>
                <div class="card-body">
                    <div id="reservationSummary">
                        <p class="text-muted">Fill in the form to see reservation summary</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('reservation_date').min = today;
    
    // Set default time to current time + 1 hour
    const now = new Date();
    now.setHours(now.getHours() + 1);
    const timeString = now.toTimeString().slice(0, 5);
    document.getElementById('reservation_time').value = timeString;
    
    // Menu item functionality
    let menuItemCounter = 1;
    
    document.getElementById('addMenuItem').addEventListener('click', function() {
        const container = document.getElementById('menuItemsContainer');
        const newRow = document.createElement('div');
        newRow.className = 'menu-item-row row g-2 mb-2';
        newRow.innerHTML = `
            <div class="col-md-4">
                <select class="form-select menu-item-select" name="menu_items[${menuItemCounter}][menu_item_id]">
                    <option value="">Select menu item</option>
                    ${document.querySelector('.menu-item-select').innerHTML.replace(/menu_items\[0\]/g, `menu_items[${menuItemCounter}]`)}
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="menu_items[${menuItemCounter}][quantity]" placeholder="Qty" min="1" value="1">
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control menu-item-price" name="menu_items[${menuItemCounter}][unit_price]" placeholder="Price" step="0.01" min="0" readonly>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="menu_items[${menuItemCounter}][special_instructions]" placeholder="Special instructions">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger btn-sm remove-menu-item">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(newRow);
        menuItemCounter++;
        
        // Show remove buttons for all rows except the first
        document.querySelectorAll('.remove-menu-item').forEach(btn => btn.style.display = 'block');
    });
    
    // Handle menu item selection and price updates
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('menu-item-select')) {
            const row = e.target.closest('.menu-item-row');
            const priceInput = row.querySelector('.menu-item-price');
            const selectedOption = e.target.options[e.target.selectedIndex];
            const price = selectedOption.dataset.price || '';
            priceInput.value = price;
            updateSummary();
        }
    });
    
    // Handle quantity changes
    document.addEventListener('input', function(e) {
        if (e.target.name && e.target.name.includes('[quantity]')) {
            updateSummary();
        }
    });
    
    // Remove menu item
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-menu-item') || e.target.closest('.remove-menu-item')) {
            const row = e.target.closest('.menu-item-row');
            row.remove();
            updateSummary();
            
            // Hide remove buttons if only one row remains
            if (document.querySelectorAll('.menu-item-row').length === 1) {
                document.querySelectorAll('.remove-menu-item').forEach(btn => btn.style.display = 'none');
            }
        }
    });
    
    // Update summary
    function updateSummary() {
        const summary = document.getElementById('reservationSummary');
        let total = 0;
        let items = [];
        
        document.querySelectorAll('.menu-item-row').forEach(row => {
            const select = row.querySelector('.menu-item-select');
            const quantity = row.querySelector('input[name*="[quantity]"]');
            const price = row.querySelector('.menu-item-price');
            
            if (select.value && quantity.value && price.value) {
                const itemTotal = parseFloat(quantity.value) * parseFloat(price.value);
                total += itemTotal;
                items.push({
                    name: select.options[select.selectedIndex].text,
                    quantity: quantity.value,
                    price: price.value,
                    total: itemTotal
                });
            }
        });
        
        // Update total amount display field
        document.getElementById('total_amount_display').value = total.toFixed(2);
        
        // Update summary display
        if (items.length > 0) {
            let summaryHTML = '<div class="mb-3">';
            items.forEach(item => {
                summaryHTML += `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-truncate" style="max-width: 150px;" title="${item.name}">${item.name}</span>
                        <span class="badge bg-light text-dark">${item.quantity} × Frw ${item.price}</span>
                    </div>
                `;
            });
            summaryHTML += '</div>';
            summaryHTML += `<hr><div class="d-flex justify-content-between"><strong>Total:</strong> <strong>Frw ${total.toFixed(2)}</strong></div>`;
            summary.innerHTML = summaryHTML;
        } else {
            summary.innerHTML = '<p class="text-muted">Fill in the form to see reservation summary</p>';
        }
    }
    
    // Form validation
    document.getElementById('reservationForm').addEventListener('submit', function(e) {
        const requiredFields = ['customer_name', 'customer_phone', 'customer_email', 'reservation_date', 'reservation_time', 'number_of_guests'];
        let isValid = true;
        
        requiredFields.forEach(field => {
            const element = document.getElementById(field);
            if (!element.value.trim()) {
                element.classList.add('is-invalid');
                isValid = false;
            } else {
                element.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
});
</script>
