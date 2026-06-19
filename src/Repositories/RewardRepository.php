<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class RewardRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rewards (parent_id, child_id, title, description, star_cost, emoji)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['parent_id'],
            $data['child_id'],
            $data['title'],
            $data['description'],
            $data['star_cost'],
            $data['emoji'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rewards WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function forParent(int $parentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, u.display_name AS child_name
             FROM rewards r LEFT JOIN users u ON u.id = r.child_id
             WHERE r.parent_id = ? ORDER BY r.active DESC, r.created_at DESC'
        );
        $stmt->execute([$parentId]);

        return $stmt->fetchAll();
    }

    /** Active rewards a given child can see (theirs, or family-wide). */
    public function availableForChild(int $childId, int $parentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM rewards
             WHERE parent_id = ? AND active = 1 AND (child_id IS NULL OR child_id = ?)
             ORDER BY star_cost ASC'
        );
        $stmt->execute([$parentId, $childId]);

        return $stmt->fetchAll();
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE rewards SET active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
    }
}
