<?php

function hashUserPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyUserPassword(string $password, ?string $storedPassword): bool
{
    if ($storedPassword === null || $storedPassword === '') {
        return false;
    }

    $storedPassword = trim($storedPassword);

    if (str_starts_with($storedPassword, '$2y$')
        || str_starts_with($storedPassword, '$2a$')
        || str_starts_with($storedPassword, '$2b$')
        || str_starts_with($storedPassword, '$argon2')) {
        return password_verify($password, $storedPassword);
    }

    return hash_equals($storedPassword, $password);
}

?>
