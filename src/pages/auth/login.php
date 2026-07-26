<section class="section" style="padding-top: 140px; min-height: 80vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-5 col-md-7">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4 p-md-5">
            <h2 class="mb-2">Sign In</h2>
            <p class="text-muted mb-4">Access your orders, favorites, and faster checkout.</p>
            <div id="loginAlert" class="alert d-none" role="alert"></div>
            <form id="customerLoginForm">
              <div class="mb-3">
                <label class="form-label" for="login_email">Email</label>
                <input type="email" class="form-control" id="login_email" name="email" required>
              </div>
              <div class="mb-3">
                <label class="form-label" for="login_password">Password</label>
                <input type="password" class="form-control" id="login_password" name="password" minlength="6" required>
              </div>
              <button type="submit" class="btn btn-primary w-100" style="background:#c0392b;border:none;">Sign In</button>
            </form>
            <p class="text-center mt-4 mb-0">No account yet? <a href="<?php echo SITE_WEB_PATH; ?>/register">Create one</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
document.getElementById('customerLoginForm').addEventListener('submit', async function (event) {
  event.preventDefault();
  const alertBox = document.getElementById('loginAlert');
  const formData = new FormData(this);

  try {
    const response = await fetch('<?php echo SITE_WEB_PATH; ?>/services/auth/login.php', {
      method: 'POST',
      body: formData
    });
    const data = await response.json();

    if (data.success) {
      window.location.href = data.redirect || '<?php echo SITE_WEB_PATH; ?>/account';
      return;
    }

    alertBox.className = 'alert alert-danger';
    alertBox.textContent = data.message || 'Unable to sign in.';
    alertBox.classList.remove('d-none');
  } catch (error) {
    alertBox.className = 'alert alert-danger';
    alertBox.textContent = 'An error occurred. Please try again.';
    alertBox.classList.remove('d-none');
  }
});
</script>
