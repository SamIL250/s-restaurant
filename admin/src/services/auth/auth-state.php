<?php
include __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/roles.php';
require_once __DIR__ . '/password_helper.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function decodeAuthToken(?string $token)
{
    if (empty($token)) {
        return null;
    }

    $key = "tacos_restaurant_secret_key_2024_secure_jwt_token";

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));

        if (time() > $decoded->exp) {
            return null;
        }

        return $decoded->user;
    } catch (Exception $e) {
        return null;
    }
}

function hydrateSessionFromUser(object $user): void
{
    $_SESSION['user_id'] = $user->user_id;
    $_SESSION['user_email'] = $user->email;
    $_SESSION['user_role'] = $user->role;
    $_SESSION['user_name'] = $user->name;
}

function clearAuthCookie(): void
{
    $path = defined('ADMIN_WEB_PATH') ? ADMIN_WEB_PATH . '/' : '/';
    setcookie('restaurant_token', '', time() - 3600, $path);
}

function redirectToSignIn(): void
{
    header('location:./auth/signin');
    exit();
}

function redirectToDashboard(): void
{
    header('location:./index');
    exit();
}

function checkAuthState()
{
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_role'])) {
        return (object) [
            'user_id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'],
            'name' => $_SESSION['user_name'] ?? '',
        ];
    }

    if (!isset($_COOKIE['restaurant_token'])) {
        redirectToSignIn();
    }

    $user = decodeAuthToken($_COOKIE['restaurant_token'] ?? null);
    if ($user === null) {
        clearAuthCookie();
        redirectToSignIn();
    }

    hydrateSessionFromUser($user);

    return $user;
}

function requireRole($required_roles)
{
    $user = checkAuthState();
    $roles = is_array($required_roles) ? $required_roles : [$required_roles];

    if (!in_array($user->role, $roles, true)) {
        redirectToDashboard();
    }

    return $user;
}

function enforcePageAccess(?string $page = null): void
{
    $user = checkAuthState();
    $page = $page ?? basename($_SERVER['PHP_SELF']);

    if (!role_can_access_page($user->role, $page)) {
        $landing = role_default_landing_page($user->role);
        $_SESSION['notification'] = 'You do not have permission to access that page.';
        header('Location: ./' . $landing);
        exit();
    }
}

function currentUserRole(): string
{
    return $_SESSION['user_role'] ?? ROLE_ADMIN;
}

?>
