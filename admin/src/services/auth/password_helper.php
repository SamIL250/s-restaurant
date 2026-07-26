<?php

function hashUserPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a plain-text password against a stored bcrypt/argon hash or legacy plain value.
 */
function verifyUserPassword(string $password, ?string $storedPassword): bool
{
    if ($storedPassword === null || $storedPassword === '') {
        return false;
    }

    $storedPassword = trim($storedPassword);

    if (isPasswordHash($storedPassword)) {
        return password_verify($password, $storedPassword);
    }

    return hash_equals($storedPassword, $password);
}

function isPasswordHash(string $storedPassword): bool
{
    return str_starts_with($storedPassword, '$2y$')
        || str_starts_with($storedPassword, '$2a$')
        || str_starts_with($storedPassword, '$2b$')
        || str_starts_with($storedPassword, '$argon2');
}

function passwordNeedsRehash(string $storedPassword): bool
{
    if ($storedPassword === '') {
        return true;
    }

    if (!isPasswordHash($storedPassword)) {
        return true;
    }

    return password_needs_rehash($storedPassword, PASSWORD_DEFAULT);
}

function upgradeUserPasswordIfNeeded(mysqli $conn, int $userId, string $plainPassword, string $storedPassword): void
{
    if (!passwordNeedsRehash($storedPassword)) {
        return;
    }

    $hashed = hashUserPassword($plainPassword);
    $stmt = $conn->prepare('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND deleted_at IS NULL');
    $stmt->bind_param('si', $hashed, $userId);
    $stmt->execute();
    $stmt->close();
}

?>
