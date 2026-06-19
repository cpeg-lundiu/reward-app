<?php

declare(strict_types=1);

namespace App\Support;

/**
 * One-shot flash messages stored in the session.
 * Types map to UI styling: 'success' | 'error' | 'info'.
 */
final class Flash
{
    public static function add(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function error(string $message): void
    {
        self::add('error', $message);
    }

    /** Returns all queued messages and clears the queue. */
    public static function pull(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return $messages;
    }
}
