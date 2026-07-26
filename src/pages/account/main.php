<section class="section" style="padding-top: 140px; min-height: 80vh;">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
      <div>
        <h2 class="mb-1">My Account</h2>
        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($currentCustomer['name']); ?></p>
      </div>
      <a href="<?php echo SITE_WEB_PATH; ?>/#menu" class="btn btn-primary" style="background:#c0392b;border:none;">Order Again</a>
    </div>

    <ul class="nav nav-tabs mb-4" id="accountTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders-panel" type="button" role="tab">My Orders</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="favorites-tab" data-bs-toggle="tab" data-bs-target="#favorites-panel" type="button" role="tab">Favorites</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-panel" type="button" role="tab">Profile</button>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane fade show active" id="orders-panel" role="tabpanel">
        <div id="ordersLoading" class="text-center py-5 text-muted">Loading your orders...</div>
        <div id="ordersList"></div>
      </div>

      <div class="tab-pane fade" id="favorites-panel" role="tabpanel">
        <div id="favoritesLoading" class="text-center py-5 text-muted">Loading your favorites...</div>
        <div id="favoritesList" class="row g-4"></div>
      </div>

      <div class="tab-pane fade" id="profile-panel" role="tabpanel">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4 p-md-5">
            <h5 class="mb-1">Profile Details</h5>
            <p class="text-muted mb-4">Update your contact information used for orders and checkout.</p>
            <div id="profileAlert" class="alert d-none" role="alert"></div>
            <form id="customerProfileForm">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label" for="profile_first_name">First Name</label>
                  <input type="text" class="form-control" id="profile_first_name" name="first_name" value="<?php echo htmlspecialchars($customerProfile['first_name'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label" for="profile_last_name">Last Name</label>
                  <input type="text" class="form-control" id="profile_last_name" name="last_name" value="<?php echo htmlspecialchars($customerProfile['last_name'] ?? ''); ?>">
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label" for="profile_email">Email</label>
                  <input type="email" class="form-control" id="profile_email" name="email" value="<?php echo htmlspecialchars($customerProfile['email'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label" for="profile_phone">Phone</label>
                  <input type="tel" class="form-control" id="profile_phone" name="phone" value="<?php echo htmlspecialchars($customerProfile['phone'] ?? ''); ?>" placeholder="0788123456" required>
                </div>
              </div>

              <hr class="my-4">
              <h6 class="mb-3">Change Password</h6>
              <p class="text-muted small mb-3">Leave blank to keep your current password.</p>
              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label" for="profile_current_password">Current Password</label>
                  <input type="password" class="form-control" id="profile_current_password" name="current_password" autocomplete="current-password">
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label" for="profile_new_password">New Password</label>
                  <input type="password" class="form-control" id="profile_new_password" name="new_password" minlength="6" autocomplete="new-password">
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label" for="profile_confirm_password">Confirm New Password</label>
                  <input type="password" class="form-control" id="profile_confirm_password" name="confirm_password" minlength="6" autocomplete="new-password">
                </div>
              </div>

              <div class="d-flex flex-wrap gap-2 mt-2">
                <button type="submit" class="btn btn-primary" style="background:#c0392b;border:none;">Save Changes</button>
                <button type="reset" class="btn btn-outline-secondary">Reset</button>
              </div>
            </form>
            <p class="text-muted small mt-4 mb-0">Checkout uses your account details automatically. Payment is arranged directly with the restaurant via WhatsApp or in person.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
window.SITE_BASE = '<?php echo SITE_WEB_PATH; ?>';

async function loadAccountOrders() {
  const loading = document.getElementById('ordersLoading');
  const container = document.getElementById('ordersList');

  try {
    const response = await fetch(window.SITE_BASE + '/services/orders/my_orders.php');
    const data = await response.json();
    loading.style.display = 'none';

    if (!data.success || !data.orders.length) {
      container.innerHTML = '<div class="alert alert-light border">No orders yet. <a href="' + window.SITE_BASE + '/#menu">Browse the menu</a> to place your first order.</div>';
      return;
    }

    container.innerHTML = data.orders.map(order => {
      const items = order.items.map(item =>
        `<li>${item.item_name} x${item.quantity} - Frw ${parseFloat(item.total_price).toFixed(2)}</li>`
      ).join('');

      return `
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
              <strong>#${order.order_number}</strong>
              <span class="badge bg-secondary">${order.order_status.replace('_', ' ')}</span>
            </div>
            <p class="mb-2 text-muted">${new Date(order.order_date).toLocaleString()} · ${order.order_type.replace('_', ' ')}</p>
            <ul class="mb-2">${items}</ul>
            <strong>Total: Frw ${parseFloat(order.total_amount).toFixed(2)}</strong>
          </div>
        </div>`;
    }).join('');
  } catch (error) {
    loading.textContent = 'Unable to load orders.';
  }
}

async function loadAccountFavorites() {
  const loading = document.getElementById('favoritesLoading');
  const container = document.getElementById('favoritesList');

  try {
    const response = await fetch(window.SITE_BASE + '/services/favorites/list.php');
    const data = await response.json();
    loading.style.display = 'none';

    if (!data.success || !data.favorites.length) {
      container.innerHTML = '<div class="col-12"><div class="alert alert-light border">No favorites yet. Tap the heart on menu items to save them.</div></div>';
      return;
    }

    container.innerHTML = data.favorites.map(function (item) {
      const imagePath = item.image_url.startsWith('http') ? item.image_url : window.SITE_BASE + '/' + item.image_url.replace(/^\//, '');
      return `
        <div class="col-md-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex gap-3">
              <img src="${imagePath}" alt="${item.item_name}" style="width:90px;height:90px;object-fit:cover;border-radius:50%;">
              <div class="flex-grow-1">
                <h5 class="mb-1">${item.item_name}</h5>
                <p class="text-muted small mb-2">${item.description || item.category_name}</p>
                <strong class="d-block mb-3">Frw ${parseFloat(item.price).toFixed(2)}</strong>
                <a href="${window.SITE_BASE}/#menu" class="btn btn-sm btn-primary" style="background:#c0392b;border:none;">Order from Menu</a>
              </div>
            </div>
          </div>
        </div>`;
    }).join('');
  } catch (error) {
    loading.textContent = 'Unable to load favorites.';
  }
}

loadAccountOrders();
document.getElementById('favorites-tab').addEventListener('shown.bs.tab', loadAccountFavorites, { once: true });

document.getElementById('customerProfileForm').addEventListener('submit', async function (event) {
  event.preventDefault();
  const alertBox = document.getElementById('profileAlert');
  const submitBtn = this.querySelector('button[type="submit"]');
  const formData = new FormData(this);

  submitBtn.disabled = true;
  alertBox.className = 'alert d-none';

  try {
    const response = await fetch(window.SITE_BASE + '/services/auth/update_profile.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    });
    const data = await response.json();

    if (data.success) {
      alertBox.className = 'alert alert-success';
      alertBox.textContent = data.message;
      alertBox.classList.remove('d-none');

      if (data.customer) {
        const welcomeText = document.querySelector('.text-muted.mb-0');
        if (welcomeText) {
          welcomeText.textContent = 'Welcome back, ' + data.customer.name;
        }

        document.getElementById('profile_first_name').value = data.customer.first_name || '';
        document.getElementById('profile_last_name').value = data.customer.last_name || '';
        document.getElementById('profile_email').value = data.customer.email || '';
        document.getElementById('profile_phone').value = data.customer.phone || '';
        document.getElementById('profile_current_password').value = '';
        document.getElementById('profile_new_password').value = '';
        document.getElementById('profile_confirm_password').value = '';

        if (window.customerAccount) {
          window.customerAccount.customer = data.customer;
          window.customerAccount.logged_in = true;
        }
        if (window.applyCheckoutAccountState) {
          window.applyCheckoutAccountState();
        }
      }
    } else {
      alertBox.className = 'alert alert-danger';
      alertBox.textContent = data.message || 'Unable to update profile.';
      alertBox.classList.remove('d-none');
    }
  } catch (error) {
    alertBox.className = 'alert alert-danger';
    alertBox.textContent = 'An error occurred. Please try again.';
    alertBox.classList.remove('d-none');
  } finally {
    submitBtn.disabled = false;
  }
});
</script>
