<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/roles.php';
require_once __DIR__ . '/password_helper.php';
require_once __DIR__ . '/auth-state.php';

function wantsJsonResponse(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

    return str_contains($accept, 'application/json')
        || strtolower($requestedWith) === 'xmlhttprequest'
        || str_contains($_SERVER['REQUEST_URI'] ?? '', '/src/services/');
}

function denyServiceAccess(int $statusCode, string $message): void
{
    if (wantsJsonResponse()) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit();
    }

    $_SESSION['notification'] = $message;
    header('Location: ../../../index');
    exit();
}

function ensureServiceSession(): object
{
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_role'])) {
        return (object) [
            'user_id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'],
            'name' => $_SESSION['user_name'] ?? '',
        ];
    }

    $user = decodeAuthToken($_COOKIE['restaurant_token'] ?? null);
    if ($user === null) {
        denyServiceAccess(401, 'Authentication required. Please sign in again.');
    }

    hydrateSessionFromUser($user);

    return $user;
}

function requireServiceRoles(array $roles): object
{
    $user = ensureServiceSession();

    if (!in_array($user->role, $roles, true)) {
        denyServiceAccess(403, 'You do not have permission to perform this action.');
    }

    return $user;
}

?>
