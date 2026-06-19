<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TaskRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks
                (parent_id, child_id, title, description, stars, frequency, specific_date, weekday, day_of_month, start_date, end_date)
             VALUES (:parent_id, :child_id, :title, :description, :stars, :frequency, :specific_date, :weekday, :day_of_month, :start_date, :end_date)'
        );
        $stmt->execute([
            ':parent_id' => $data['parent_id'],
            ':child_id' => $data['child_id'],
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':stars' => $data['stars'],
            ':frequency' => $data['frequency'],
            ':specific_date' => $data['specific_date'],
            ':weekday' => $data['weekday'],
            ':day_of_month' => $data['day_of_month'],
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tasks WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** All tasks created by a parent, with the assigned child's name. */
    public function forParent(int $parentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.*, u.display_name AS child_name, u.avatar_emoji
             FROM tasks t JOIN users u ON u.id = t.child_id
             WHERE t.parent_id = ? ORDER BY t.active DESC, t.created_at DESC'
        );
        $stmt->execute([$parentId]);

        return $stmt->fetchAll();
    }

    /** Active tasks assigned to a child (used to build calendar occurrences). */
    public function activeForChild(int $childId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tasks WHERE child_id = ? AND active = 1');
        $stmt->execute([$childId]);

        return $stmt->fetchAll();
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE tasks SET active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
    }
}
