<section id="menu" class="menu section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Menu</h2>
        <div><span>Check Our Tasty</span> <span class="description-title">Menu</span></div>
      </div><!-- End Section Title -->

      <div class="container isotope-layout" data-default-filter="*" data-layout="masonry" data-sort="original-order">

        <div class="row" data-aos="fade-up" data-aos-delay="100">
          <div class="col-lg-12 d-flex justify-content-center">
            <ul class="menu-filters isotope-filters">
              <li data-filter="*" class="filter-active">All</li>
              <?php
              // Fetch categories from database
              if ($conn) {
                $category_query = "SELECT category_id, category_name FROM categories WHERE is_active = 1 AND deleted_at IS NULL ORDER BY category_name";
                $category_result = mysqli_query($conn, $category_query);
                
                if ($category_result && mysqli_num_rows($category_result) > 0) {
                    while ($category = mysqli_fetch_assoc($category_result)) {
                        $category_filter = 'filter-' . strtolower(str_replace(' ', '-', $category['category_name']));
                        echo '<li data-filter=".' . $category_filter . '">' . htmlspecialchars($category['category_name']) . '</li>';
                    }
                }
              }
              ?>
            </ul>
          </div>
        </div><!-- Menu Filters -->

        <div class="row isotope-container" data-aos="fade-up" data-aos-delay="200">
          <?php
          // Fetch menu items from database
          if ($conn) {
            $menu_query = "SELECT mi.menu_item_id, mi.item_name, mi.description, mi.price, mi.image_url, c.category_name 
                          FROM menu_items mi 
                          LEFT JOIN categories c ON mi.category_id = c.category_id 
                          WHERE mi.is_available = 1 AND mi.deleted_at IS NULL 
                          AND (c.deleted_at IS NULL OR c.deleted_at IS NULL)
                          ORDER BY c.category_name, mi.item_name";
            $menu_result = mysqli_query($conn, $menu_query);
            
            // Group items by category
            $menu_items_by_category = [];
            if ($menu_result && mysqli_num_rows($menu_result) > 0) {
                while ($item = mysqli_fetch_assoc($menu_result)) {
                    $category_name = $item['category_name'] ?? 'Uncategorized';
                    if (!isset($menu_items_by_category[$category_name])) {
                        $menu_items_by_category[$category_name] = [];
                    }
                    $menu_items_by_category[$category_name][] = $item;
                }
            }
            
            // Display all menu items
            $has_items = false;
            foreach ($menu_items_by_category as $category_name => $items) {
                foreach ($items as $item) {
                    $has_items = true;
                    $category_filter = 'filter-' . strtolower(str_replace(' ', '-', $item['category_name']));
                    $image_path = !empty($item['image_url']) ? $item['image_url'] : 'assets/img/menu/default-menu-item.jpg';
                    $item_name = htmlspecialchars($item['item_name']);
                    $description = htmlspecialchars($item['description'] ?? 'Delicious item prepared with care');
                    $price = number_format($item['price'], 2);
                    
                    echo '<div class="col-lg-6 menu-item isotope-item ' . $category_filter . '" data-menu-item-id="' . (int) $item['menu_item_id'] . '">';
                    echo '<img src="' . $image_path . '" class="menu-img" alt="' . $item_name . '">';
                    echo '<div class="menu-content">';
                    echo '<a href="#">' . $item_name . '</a><span>Frw ' . $price . '</span>';
                    echo '</div>';
                    echo '<div class="menu-ingredients">';
                    echo $description;
                    echo '</div>';
                    echo '<div class="menu-order">';
                    echo '<button type="button" class="btn-favorite" data-menu-id="' . (int) $item['menu_item_id'] . '" onclick="toggleFavorite(' . (int) $item['menu_item_id'] . ', this)" title="Save to favorites"><i class="bi bi-heart"></i></button>';
                    echo '<button class="btn-order" onclick="addToCart(' . $item['menu_item_id'] . ', \'' . addslashes($item_name) . '\', ' . $item['price'] . ', \'' . addslashes($image_path) . '\')">Add to Cart</button>';
                    echo '</div>';
                    echo '</div><!-- Menu Item -->';
                }
            }
            
            // Show message if no items at all
            if (!$has_items) {
                echo '<div class="col-12 text-center">';
                echo '<div class="no-items-message">';
                echo '<i class="bi bi-inbox" style="font-size: 48px; color: var(--accent-color); margin-bottom: 20px;"></i>';
                echo '<h4>No menu items available</h4>';
                echo '<p>We\'re preparing delicious items for you. Please check back soon!</p>';
                echo '</div>';
                echo '</div>';
            }
          } else {
            echo '<div class="col-12 text-center"><p>Unable to connect to database. Please try again later.</p></div>';
          }
          ?>
        </div><!-- Menu Container -->

        <!-- Empty Category Message -->
        <div id="emptyCategoryMessage" class="col-12 text-center" style="display: none;">
          <div class="no-items-message">
            <i class="bi bi-funnel" style="font-size: 48px; color: var(--accent-color); margin-bottom: 20px;"></i>
            <h4>No items in this category</h4>
            <p>Try selecting a different category to see more delicious items!</p>
          </div>
        </div>

        <!-- Cart Button -->
        <div class="cart-button-container">
            <button class="cart-btn" onclick="console.log('Cart button clicked'); openCartModal();">
                <i class="bi bi-cart3"></i>
                <span class="cart-count">0</span>
                <span class="cart-total">Frw 0</span>
            </button>
        </div>

      </div>

    </section><!-- End Menu Section -->

    <!-- Order Modal -->
    <div class="order-modal" id="orderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Your Order</h3>
                <button class="close-btn" onclick="closeCartModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="cart-items" id="cartItems">
                    <!-- Cart items will be dynamically added here -->
                </div>
                <div class="order-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">Frw 0</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span id="total">Frw 0</span>
                    </div>
                </div>
                <form id="orderForm">
                    <div class="form-row" id="guestCheckoutFields">
                        <div class="form-group">
                            <label for="customer_name">Full Name *</label>
                            <input type="text" id="customer_name" name="customer_name">
                        </div>
                        <div class="form-group">
                            <label for="customer_phone">Phone *</label>
                            <input type="tel" id="customer_phone" name="customer_phone" placeholder="0788123456">
                        </div>
                    </div>
                    <div class="form-row" id="guestEmailRow">
                        <div class="form-group">
                            <label for="customer_email">Email *</label>
                            <input type="email" id="customer_email" name="customer_email">
                        </div>
                        <div class="form-group">
                            <label for="order_type">Order Type</label>
                            <select id="order_type" name="order_type">
                                <option value="takeaway">Takeaway</option>
                                <option value="dine_in">Dine In</option>
                                <option value="delivery">Delivery</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row logged-in-order-type" id="loggedInOrderTypeRow" style="display:none;">
                        <div class="form-group full-width">
                            <label for="order_type_logged_in">Order Type</label>
                            <select id="order_type_logged_in" name="order_type_logged_in">
                                <option value="takeaway">Takeaway</option>
                                <option value="dine_in">Dine In</option>
                                <option value="delivery">Delivery</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row" id="address_field" style="display: none;">
                        <div class="form-group full-width">
                            <label for="delivery_address">Delivery Address</label>
                            <input type="text" id="delivery_address" name="delivery_address" placeholder="Enter your delivery address">
                        </div>
                    </div>
                    <div class="alert alert-light border mb-3">
                        No online payment required. After placing your order, confirm it with us on WhatsApp and pay when you pick up or receive delivery.
                    </div>
                    <div class="form-group full-width">
                        <label for="special_instructions">Special Instructions</label>
                        <textarea id="special_instructions" name="special_instructions" rows="3" placeholder="Any special requests or dietary requirements?"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <div id="formMessages">
                    <div id="loadingMessage" class="loading-message" style="display: none;">
                        <div class="message-content">
                            <div class="message-icon">
                                <div class="spinner"></div>
                            </div>
                            <div class="message-text">
                                <h4>Processing Your Order</h4>
                                <p>Please wait while we process your order...</p>
                            </div>
                        </div>
                    </div>
                    <div id="errorMessage" class="error-message" style="display: none;">
                        <div class="message-content">
                            <div class="message-icon">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="message-text">
                                <h4>Oops! Something Needs Attention</h4>
                                <p class="error-details"></p>
                            </div>
                        </div>
                    </div>
                    <div id="successMessage" class="success-message" style="display: none;">
                        <div class="message-content">
                            <div class="message-icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="message-text">
                                <h4>Order Placed Successfully!</h4>
                                <p class="success-details"></p>
                                <div id="whatsappAction" style="display:none; margin-top: 12px;">
                                    <a id="whatsappConfirmBtn" href="#" target="_blank" class="btn btn-success btn-sm">Confirm on WhatsApp</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button class="btn-secondary" onclick="closeCartModal()">Cancel</button>
                    <button class="btn-primary" onclick="placeOrder()">Place Order</button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .menu-order {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 12px;
    }

    .btn-favorite {
        background: #fff;
        border: 1px solid #dee2e6;
        color: #c0392b;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .btn-favorite.active {
        background: #c0392b;
        color: #fff;
        border-color: #c0392b;
    }

    .btn-order {
        flex: 1;
    }

    /* Cart Button Styles */
    .cart-button-container {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
    }

    .cart-btn {
        background-color:  #c0392b;
        color: white;
        border: none;
        border-radius: 50px;
        padding: 15px 25px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
    }

    .cart-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.3);
    }

    .cart-count {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    .cart-total {
        font-weight: 700;
    }

    /* Modal Styles */
    .order-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 2000;
        overflow-y: auto;
    }

    .modal-content {
        background: white;
        margin: 50px auto;
        max-width: 600px;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        animation: slideUp 0.3s ease-out;
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
        color: var(--default-color);
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }

    .modal-body {
        padding: 20px;
    }

    .cart-items {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 20px;
    }

    .cart-item {
        display: flex;
        align-items: center;
        padding: 15px;
        border: 1px solid #eee;
        border-radius: 8px;
        margin-bottom: 10px;
    }

    .cart-item img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        margin-right: 15px;
    }

    .cart-item-details {
        flex: 1;
    }

    .cart-item-name {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .cart-item-price {
        color: var(--accent-color);
        font-weight: 500;
    }

    .cart-item-quantity {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .quantity-btn {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        width: 30px;
        height: 30px;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .quantity-btn:hover {
        background: var(--accent-color);
        color: white;
    }

    .remove-btn {
        background: #dc3545;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 12px;
    }

    .order-summary {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .summary-row.total {
        font-weight: 700;
        font-size: 18px;
        border-top: 2px solid #dee2e6;
        padding-top: 10px;
    }

    .form-row {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
    }

    .form-group {
        flex: 1;
    }

    .form-group.full-width {
        width: 100%;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: var(--default-color);
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        font-size: 14px;
    }

    .modal-footer {
        padding: 20px;
        border-top: 1px solid #eee;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
    }

    .btn-primary {
        background: var(--accent-color);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Message Styles (same as reservation) */
    .loading-message,
    .error-message,
    .success-message {
      margin: 20px 0;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      animation: slideIn 0.3s ease-out;
    }

    .message-content {
      display: flex;
      align-items: center;
      padding: 20px;
      gap: 15px;
    }

    .message-icon {
      flex-shrink: 0;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }

    .message-text {
      flex: 1;
      text-align: left;
    }

    .message-text h4 {
      margin: 0 0 5px 0;
      font-size: 18px;
      font-weight: 600;
    }

    .message-text p {
      margin: 0;
      font-size: 14px;
      line-height: 1.4;
    }

    .loading-message {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }

    .loading-message .message-icon {
      background: rgba(255, 255, 255, 0.2);
    }

    .spinner {
      width: 24px;
      height: 24px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-top: 3px solid white;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    .error-message {
      background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
      color: white;
    }

    .error-message .message-icon {
      background: rgba(255, 255, 255, 0.2);
    }

    .success-message {
      background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
      color: white;
    }

    .success-message .message-icon {
      background: rgba(255, 255, 255, 0.2);
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .cart-button-container {
            bottom: 20px;
            right: 20px;
        }
        
        .modal-content {
            margin: 20px;
            max-width: 100%;
        }
        
        .form-row {
            flex-direction: column;
            gap: 10px;
        }
    }
    </style>

    <script>
    // Cart functionality
    let cart = [];

    function addToCart(itemId, itemName, price, image) {
        const existingItem = cart.find(item => item.menu_item_id === itemId);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                menu_item_id: itemId,
                name: itemName,
                price: price,
                image: image,
                quantity: 1
            });
        }
        
        updateCartDisplay();
        showNotification('Item added to cart!');
    }

    function removeFromCart(itemId) {
        cart = cart.filter(item => item.menu_item_id !== itemId);
        updateCartDisplay();
        // Update modal if it's open
        updateModalIfOpen();
    }

    function updateQuantity(itemId, change) {
        const item = cart.find(item => item.menu_item_id === itemId);
        if (item) {
            item.quantity += change;
            if (item.quantity <= 0) {
                removeFromCart(itemId);
            } else {
                updateCartDisplay();
                // Update modal if it's open
                updateModalIfOpen();
            }
        }
    }

    function updateCartDisplay() {
        const cartCount = document.querySelector('.cart-count');
        const cartTotal = document.querySelector('.cart-total');
        
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        cartCount.textContent = totalItems;
        cartTotal.textContent = 'Frw ' + totalPrice.toFixed(2);
    }

    function updateModalIfOpen() {
        const modal = document.getElementById('orderModal');
        if (modal.style.display === 'block') {
            const cartItems = document.getElementById('cartItems');
            
            // Re-render cart items
            cartItems.innerHTML = '';
            cart.forEach(item => {
                const cartItem = document.createElement('div');
                cartItem.className = 'cart-item';
                cartItem.innerHTML = `
                    <img src="${item.image}" alt="${item.name}">
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">Frw ${item.price.toFixed(2)}</div>
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn" onclick="updateQuantity(${item.menu_item_id}, -1)">-</button>
                        <span>${item.quantity}</span>
                        <button class="quantity-btn" onclick="updateQuantity(${item.menu_item_id}, 1)">+</button>
                    </div>
                    <button class="remove-btn" onclick="removeFromCart(${item.menu_item_id})">Remove</button>
                `;
                cartItems.appendChild(cartItem);
            });
            
            // Update order summary
            updateOrderSummary();
            
            // Close modal if cart is empty
            if (cart.length === 0) {
                closeCartModal();
                showNotification('Your cart is empty!');
            }
        }
    }

    function openCartModal() {
        const modal = document.getElementById('orderModal');
        const cartItems = document.getElementById('cartItems');
        
        // Display cart items
        cartItems.innerHTML = '';
        cart.forEach(item => {
            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            cartItem.innerHTML = `
                <img src="${item.image}" alt="${item.name}">
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">Frw ${item.price.toFixed(2)}</div>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn" onclick="updateQuantity(${item.menu_item_id}, -1)">-</button>
                    <span>${item.quantity}</span>
                    <button class="quantity-btn" onclick="updateQuantity(${item.menu_item_id}, 1)">+</button>
                </div>
                <button class="remove-btn" onclick="removeFromCart(${item.menu_item_id})">Remove</button>
            `;
            cartItems.appendChild(cartItem);
        });
        
        // Update order summary
        updateOrderSummary();
        
        // Show empty cart message if cart is empty, otherwise show modal
        if (cart.length === 0) {
            cartItems.innerHTML = '<div class="text-center p-4">Your cart is empty</div>';
        }
        
        modal.style.display = 'block';
    }

    function closeCartModal() {
        const modal = document.getElementById('orderModal');
        modal.style.display = 'none';
    }

    function updateOrderSummary() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        const subtotalElement = document.getElementById('subtotal');
        const totalElement = document.getElementById('total');
        
        if (subtotalElement) {
            subtotalElement.textContent = 'Frw ' + subtotal.toFixed(2);
        }
        if (totalElement) {
            totalElement.textContent = 'Frw ' + subtotal.toFixed(2);
        }
    }

    function placeOrder() {
        const form = document.getElementById('orderForm');
        const isLoggedIn = window.customerAccount && window.customerAccount.logged_in;
        const customerName = isLoggedIn ? window.customerAccount.customer.name : document.getElementById('customer_name').value.trim();
        const customerPhone = isLoggedIn ? window.customerAccount.customer.phone : document.getElementById('customer_phone').value.trim();
        const customerEmail = isLoggedIn ? window.customerAccount.customer.email : document.getElementById('customer_email').value.trim();
        const orderType = isLoggedIn
            ? document.getElementById('order_type_logged_in').value
            : document.getElementById('order_type').value;
        const specialInstructions = document.getElementById('special_instructions').value.trim();
        const deliveryAddress = document.getElementById('delivery_address').value.trim();
        
        if (!isLoggedIn && (!customerName || !customerPhone || !customerEmail)) {
            showNotification('Please fill in all required fields or sign in.');
            return;
        }
        
        if (orderType === 'delivery' && !deliveryAddress) {
            showNotification('Delivery address is required for delivery orders.');
            return;
        }
        
        if (cart.length === 0) {
            showNotification('Your cart is empty!');
            return;
        }
        
        document.getElementById('loadingMessage').style.display = 'none';
        document.getElementById('errorMessage').style.display = 'none';
        document.getElementById('successMessage').style.display = 'none';
        document.getElementById('whatsappAction').style.display = 'none';
        document.getElementById('loadingMessage').style.display = 'block';
        
        const formData = new FormData();
        if (!isLoggedIn) {
            formData.append('name', customerName);
            formData.append('phone', customerPhone);
            formData.append('email', customerEmail);
        }
        formData.append('order_type', orderType);
        formData.append('special_instructions', specialInstructions);
        
        if (orderType === 'delivery') {
            formData.append('delivery_address', deliveryAddress);
        }
        
        cart.forEach(item => {
            formData.append('menu_item_id[]', item.menu_item_id);
            formData.append('quantity[]', item.quantity);
            formData.append('unit_price[]', item.price);
            formData.append('item_name[]', item.name);
        });
        
        fetch((window.SITE_BASE || '') + '/services/order/process_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('loadingMessage').style.display = 'none';
            
            if (data.success) {
                let successText = data.message;
                if (data.email_sent) {
                    successText += ' A confirmation email has been sent.';
                }
                document.getElementById('successMessage').querySelector('.success-details').textContent = successText;
                document.getElementById('successMessage').style.display = 'block';

                if (data.whatsapp_url) {
                    const whatsappBtn = document.getElementById('whatsappConfirmBtn');
                    whatsappBtn.href = data.whatsapp_url;
                    document.getElementById('whatsappAction').style.display = 'block';
                }
                
                cart = [];
                updateCartDisplay();
                form.reset();
                updateOrderSummary();
                if (window.applyCheckoutAccountState) {
                    window.applyCheckoutAccountState();
                }
            } else {
                document.getElementById('errorMessage').querySelector('.error-details').textContent = data.message;
                document.getElementById('errorMessage').style.display = 'block';
                
                setTimeout(() => {
                    document.getElementById('errorMessage').style.display = 'none';
                }, 5000);
            }
        })
        .catch(error => {
            document.getElementById('loadingMessage').style.display = 'none';
            document.getElementById('errorMessage').querySelector('.error-details').textContent = 'An error occurred. Please try again.';
            document.getElementById('errorMessage').style.display = 'block';
            console.error('Error:', error);
            
            setTimeout(() => {
                document.getElementById('errorMessage').style.display = 'none';
            }, 5000);
        });
    }

    function showNotification(message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ffb03b;
            color: #ffffff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(255, 176, 59, 0.3);
            z-index: 3000;
            animation: slideIn 0.3s ease-out;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            font-weight: 500;
            border-left: 4px solid #433f39;
            max-width: 300px;
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('orderModal');
        if (event.target === modal) {
            closeCartModal();
        }
    }

    // Handle order type change to show/hide address field
    document.addEventListener('DOMContentLoaded', function() {
        const orderTypeSelect = document.getElementById('order_type');
        const addressField = document.getElementById('address_field');
        
        if (orderTypeSelect && addressField) {
            orderTypeSelect.addEventListener('change', function() {
                if (this.value === 'delivery') {
                    addressField.style.display = 'flex';
                    document.getElementById('delivery_address').required = true;
                } else {
                    addressField.style.display = 'none';
                    document.getElementById('delivery_address').required = false;
                    document.getElementById('delivery_address').value = '';
                }
            });
        }
    });

    // Handle empty category filtering
    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('.menu-filters li');
        const menuItems = document.querySelectorAll('.isotope-item');
        const emptyCategoryMessage = document.getElementById('emptyCategoryMessage');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Update active filter
                filterButtons.forEach(btn => btn.classList.remove('filter-active'));
                this.classList.add('filter-active');
                
                // Filter items - show items that match the selected category
                let visibleItems = 0;
                const isotopeContainer = document.querySelector('.isotope-container');
                
                menuItems.forEach(item => {
                    if (filter === '*' || item.classList.contains(filter.substring(1))) {
                        // Show items that match the filter (remove the '.' from data-filter)
                        item.style.display = 'block';
                        visibleItems++;
                    } else {
                        // Hide items that don't match
                        item.style.display = 'none';
                    }
                });
                
                // Always show the container
                isotopeContainer.style.display = 'flex';
                isotopeContainer.style.minHeight = 'auto';
                
                // Show/hide empty message
                if (visibleItems === 0) {
                    emptyCategoryMessage.style.display = 'flex';
                    emptyCategoryMessage.style.justifyContent = 'center';
                    emptyCategoryMessage.style.alignItems = 'center';
                    emptyCategoryMessage.style.minHeight = '300px';
                } else {
                    emptyCategoryMessage.style.display = 'none';
                    emptyCategoryMessage.style.minHeight = 'auto';
                }
            });
        });
    });
    </script>