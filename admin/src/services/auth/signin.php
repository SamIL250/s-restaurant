<?php
session_start();
include '../../../config/config.php';
require '../../../vendor/autoload.php';

use Firebase\JWT\JWT;

function errorRedirectWithMessage($message): void
{
    $_SESSION['notification'] = $message;
    header('location:../../../auth/signin');
    exit();
}
function redirectWithMessage($message) {
    $_SESSION['notification'] = $message;
    header('location:../../../index');
    exit();
}

$email = mysqli_real_escape_string($conn, $_POST['email']);
$password = mysqli_real_escape_string($conn, $_POST['password']);

if (empty($email) || empty($password)) {
    errorRedirectWithMessage("All fields are required!");
}

// validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    errorRedirectWithMessage("Invalid email format");
}

//check user email - Updated to match restaurant database schema
$user = mysqli_query(
    $conn,
    "SELECT * FROM users WHERE email = '$email' AND is_active = 1"
);

if (mysqli_num_rows($user) == 0) {
    errorRedirectWithMessage("Incorrect email or password, Try again!");
}

$user_data = mysqli_fetch_assoc($user);

// Verify password - In production, you should use password_verify() with hashed passwords
// For now, we'll check against password_hash field directly (you should implement proper hashing)
if ($password !== $user_data['password_hash']) {
    errorRedirectWithMessage("Incorrect email or password, Try again!");
}

$user_id = $user_data['user_id'];
$user_email = $user_data['email'];
$user_role = $user_data['role'];
$user_name = $user_data['first_name'] . ' ' . $user_data['last_name'];

// Restaurant JWT token
$key = "tacos_restaurant_secret_key_2024_secure_jwt_token";
$token = JWT::encode(
    array(
        'iat' => time(),
        'nbf' => time(),
        'exp' => time() + (24 * 60 * 60), // 1 day
        'user' => array(
            'user_id' => $user_id,
            'email' => $user_email,
            'role' => $user_role,
            'name' => $user_name
        )
    ),
    $key,
    'HS256'
);

//store jwt in cookies
$set_cookie = setcookie("restaurant_token", $token, time() + 3600, "/", "", false, true);

if($set_cookie) {
    redirectWithMessage("Welcome back, " . $user_name . "!");
} else {
    errorRedirectWithMessage("Failed to sign in, Try again later");
}