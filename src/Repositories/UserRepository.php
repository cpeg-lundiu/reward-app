<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'parent'");
        $stmt->execute([$email]);

        return $stmt->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'child'");
        $stmt->execute([$username]);

        return $stmt->fetch() ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([$email]);

        return (bool) $stmt->fetchColumn();
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE username = ?');
        $stmt->execute([$username]);

        return (bool) $stmt->fetchColumn();
    }

    /** @return array<int, array> All children belonging to a parent. */
    public function childrenOf(int $parentId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE parent_id = ? AND role = \'child\' ORDER BY display_name');
        $stmt->execute([$parentId]);

        return $stmt->fetchAll();
    }

    /** Loads a child only if it belongs to the given parent (ownership guard). */
    public function childOfParent(int $parentId, int $childId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? AND parent_id = ? AND role = 'child'");
        $stmt->execute([$childId, $parentId]);

        return $stmt->fetch() ?: null;
    }

    public function createParent(string $email, string $passwordHash, string $displayName, string $currency): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (role, email, password_hash, display_name, currency)
             VALUES ('parent', ?, ?, ?, ?)"
        );
        $stmt->execute([$email, $passwordHash, $displayName, $currency]);

        return (int) $this->pdo->lastInsertId();
    }

    public function createChild(
        int $parentId,
        string $username,
        string $passwordHash,
        string $displayName,
        string $avatarEmoji
    ): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (role, parent_id, username, password_hash, display_name, must_change_password, avatar_emoji)
             VALUES ('child', ?, ?, ?, ?, 1, ?)"
        );
        $stmt->execute([$parentId, $username, $passwordHash, $displayName, $avatarEmoji]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updatePassword(int $userId, string $passwordHash, bool $clearMustChange = false): void
    {
        $sql = 'UPDATE users SET password_hash = ?' . ($clearMustChange ? ', must_change_password = 0' : '') . ' WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$passwordHash, $userId]);
    }

    public function updateCurrency(int $parentId, string $currency): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET currency = ? WHERE id = ?');
        $stmt->execute([$currency, $parentId]);
    }

    /** Applies a signed delta to a child's balance. Caller owns the transaction. */
    public function adjustBalance(int $childId, int $deltaCents): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET balance_cents = balance_cents + ? WHERE id = ?');
        $stmt->execute([$deltaCents, $childId]);
    }

    /** Multiplies a child's balance by a rate (currency conversion). Caller owns the transaction. */
    public function setBalance(int $childId, int $cents): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET balance_cents = ? WHERE id = ?');
        $stmt->execute([$cents, $childId]);
    }

    public function adjustStars(int $childId, int $delta): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET stars = stars + ? WHERE id = ?');
        $stmt->execute([$delta, $childId]);
    }
}
