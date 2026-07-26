<?php
require_once '../../../config/config.php';
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Log all POST data
error_log("Add Staff POST Data: " . print_r($_POST, true));

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
    setErrorMessage('Only administrators can add staff members.');
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$role = trim($_POST['role'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($role)) {
    setErrorMessage('All fields are required.');
}

// Validate role - must match database ENUM values
$valid_roles = ['admin', 'cashier', 'stock_clerk'];
if (!in_array($role, $valid_roles)) {
    setErrorMessage('Invalid role selected. Allowed roles: admin, cashier, stock_clerk');
}

// Check for duplicate username
$stmt = $conn->prepare('SELECT user_id FROM users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('Username already exists.');
}
$stmt->close();

// Check for duplicate email
$stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('Email address already exists.');
}
$stmt->close();

// Store password as plain text (no hashing)
$password_plain = $password;

// Debug: Log before insert
error_log("About to insert staff: username='$username', email='$email', role='$role'");

$stmt = $conn->prepare('INSERT INTO users (username, email, password_hash, first_name, last_name, role, phone) VALUES (?, ?, ?, ?, ?, ?, ?)');
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    setErrorMessage('Database prepare error.');
}

$stmt->bind_param('sssssss', $username, $email, $password_plain, $first_name, $last_name, $role, $phone);
if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    $stmt->close();
    setErrorMessage('Failed to add staff member: ' . $stmt->error);
}
$stmt->close();
setSuccessMessage('Staff member added successfully.'); 