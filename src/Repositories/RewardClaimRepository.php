<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class RewardClaimRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(int $rewardId, int $childId, int $starCost): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO reward_claims (reward_id, child_id, star_cost, status) VALUES (?, ?, ?, 'pending')"
        );
        $stmt->execute([$rewardId, $childId, $starCost]);

        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM reward_claims WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** Claim history for a child (with reward details). */
    public function forChild(int $childId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, r.title, r.emoji
             FROM reward_claims c JOIN rewards r ON r.id = c.reward_id
             WHERE c.child_id = ? ORDER BY c.claimed_at DESC'
        );
        $stmt->execute([$childId]);

        return $stmt->fetchAll();
    }

    /** Pending claims awaiting a parent's action. */
    public function pendingForParent(int $parentId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, r.title, r.emoji, u.display_name AS child_name, u.avatar_emoji
             FROM reward_claims c
             JOIN rewards r ON r.id = c.reward_id
             JOIN users u ON u.id = c.child_id
             WHERE r.parent_id = ? AND c.status = 'pending'
             ORDER BY c.claimed_at ASC"
        );
        $stmt->execute([$parentId]);

        return $stmt->fetchAll();
    }

    public function countPendingForParent(int $parentId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM reward_claims c
             JOIN rewards r ON r.id = c.reward_id
             WHERE r.parent_id = ? AND c.status = 'pending'"
        );
        $stmt->execute([$parentId]);

        return (int) $stmt->fetchColumn();
    }

    public function updateStatus(int $id, string $status): void
    {
        $completedAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;
        $stmt = $this->pdo->prepare('UPDATE reward_claims SET status = ?, completed_at = ? WHERE id = ?');
        $stmt->execute([$status, $completedAt, $id]);
    }
}
