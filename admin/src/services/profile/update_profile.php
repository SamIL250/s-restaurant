<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../profile.php');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../profile.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

// Debug: Log all POST data
error_log("Profile Update POST Data: " . print_r($_POST, true));

$staff_id = intval($_POST['staff_id'] ?? 0);
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$role = trim($_POST['role'] ?? '');

// Debug: Log extracted values
error_log("Extracted values - staff_id: $staff_id, first_name: '$first_name', last_name: '$last_name'");

if ($staff_id <= 0 || empty($first_name) || empty($last_name)) {
    setErrorMessage('Staff ID, first name, and last name are required.');
}

if (empty($username)) {
    setErrorMessage('Username is required.');
}

if (empty($email)) {
    setErrorMessage('Email is required.');
}

// Check for duplicate username if provided (excluding self)
if (!empty($username)) {
    $stmt = $conn->prepare('SELECT user_id FROM users WHERE username = ? AND user_id != ? AND deleted_at IS NULL');
    $stmt->bind_param('si', $username, $staff_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        setErrorMessage('Username already exists.');
    }
    $stmt->close();
}

// Check for duplicate email if provided (excluding self)
if (!empty($email)) {
    $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ? AND deleted_at IS NULL');
    $stmt->bind_param('si', $email, $staff_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        setErrorMessage('Email address already exists.');
    }
    $stmt->close();
}

// Handle password change if provided
$password_update = '';
$password_params = [];
$param_types = '';

if (!empty($_POST['new_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate password fields
    if (empty($current_password)) {
        setErrorMessage('Current password is required to change password.');
    }

    if ($new_password !== $confirm_password) {
        setErrorMessage('New passwords do not match.');
    }

    if (strlen($new_password) < 6) {
        setErrorMessage('New password must be at least 6 characters.');
    }

    // Verify current password
    $stmt = $conn->prepare('SELECT password_hash FROM users WHERE user_id = ? AND deleted_at IS NULL');
    $stmt->bind_param('i', $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        setErrorMessage('Staff member not found.');
    }
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($current_password !== $user['password_hash']) {
        setErrorMessage('Current password is incorrect.');
    }

    $password_update = ', password_hash = ?';
    $password_params[] = $new_password;
    $param_types .= 's';
}

// Update staff member
$update_query = 'UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP' . $password_update . ' WHERE user_id = ? AND deleted_at IS NULL';

$params = [$first_name, $last_name, $username, $email, $phone];
$param_types = 'sssss';

// Add password to parameters if provided
if (!empty($password_params)) {
    $params = array_merge($params, $password_params);
}

$params[] = $staff_id;
$param_types .= 'i';

$stmt = $conn->prepare($update_query);
$stmt->bind_param($param_types, ...$params);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to update profile. Please try again.');
}

$affected_rows = $stmt->affected_rows;
$stmt->close();

if ($affected_rows === 0) {
    setErrorMessage('No changes were made or profile not found.');
}

// Update session data
$_SESSION['user_name'] = $first_name . ' ' . $last_name;
$_SESSION['user_email'] = $email;

setSuccessMessage('Profile updated successfully.');
