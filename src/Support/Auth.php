<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Thin wrapper over $_SESSION for authentication state.
 * Only the user id + role live in the session; full user data is loaded
 * fresh from the database each request so balances/stars are never stale.
 */
final class Auth
{
    public static function login(int $userId, string $role): void
    {
        // Prevent session fixation on privilege change.
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = $role;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['role']);
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function isParent(): bool
    {
        return self::role() === 'parent';
    }

    public static function isChild(): bool
    {
        return self::role() === 'child';
    }
}
