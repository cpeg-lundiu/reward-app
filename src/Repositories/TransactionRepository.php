<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TransactionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(
        int $childId,
        int $amountCents,
        string $type,
        string $status,
        string $currency,
        ?string $note = null
    ): int {
        $resolvedAt = $status === 'pending' ? null : date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO transactions (child_id, amount_cents, type, status, currency, note, resolved_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$childId, $amountCents, $type, $status, $currency, $note, $resolvedAt]);

        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM transactions WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** @return array<int, array> Recent ledger rows for a child. */
    public function forChild(int $childId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM transactions WHERE child_id = ? ORDER BY created_at DESC, id DESC LIMIT ?');
        $stmt->bindValue(1, $childId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** A page of a child's ledger rows, newest first. */
    public function forChildPaged(int $childId, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM transactions WHERE child_id = ? ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $childId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Sum of a child's still-pending withdrawals (positive number of cents). */
    public function pendingWithdrawCents(int $childId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(ABS(amount_cents)), 0) FROM transactions
             WHERE child_id = ? AND type = 'withdraw' AND status = 'pending'"
        );
        $stmt->execute([$childId]);

        return (int) $stmt->fetchColumn();
    }

    /** Pending withdrawals across all of a parent's children, with child names. */
    public function pendingWithdrawalsForParent(int $parentId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.*, u.display_name, u.avatar_emoji
             FROM transactions t
             JOIN users u ON u.id = t.child_id
             WHERE u.parent_id = ? AND t.type = 'withdraw' AND t.status = 'pending'
             ORDER BY t.created_at ASC"
        );
        $stmt->execute([$parentId]);

        return $stmt->fetchAll();
    }

    public function countPendingWithdrawalsForParent(int $parentId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM transactions t
             JOIN users u ON u.id = t.child_id
             WHERE u.parent_id = ? AND t.type = 'withdraw' AND t.status = 'pending'"
        );
        $stmt->execute([$parentId]);

        return (int) $stmt->fetchColumn();
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE transactions SET status = ?, resolved_at = ? WHERE id = ?');
        $stmt->execute([$status, date('Y-m-d H:i:s'), $id]);
    }

    /** Used during currency conversion to restate a pending withdrawal. */
    public function updateAmountAndCurrency(int $id, int $amountCents, string $currency): void
    {
        $stmt = $this->pdo->prepare('UPDATE transactions SET amount_cents = ?, currency = ? WHERE id = ?');
        $stmt->execute([$amountCents, $currency, $id]);
    }
}
