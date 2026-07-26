<?php
require_once __DIR__ . '/../auth/service_guard.php';
requireServiceRoles([ROLE_ADMIN]);

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

if (!is_valid_role($role)) {
    setErrorMessage('Invalid role selected. Allowed roles: admin, cashier, stock_clerk');
}

if (strlen($password) < 6) {
    setErrorMessage('Password must be at least 6 characters.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setErrorMessage('Invalid email format.');
}

$stmt = $conn->prepare('SELECT user_id FROM users WHERE username = ? AND deleted_at IS NULL');
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('Username already exists.');
}
$stmt->close();

$stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? AND deleted_at IS NULL');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('Email address already exists.');
}
$stmt->close();

$password_hash = hashUserPassword($password);

$stmt = $conn->prepare('INSERT INTO users (username, email, password_hash, first_name, last_name, role, phone) VALUES (?, ?, ?, ?, ?, ?, ?)');
if (!$stmt) {
    setErrorMessage('Database prepare error.');
}

$stmt->bind_param('sssssss', $username, $email, $password_hash, $first_name, $last_name, $role, $phone);
if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to add staff member: ' . $stmt->error);
}
$stmt->close();
setSuccessMessage('Staff member added successfully.');
