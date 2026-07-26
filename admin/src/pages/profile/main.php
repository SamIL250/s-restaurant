<?php
// Get current user information from session
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['user_role'] ?? '';

// Debug: Check if user is logged in
if (!$user_id) {
    echo '<div class="alert alert-warning">Please log in to view your profile.</div>';
    return;
}

// Fetch user details
$user_query = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE user_id = $user_id AND deleted_at IS NULL
");

if (!$user_query) {
    echo '<div class="alert alert-danger">Database error: ' . mysqli_error($conn) . '</div>';
    return;
}

$user_data = mysqli_fetch_assoc($user_query);

if (!$user_data) {
    echo '<div class="alert alert-warning">User not found.</div>';
    return;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Profile Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="avatar avatar-xl">
                                <img class="rounded-circle" src="src/assets/img/icons/profile.png" alt="Profile">
                            </div>
                        </div>
                        <div class="col">
                            <h3 class="mb-1"><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h3>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars($user_data['email']); ?></p>
                            <div class="d-flex gap-2">
                                <span class="badge badge-soft-<?php echo $user_role === 'admin' ? 'primary' : ($user_role === 'cashier' ? 'info' : 'warning'); ?>">
                                    <?php echo ucfirst($user_role); ?>
                                </span>
                                <span class="badge badge-soft-success">
                                    <?php echo $user_data['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary" onclick="editProfile()">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Personal Information -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Username</label>
                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($user_data['username']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">First Name</label>
                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($user_data['first_name']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Last Name</label>
                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($user_data['last_name']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Email</label>
                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($user_data['email']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Phone</label>
                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($user_data['phone'] ?: 'Not provided'); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Role</label>
                        <p class="mb-0 fw-bold"><?php echo ucfirst($user_data['role']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Status</label>
                        <p class="mb-0 fw-bold"><?php echo $user_data['is_active'] ? 'Active' : 'Inactive'; ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Member Since</label>
                        <p class="mb-0 fw-bold"><?php echo date('M j, Y', strtotime($user_data['created_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Settings -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Account Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Account ID</label>
                        <p class="mb-0 fw-bold">#<?php echo str_pad($user_data['user_id'], 6, '0', STR_PAD_LEFT); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Last Updated</label>
                        <p class="mb-0 fw-bold"><?php echo date('M j, Y g:i A', strtotime($user_data['updated_at'])); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Account Status</label>
                        <p class="mb-0">
                            <?php if ($user_data['is_active']): ?>
                                <span class="badge bg-success-subtle text-success">Active Account</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary">Inactive Account</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Security</label>
                        <p class="mb-0">
                            <span class="badge bg-info-subtle text-info">
                                <i class="fas fa-lock me-1"></i>Password Protected
                            </span>
                        </p>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button class="btn btn-outline-primary" onclick="editProfile()">
                            <i class="fas fa-user-edit me-2"></i>Edit Personal Information
                        </button>
                        <button class="btn btn-outline-secondary" onclick="changePassword()">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="editProfileModalLabel">
                    <i class="fas fa-user-edit me-2"></i>Edit Profile
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editProfileForm">
                <input type="hidden" name="staff_id" value="<?php echo $user_id; ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="first_name" 
                                   value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="last_name" 
                                   value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-control" id="role" name="role" 
                                    <?php echo $user_role !== 'admin' ? 'disabled' : ''; ?>>
                                <option value="admin" <?php echo $user_data['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="cashier" <?php echo $user_data['role'] === 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                                <option value="stock_clerk" <?php echo $user_data['role'] === 'stock_clerk' ? 'selected' : ''; ?>>Stock Clerk</option>
                            </select>
                            <?php if ($user_role !== 'admin'): ?>
                                <small class="text-muted">Only admins can change roles</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Change Password</h6>
                    <p class="text-muted small mb-3">Leave blank if you don't want to change password</p>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editProfile() {
    const modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
    modal.show();
}

// Handle form submission
document.getElementById('editProfileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // Validate password fields if trying to change password
    if (data.new_password || data.current_password) {
        if (!data.current_password) {
            showToast('Please enter current password', 'warning');
            return;
        }
        if (data.new_password !== data.confirm_password) {
            showToast('New passwords do not match', 'warning');
            return;
        }
        if (data.new_password.length < 6) {
            showToast('Password must be at least 6 characters', 'warning');
            return;
        }
    }
    
    // Show loading state
    const saveBtn = document.getElementById('saveProfileBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
    saveBtn.disabled = true;
    
    // Send update request
    fetch('src/services/profile/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            // Success - the service will redirect, but we'll show success message
            showToast('Profile updated successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editProfileModal')).hide();
            // Reload page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showToast('Error updating profile: ' + error.message, 'danger');
    })
    .finally(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
});

// Show toast notification
function showToast(message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}
</script>
