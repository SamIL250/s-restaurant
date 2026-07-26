<?php
include  './vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function checkAuthState() {
    if (!isset($_COOKIE['restaurant_token'])) {
        header('location:./auth/signin');
        exit();
    }

    $token = $_COOKIE['restaurant_token'];
    $key = "tacos_restaurant_secret_key_2024_secure_jwt_token";

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        
        // Check if token is expired
        if (time() > $decoded->exp) {
            setcookie("restaurant_token", "", time() - 3600, "/");
            header('location:../../../auth/signin');
            exit();
        }

        // Store user data in session for easy access
        $_SESSION['user_id'] = $decoded->user->user_id;
        $_SESSION['user_email'] = $decoded->user->email;
        $_SESSION['user_role'] = $decoded->user->role;
        $_SESSION['user_name'] = $decoded->user->name;
        
        return $decoded->user;
    } catch (Exception $e) {
        // Invalid token
        setcookie("restaurant_token", "", time() - 3600, "/");
        header('location:../../../auth/signin');
        exit();
    }
}

function requireRole($required_roles) {
    $user = checkAuthState();
    
    if (is_array($required_roles)) {
        if (!in_array($user->role, $required_roles)) {
            header('location:../../../index');
            exit();
        }
    } else {
        if ($user->role !== $required_roles) {
            header('location:../../../index');
            exit();
        }
    }
    
    return $user;
}

// Check authentication on every page load
$current_user = checkAuthState();
?>