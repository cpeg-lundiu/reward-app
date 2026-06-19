<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Strong-password rules, enforced wherever a password is set
 * (parent registration, child first-login change).
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 8;

    /**
     * Returns a list of human-readable error messages.
     * An empty list means the password is acceptable.
     *
     * @return string[]
     */
    public static function validate(string $password): array
    {
        $errors = [];

        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = 'be at least ' . self::MIN_LENGTH . ' characters long';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'contain an uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'contain a lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'contain a number';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'contain a symbol (e.g. !@#$)';
        }

        return $errors;
    }

    public static function isValid(string $password): bool
    {
        return self::validate($password) === [];
    }

    public static function rulesText(): string
    {
        return 'Use at least ' . self::MIN_LENGTH
            . ' characters with an uppercase letter, a lowercase letter, a number, and a symbol.';
    }
}
