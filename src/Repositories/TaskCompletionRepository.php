<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TaskCompletionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM task_completions WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function findByTaskAndDate(int $taskId, string $dueDate): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM task_completions WHERE task_id = ? AND due_date = ?');
        $stmt->execute([$taskId, $dueDate]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Completion rows for a child within a date range, keyed "taskId|dueDate"
     * so the occurrence engine can attach status to each generated occurrence.
     *
     * @return array<string, array>
     */
    public function mapForChildInRange(int $childId, string $from, string $to): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM task_completions WHERE child_id = ? AND due_date BETWEEN ? AND ?'
        );
        $stmt->execute([$childId, $from, $to]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['task_id'] . '|' . $row['due_date']] = $row;
        }

        return $map;
    }

    /** Child marks an occurrence complete (idempotent on task+date). */
    public function markCompleted(int $taskId, int $childId, string $dueDate): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO task_completions (task_id, child_id, due_date, status, completed_at)
             VALUES (?, ?, ?, 'completed', ?)
             ON DUPLICATE KEY UPDATE status = 'completed', completed_at = VALUES(completed_at)"
        );
        $stmt->execute([$taskId, $childId, $dueDate, date('Y-m-d H:i:s')]);
    }

    /** Pending (child-completed, not yet approved) occurrences for a parent. */
    public function pendingForParent(int $parentId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, t.title, t.stars, u.display_name AS child_name, u.avatar_emoji
             FROM task_completions c
             JOIN tasks t ON t.id = c.task_id
             JOIN users u ON u.id = c.child_id
             WHERE t.parent_id = ? AND c.status = 'completed'
             ORDER BY c.completed_at ASC"
        );
        $stmt->execute([$parentId]);

        return $stmt->fetchAll();
    }

    public function countPendingForParent(int $parentId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM task_completions c
             JOIN tasks t ON t.id = c.task_id
             WHERE t.parent_id = ? AND c.status = 'completed'"
        );
        $stmt->execute([$parentId]);

        return (int) $stmt->fetchColumn();
    }

    public function approve(int $id, int $starsAwarded): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE task_completions SET status = 'approved', stars_awarded = ?, approved_at = ? WHERE id = ?"
        );
        $stmt->execute([$starsAwarded, date('Y-m-d H:i:s'), $id]);
    }

    public function reject(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE task_completions SET status = 'rejected', approved_at = ? WHERE id = ?");
        $stmt->execute([date('Y-m-d H:i:s'), $id]);
    }
}
