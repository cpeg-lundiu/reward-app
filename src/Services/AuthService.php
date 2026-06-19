<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Support\Money;
use App\Support\PasswordPolicy;
use App\Support\ValidationException;

final class AuthService
{
    public function __construct(private UserRepository $users)
    {
    }

    /**
     * Registers a parent account. Returns the new user id.
     *
     * @throws ValidationException
     */
    public function registerParent(string $email, string $displayName, string $password, string $currency): int
    {
        $errors = [];
        $email = trim(strtolower($email));
        $displayName = trim($displayName);
        $currency = strtoupper(trim($currency));

        if ($displayName === '') {
            $errors[] = 'Please enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif ($this->users->emailExists($email)) {
            $errors[] = 'That email is already registered.';
        }
        if (!Money::isSupported($currency)) {
            $errors[] = 'Please choose a supported currency.';
        }
        foreach (PasswordPolicy::validate($password) as $rule) {
            $errors[] = 'Password must ' . $rule . '.';
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        return $this->users->createParent(
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $displayName,
            $currency
        );
    }

    /** Verifies a parent's email + password. Returns the user row or null. */
    public function attemptParentLogin(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail(trim(strtolower($email)));
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return null;
    }

    /** Verifies a child's username + password. Returns the user row or null. */
    public function attemptChildLogin(string $username, string $password): ?array
    {
        $user = $this->users->findByUsername(trim($username));
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return null;
    }

    /**
     * A child sets their own password on first login (or any later change).
     *
     * @throws ValidationException
     */
    public function setChildPassword(int $childId, string $password, string $confirm): void
    {
        $errors = [];
        if ($password !== $confirm) {
            $errors[] = 'The two passwords do not match.';
        }
        foreach (PasswordPolicy::validate($password) as $rule) {
            $errors[] = 'Password must ' . $rule . '.';
        }
        if ($errors) {
            throw new ValidationException($errors);
        }

        $this->users->updatePassword($childId, password_hash($password, PASSWORD_DEFAULT), true);
    }
}
