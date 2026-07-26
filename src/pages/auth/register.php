<section class="section" style="padding-top: 140px; min-height: 80vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4 p-md-5">
            <h2 class="mb-2">Create Account</h2>
            <p class="text-muted mb-4">Save favorites, track orders, and checkout faster.</p>
            <div id="registerAlert" class="alert d-none" role="alert"></div>
            <form id="customerRegisterForm">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label" for="first_name">First Name</label>
                  <input type="text" class="form-control" id="first_name" name="first_name" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label" for="last_name">Last Name</label>
                  <input type="text" class="form-control" id="last_name" name="last_name">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label" for="register_email">Email</label>
                <input type="email" class="form-control" id="register_email" name="email" required>
              </div>
              <div class="mb-3">
                <label class="form-label" for="register_phone">Phone</label>
                <input type="tel" class="form-control" id="register_phone" name="phone" placeholder="0788123456" required>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label" for="register_password">Password</label>
                  <input type="password" class="form-control" id="register_password" name="password" minlength="6" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label" for="confirm_password">Confirm Password</label>
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100" style="background:#c0392b;border:none;">Create Account</button>
            </form>
            <p class="text-center mt-4 mb-0">Already have an account? <a href="<?php echo SITE_WEB_PATH; ?>/login">Sign in</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
document.getElementById('customerRegisterForm').addEventListener('submit', async function (event) {
  event.preventDefault();
  const alertBox = document.getElementById('registerAlert');
  const formData = new FormData(this);

  try {
    const response = await fetch('<?php echo SITE_WEB_PATH; ?>/services/auth/register.php', {
      method: 'POST',
      body: formData
    });
    const data = await response.json();

    if (data.success) {
      window.location.href = data.redirect || '<?php echo SITE_WEB_PATH; ?>/account';
      return;
    }

    alertBox.className = 'alert alert-danger';
    alertBox.textContent = data.message || 'Unable to create account.';
    alertBox.classList.remove('d-none');
  } catch (error) {
    alertBox.className = 'alert alert-danger';
    alertBox.textContent = 'An error occurred. Please try again.';
    alertBox.classList.remove('d-none');
  }
});
</script>
