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

$staff_id = intval($_POST['user_id'] ?? 0);
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$role = trim($_POST['role'] ?? '');

if ($staff_id <= 0) {
    setErrorMessage('Staff ID is required.');
}

if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($role)) {
    setErrorMessage('All required fields must be filled.');
}

if (!is_valid_role($role)) {
    setErrorMessage('Invalid role selected.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setErrorMessage('Invalid email format.');
}

$stmt = $conn->prepare('SELECT user_id FROM users WHERE username = ? AND user_id != ? AND deleted_at IS NULL');
$stmt->bind_param('si', $username, $staff_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('Username already exists.');
}
$stmt->close();

$stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ? AND deleted_at IS NULL');
$stmt->bind_param('si', $email, $staff_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('Email address already exists.');
}
$stmt->close();

$password_update = '';
$password_params = [];

if (!empty($_POST['password'])) {
    $new_password = $_POST['password'];

    if (strlen($new_password) < 6) {
        setErrorMessage('New password must be at least 6 characters.');
    }

    $password_update = ', password_hash = ?';
    $password_params[] = hashUserPassword($new_password);
}

$update_query = 'UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, phone = ?, role = ?, updated_at = CURRENT_TIMESTAMP' . $password_update . ' WHERE user_id = ? AND deleted_at IS NULL';

$params = [$first_name, $last_name, $username, $email, $phone, $role];
$param_types = 'ssssss';

if (!empty($password_params)) {
    $params = array_merge($params, $password_params);
    $param_types .= 's';
}

$params[] = $staff_id;
$param_types .= 'i';

$stmt = $conn->prepare($update_query);
if (!$stmt) {
    setErrorMessage('Database prepare error: ' . $conn->error);
}

$stmt->bind_param($param_types, ...$params);

if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to update staff member: ' . $stmt->error);
}

$affected_rows = $stmt->affected_rows;
$stmt->close();

if ($affected_rows === 0) {
    setErrorMessage('No changes were made or staff member not found.');
}

setSuccessMessage('Staff member updated successfully.');
