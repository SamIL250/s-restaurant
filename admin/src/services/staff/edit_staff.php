<?php
require_once '../../../config/config.php';
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../staff');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../staff');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    setErrorMessage('Only administrators can update staff members.');
}

// Debug: Log all POST data
error_log("Staff Update POST Data: " . print_r($_POST, true));

$staff_id = intval($_POST['user_id'] ?? 0);
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$role = trim($_POST['role'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Debug: Log extracted values and validation checks
error_log("Staff validation - staff_id: $staff_id (<=0: " . ($staff_id <= 0 ? 'true' : 'false') . ")");
error_log("Staff validation - first_name: '$first_name' (empty: " . (empty($first_name) ? 'true' : 'false') . ")");
error_log("Staff validation - last_name: '$last_name' (empty: " . (empty($last_name) ? 'true' : 'false') . ")");
error_log("Staff validation - username: '$username' (empty: " . (empty($username) ? 'true' : 'false') . ")");
error_log("Staff validation - email: '$email' (empty: " . (empty($email) ? 'true' : 'false') . ")");
error_log("Staff validation - role: '$role' (empty: " . (empty($role) ? 'true' : 'false') . ")");

if ($staff_id <= 0) {
    error_log("VALIDATION FAILED: staff_id <= 0");
    setErrorMessage('Staff ID is required.');
}

if (empty($first_name)) {
    error_log("VALIDATION FAILED: first_name is empty");
    setErrorMessage('First name is required.');
}

if (empty($last_name)) {
    error_log("VALIDATION FAILED: last_name is empty");
    setErrorMessage('Last name is required.');
}

if (empty($username)) {
    error_log("VALIDATION FAILED: username is empty");
    setErrorMessage('Username is required.');
}

if (empty($email)) {
    error_log("VALIDATION FAILED: email is empty");
    setErrorMessage('Email is required.');
}

if (empty($role)) {
    error_log("VALIDATION FAILED: role is empty");
    setErrorMessage('Role is required.');
}

if (!in_array($role, ['admin', 'cashier', 'stock_clerk'])) {
    error_log("VALIDATION FAILED: invalid role '$role'");
    setErrorMessage('Invalid role selected.');
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

// Handle password change if provided (stored as plain text)
$password_update = '';
$password_params = [];

if (!empty($_POST['password'])) {  // Changed from new_password to password to match form
    $new_password = $_POST['password'] ?? '';

    // Validate password
    if (strlen($new_password) < 6) {
        setErrorMessage('New password must be at least 6 characters.');
    }

    // Store password as plain text (no hashing)
    $password_update = ', password_hash = ?';
    $password_params[] = $new_password;  // Store plain text password
}

// Update staff member
$update_query = 'UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, phone = ?, role = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP' . $password_update . ' WHERE user_id = ? AND deleted_at IS NULL';

// Debug: Log the query and parameters
error_log("Update query: " . $update_query);

$params = [$first_name, $last_name, $username, $email, $phone, $role, $is_active];
$param_types = 'ssssssi';

// Add password to parameters if provided
if (!empty($password_params)) {
    $params = array_merge($params, $password_params);
    $param_types .= 's';  // Add string type for password
}

$params[] = $staff_id;
$param_types .= 'i';

// Debug: Log all parameters
error_log("Update parameters: " . print_r($params, true));
error_log("Parameter types: " . $param_types);

$stmt = $conn->prepare($update_query);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    setErrorMessage('Database prepare error: ' . $conn->error);
}

$bind_result = $stmt->bind_param($param_types, ...$params);
if (!$bind_result) {
    error_log("Bind failed: " . $stmt->error);
    $stmt->close();
    setErrorMessage('Database bind error: ' . $stmt->error);
}

if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    $stmt->close();
    setErrorMessage('Failed to update staff member: ' . $stmt->error);
}

$affected_rows = $stmt->affected_rows;
$stmt->close();

if ($affected_rows === 0) {
    setErrorMessage('No changes were made or staff member not found.');
}

setSuccessMessage('Staff member updated successfully.');
