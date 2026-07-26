<?php

class CustomerSession
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(array $customer): void
    {
        self::start();
        $_SESSION['customer_id'] = (int) $customer['customer_id'];
        $_SESSION['customer_email'] = $customer['email'];
        $_SESSION['customer_name'] = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $_SESSION['customer_phone'] = $customer['phone'] ?? '';
    }

    public static function logout(): void
    {
        self::start();
        unset(
            $_SESSION['customer_id'],
            $_SESSION['customer_email'],
            $_SESSION['customer_name'],
            $_SESSION['customer_phone']
        );
    }

    public static function isLoggedIn(): bool
    {
        self::start();
        return !empty($_SESSION['customer_id']);
    }

    public static function user(): ?array
    {
        self::start();
        if (empty($_SESSION['customer_id'])) {
            return null;
        }

        return [
            'customer_id' => (int) $_SESSION['customer_id'],
            'email' => $_SESSION['customer_email'] ?? '',
            'name' => $_SESSION['customer_name'] ?? '',
            'phone' => $_SESSION['customer_phone'] ?? '',
        ];
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please sign in to continue.']);
            exit();
        }

        return $user;
    }
}

?>
