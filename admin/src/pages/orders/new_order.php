<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="orders">Orders</a></li>
        <li class="breadcrumb-item active">New Order</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Create New Order</h2>
        </div>
    </div>
    
    <?php
    if (!empty($_SESSION['notification'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['notification']) . '</div>';
        unset($_SESSION['notification']);
    }
    ?>
    
    <form id="newOrderForm" action="./src/services/orders/create_order.php" method="POST">
        <div class="row">
            <!-- Customer Information -->
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Customer Type</label>
                            <select class="form-select" id="customerType" name="customer_type" required>
                                <option value="">Select Customer Type</option>
                                <option value="existing">Existing Customer</option>
                                <option value="walkin">Walk-in Customer</option>
                                <option value="new">New Customer</option>
                            </select>
                        </div>
                        
                        <div id="existingCustomerSection" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Select Customer</label>
                                <select class="form-select" id="existingCustomer" name="customer_id">
                                    <option value="">Select Customer</option>
                                    <?php
                                    $customers_stmt = $conn->prepare("SELECT customer_id, first_name, last_name, email FROM customers WHERE deleted_at IS NULL ORDER BY first_name");
                                    $customers_stmt->execute();
                                    $customers_result = $customers_stmt->get_result();
                                    while ($customer = $customers_result->fetch_assoc()) {
                                        echo "<option value='{$customer['customer_id']}'>{$customer['first_name']} {$customer['last_name']} ({$customer['email']})</option>";
                                    }
                                    $customers_stmt->close();
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div id="newCustomerSection" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="firstName" name="first_name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="lastName" name="last_name">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Information -->
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Order Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Order Type</label>
                            <select class="form-select" id="orderType" name="order_type" required>
                                <option value="">Select Order Type</option>
                                <option value="dine_in">Dine In</option>
                                <option value="takeaway">Takeaway</option>
                                <option value="delivery">Delivery</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        
                        <div id="tableSection" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Table</label>
                                <select class="form-select" id="tableId" name="table_id">
                                    <option value="">Select Table</option>
                                    <?php
                                    $tables_stmt = $conn->prepare("SELECT table_id, table_number, capacity FROM restaurant_tables WHERE is_available = 1 AND deleted_at IS NULL ORDER BY table_number");
                                    $tables_stmt->execute();
                                    $tables_result = $tables_stmt->get_result();
                                    while ($table = $tables_result->fetch_assoc()) {
                                        echo "<option value='{$table['table_id']}'>Table {$table['table_number']} (Capacity: {$table['capacity']})</option>";
                                    }
                                    $tables_stmt->close();
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div id="deliverySection" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Delivery Address</label>
                                <textarea class="form-control" id="deliveryAddress" name="delivery_address" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Delivery Fee (Frw)</label>
                                <input type="number" step="0.01" class="form-control" id="deliveryFee" name="delivery_fee" value="0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" id="paymentMethod" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="online">Online</option>
                                <option value="mobile">Mobile Money</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Special Instructions</label>
                            <textarea class="form-control" id="specialInstructions" name="special_instructions" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Order Items</h5>
                <button type="button" class="btn btn-primary btn-sm" onclick="addOrderItem()">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
            <div class="card-body">
                <div id="orderItems">
                    <div class="row mb-3 order-item">
                        <div class="col-md-5">
                            <label class="form-label">Menu Item</label>
                            <select class="form-select menu-item-select" name="menu_items[]" required>
                                <option value="">Select Menu Item</option>
                                <?php
                                $menu_stmt = $conn->prepare("
                                    SELECT menu_item_id, item_name, price, category_name 
                                    FROM menu_items mi 
                                    LEFT JOIN categories c ON mi.category_id = c.category_id 
                                    WHERE mi.is_available = 1 AND mi.deleted_at IS NULL
                                    ORDER BY item_name
                                ");
                                $menu_stmt->execute();
                                $menu_result = $menu_stmt->get_result();
                                while ($item = $menu_result->fetch_assoc()) {
                                    echo "<option value='{$item['menu_item_id']}' data-price='{$item['price']}'>{$item['item_name']} - Frw " . number_format($item['price'], 2) . " ({$item['category_name']})</option>";
                                }
                                $menu_stmt->close();
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control quantity-input" name="quantities[]" min="1" value="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit Price</label>
                            <input type="number" class="form-control unit-price" step="0.01" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Total</label>
                            <input type="number" class="form-control item-total" step="0.01" readonly>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6>Order Summary</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="subtotal">Frw 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (10%):</span>
                            <span id="tax">Frw 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Delivery Fee:</span>
                            <span id="deliveryFeeDisplay">Frw 0.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total Amount:</strong>
                            <strong id="totalAmount">Frw 0.00</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-cart"></i> Create Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Customer type change handler
    document.getElementById('customerType').addEventListener('change', function() {
        const type = this.value;
        document.getElementById('existingCustomerSection').style.display = type === 'existing' ? 'block' : 'none';
        document.getElementById('newCustomerSection').style.display = type === 'new' ? 'block' : 'none';
        
        if (type !== 'existing') {
            document.getElementById('existingCustomer').value = '';
        }
    });
    
    // Order type change handler
    document.getElementById('orderType').addEventListener('change', function() {
        const type = this.value;
        document.getElementById('tableSection').style.display = type === 'dine_in' ? 'block' : 'none';
        document.getElementById('deliverySection').style.display = type === 'delivery' ? 'block' : 'none';
        
        if (type !== 'dine_in') {
            document.getElementById('tableId').value = '';
        }
        if (type !== 'delivery') {
            document.getElementById('deliveryAddress').value = '';
            document.getElementById('deliveryFee').value = '0';
        }
        
        updateOrderSummary();
    });
    
    // Delivery fee change handler
    document.getElementById('deliveryFee').addEventListener('input', updateOrderSummary);
    
    // Menu item and quantity change handlers
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('menu-item-select') || e.target.classList.contains('quantity-input')) {
            updateItemTotal(e.target.closest('.order-item'));
            updateOrderSummary();
        }
    });
});

function addOrderItem() {
    const itemsContainer = document.getElementById('orderItems');
    const newItem = document.createElement('div');
    newItem.className = 'row mb-3 order-item';
    newItem.innerHTML = `
        <div class="col-md-5">
            <label class="form-label">Menu Item</label>
            <select class="form-select menu-item-select" name="menu_items[]" required>
                <option value="">Select Menu Item</option>
                <?php
                $menu_stmt = $conn->prepare("
                                    SELECT menu_item_id, item_name, price, category_name 
                                    FROM menu_items mi 
                                    LEFT JOIN categories c ON mi.category_id = c.category_id 
                                    WHERE mi.is_available = 1 AND mi.deleted_at IS NULL
                                    ORDER BY item_name
                                ");
                                $menu_stmt->execute();
                                $menu_result = $menu_stmt->get_result();
                                while ($item = $menu_result->fetch_assoc()) {
                                    echo "<option value='{$item['menu_item_id']}' data-price='{$item['price']}'>{$item['item_name']} - Frw " . number_format($item['price'], 2) . " ({$item['category_name']})</option>";
                                }
                                $menu_stmt->close();
                ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Quantity</label>
            <input type="number" class="form-control quantity-input" name="quantities[]" min="1" value="1" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Unit Price</label>
            <input type="number" class="form-control unit-price" step="0.01" readonly>
        </div>
        <div class="col-md-2">
            <label class="form-label">Total</label>
            <div class="input-group">
                <input type="number" class="form-control item-total" step="0.01" readonly>
                <button type="button" class="btn btn-outline-danger" onclick="removeOrderItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    itemsContainer.appendChild(newItem);
}

function removeOrderItem(button) {
    button.closest('.order-item').remove();
    updateOrderSummary();
}

function updateItemTotal(itemRow) {
    const menuSelect = itemRow.querySelector('.menu-item-select');
    const quantityInput = itemRow.querySelector('.quantity-input');
    const unitPriceInput = itemRow.querySelector('.unit-price');
    const totalInput = itemRow.querySelector('.item-total');
    
    if (menuSelect.value && quantityInput.value) {
        const selectedOption = menuSelect.options[menuSelect.selectedIndex];
        const unitPrice = parseFloat(selectedOption.dataset.price) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        
        unitPriceInput.value = unitPrice.toFixed(2);
        totalInput.value = (unitPrice * quantity).toFixed(2);
    } else {
        unitPriceInput.value = '';
        totalInput.value = '';
    }
}

function updateOrderSummary() {
    let subtotal = 0;
    const orderItems = document.querySelectorAll('.order-item');
    
    orderItems.forEach(item => {
        const totalInput = item.querySelector('.item-total');
        if (totalInput.value) {
            subtotal += parseFloat(totalInput.value);
        }
    });
    
    const tax = subtotal * 0.1; // 10% tax
    const deliveryFee = parseFloat(document.getElementById('deliveryFee').value) || 0;
    const total = subtotal + tax + deliveryFee;
    
    document.getElementById('subtotal').textContent = `Frw ${subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `Frw ${tax.toFixed(2)}`;
    document.getElementById('deliveryFeeDisplay').textContent = `Frw ${deliveryFee.toFixed(2)}`;
    document.getElementById('totalAmount').textContent = `Frw ${total.toFixed(2)}`;
}

// Form submission
document.getElementById('newOrderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Debug: Log all form data
    console.log('=== FORM DATA DEBUG ===');
    for (let [key, value] of formData.entries()) {
        console.log(key + ':', value, typeof value);
    }
    console.log('======================');
        
    // Validate at least one item is selected
    const menuItems = formData.getAll('menu_items[]');
    const validItems = menuItems.filter(item => item !== '');
    
    if (validItems.length === 0) {
        alert('Please add at least one menu item to the order.');
        return;
    }
    
    // Submit the form
    fetch('./src/services/orders/create_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', [...response.headers.entries()]);
        return response.text().then(text => {
            console.log('Raw response text:', text);
            console.log('Response text length:', text.length);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Text that failed to parse:', text);
                throw new Error('Invalid JSON response: ' + text);
            }
        });
    })
    .then(data => {
        console.log('Parsed JSON data:', data);
        if (data.success) {
            window.location.href = 'orders';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Full error:', error);
        alert('Error creating order: ' + error.message);
    });
});
</script>
