<?php
session_start();

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/password_helper.php';
require_once __DIR__ . '/../../../config/roles.php';

use Firebase\JWT\JWT;

function errorRedirectWithMessage(string $message): void
{
    $_SESSION['notification'] = $message;
    header('Location: ' . ADMIN_WEB_PATH . '/auth/signin');
    exit();
}

function redirectWithMessage(string $message, string $role = ROLE_ADMIN): void
{
    $_SESSION['notification'] = $message;
    header('Location: ' . ADMIN_WEB_PATH . '/' . role_default_landing_page($role));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorRedirectWithMessage('Invalid request method.');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    errorRedirectWithMessage('All fields are required!');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    errorRedirectWithMessage('Invalid email format');
}

$stmt = $conn->prepare('SELECT user_id, email, password_hash, first_name, last_name, role, is_active FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

if (!$user_data) {
    errorRedirectWithMessage('Incorrect email or password, Try again!');
}

if (!(int) $user_data['is_active']) {
    errorRedirectWithMessage('This account is inactive. Please contact your administrator.');
}

if (!verifyUserPassword($password, $user_data['password_hash'])) {
    errorRedirectWithMessage('Incorrect email or password, Try again!');
}

upgradeUserPasswordIfNeeded($conn, (int) $user_data['user_id'], $password, $user_data['password_hash']);

$user_id = (int) $user_data['user_id'];
$user_email = $user_data['email'];
$user_role = $user_data['role'];
$user_name = trim($user_data['first_name'] . ' ' . $user_data['last_name']);

$key = 'tacos_restaurant_secret_key_2024_secure_jwt_token';
$token = JWT::encode(
    [
        'iat' => time(),
        'nbf' => time(),
        'exp' => time() + (24 * 60 * 60),
        'user' => [
            'user_id' => $user_id,
            'email' => $user_email,
            'role' => $user_role,
            'name' => $user_name,
        ],
    ],
    $key,
    'HS256'
);

$cookiePath = ADMIN_WEB_PATH . '/';
$set_cookie = setcookie('restaurant_token', $token, [
    'expires' => time() + (24 * 60 * 60),
    'path' => $cookiePath,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if ($set_cookie) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $user_email;
    $_SESSION['user_role'] = $user_role;
    $_SESSION['user_name'] = $user_name;
    redirectWithMessage('Welcome back, ' . $user_name . '!', $user_role);
}

errorRedirectWithMessage('Failed to sign in, Try again later');
