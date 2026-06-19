<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TaskCompletionRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use App\Support\ValidationException;
use DateTimeImmutable;
use PDO;
use Throwable;

/**
 * Task definitions + the on-the-fly occurrence engine that turns recurring
 * rules into dated calendar items, plus completion/approval (which awards stars).
 */
final class TaskService
{
    public function __construct(
        private PDO $pdo,
        private TaskRepository $tasks,
        private TaskCompletionRepository $completions,
        private UserRepository $users
    ) {
    }

    /** Parent creates a task assigned to one of their children. */
    public function createTask(int $parentId, array $input): int
    {
        $errors = [];
        $childId = (int) ($input['child_id'] ?? 0);
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? '')) ?: null;
        $stars = (int) ($input['stars'] ?? 0);
        $frequency = (string) ($input['frequency'] ?? '');

        if (!$this->users->childOfParent($parentId, $childId)) {
            $errors[] = 'Please choose one of your children.';
        }
        if ($title === '') {
            $errors[] = 'Please enter a task title.';
        }
        if ($stars < 1) {
            $errors[] = 'Stars must be at least 1.';
        }
        if (!in_array($frequency, ['once', 'daily', 'weekly', 'monthly'], true)) {
            $errors[] = 'Please choose how often the task repeats.';
        }

        $specificDate = null;
        $weekday = null;
        $dayOfMonth = null;
        $today = date('Y-m-d');
        $startDate = $today;
        $endDate = trim((string) ($input['end_date'] ?? '')) ?: null;

        if ($frequency === 'once') {
            $specificDate = trim((string) ($input['specific_date'] ?? ''));
            if (!$this->isValidDate($specificDate)) {
                $errors[] = 'Please choose a date for the one-time task.';
            } else {
                $startDate = $specificDate;
                $endDate = $specificDate;
            }
        } elseif ($frequency === 'weekly') {
            $weekday = (int) ($input['weekday'] ?? -1);
            if ($weekday < 0 || $weekday > 6) {
                $errors[] = 'Please choose a day of the week.';
            }
        } elseif ($frequency === 'monthly') {
            $dayOfMonth = (int) ($input['day_of_month'] ?? 0);
            if ($dayOfMonth < 1 || $dayOfMonth > 31) {
                $errors[] = 'Please choose a day of the month (1–31).';
            }
        }

        if ($endDate !== null && !$this->isValidDate($endDate)) {
            $errors[] = 'The end date is not valid.';
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        return $this->tasks->create([
            'parent_id' => $parentId,
            'child_id' => $childId,
            'title' => $title,
            'description' => $description,
            'stars' => $stars,
            'frequency' => $frequency,
            'specific_date' => $specificDate,
            'weekday' => $weekday,
            'day_of_month' => $dayOfMonth,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /** Does a task occur on a given date per its frequency + active range? */
    public function occursOn(array $task, DateTimeImmutable $date): bool
    {
        $ymd = $date->format('Y-m-d');
        if ($ymd < $task['start_date']) {
            return false;
        }
        if (!empty($task['end_date']) && $ymd > $task['end_date']) {
            return false;
        }

        switch ($task['frequency']) {
            case 'once':
                return $ymd === $task['specific_date'];
            case 'daily':
                return true;
            case 'weekly':
                return (int) $date->format('w') === (int) $task['weekday'];
            case 'monthly':
                $dom = (int) $task['day_of_month'];
                $daysInMonth = (int) $date->format('t');
                $target = min($dom, $daysInMonth); // clamp e.g. 31 -> last day
                return (int) $date->format('j') === $target;
            default:
                return false;
        }
    }

    /**
     * Builds a month calendar grid (weeks of 7 days, Sunday-first) for a child,
     * with each task occurrence and its current status attached to its day.
     */
    public function calendarMonth(int $childId, int $year, int $month): array
    {
        $first = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $last = $first->modify('last day of this month');

        // Expand to whole weeks (grid starts on Sunday).
        $gridStart = $first->modify('-' . (int) $first->format('w') . ' days');
        $gridEnd = $last->modify('+' . (6 - (int) $last->format('w')) . ' days');

        $tasks = $this->tasks->activeForChild($childId);
        $map = $this->completions->mapForChildInRange($childId, $gridStart->format('Y-m-d'), $gridEnd->format('Y-m-d'));
        $today = date('Y-m-d');

        $weeks = [];
        $week = [];
        for ($d = $gridStart; $d <= $gridEnd; $d = $d->modify('+1 day')) {
            $ymd = $d->format('Y-m-d');
            $occurrences = [];
            foreach ($tasks as $task) {
                if ($this->occursOn($task, $d)) {
                    $completion = $map[$task['id'] . '|' . $ymd] ?? null;
                    $occurrences[] = [
                        'task' => $task,
                        'status' => $completion['status'] ?? 'todo',
                    ];
                }
            }

            $week[] = [
                'date' => $ymd,
                'day' => (int) $d->format('j'),
                'in_month' => (int) $d->format('n') === $month,
                'is_today' => $ymd === $today,
                'is_future' => $ymd > $today,
                'occurrences' => $occurrences,
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        $prev = $first->modify('-1 month');
        $next = $first->modify('+1 month');

        return [
            'weeks' => $weeks,
            'label' => $first->format('F Y'),
            'year' => $year,
            'month' => $month,
            'prev' => ['year' => (int) $prev->format('Y'), 'month' => (int) $prev->format('n')],
            'next' => ['year' => (int) $next->format('Y'), 'month' => (int) $next->format('n')],
        ];
    }

    /** Child marks a specific occurrence complete (awaits parent approval). */
    public function markComplete(int $childId, int $taskId, string $dueDate): void
    {
        $task = $this->tasks->find($taskId);
        if (!$task || (int) $task['child_id'] !== $childId || (int) $task['active'] !== 1) {
            throw new ValidationException('That task is not available.');
        }
        if (!$this->isValidDate($dueDate) || !$this->occursOn($task, new DateTimeImmutable($dueDate))) {
            throw new ValidationException('That task is not scheduled for that day.');
        }
        if ($dueDate > date('Y-m-d')) {
            throw new ValidationException('You can\'t complete a task before its day.');
        }

        $existing = $this->completions->findByTaskAndDate($taskId, $dueDate);
        if ($existing && $existing['status'] === 'approved') {
            throw new ValidationException('That task was already approved.');
        }

        $this->completions->markCompleted($taskId, $childId, $dueDate);
    }

    /** Parent approves a completion and awards its stars to the child. */
    public function approveCompletion(int $parentId, int $completionId): void
    {
        $completion = $this->loadPendingCompletion($parentId, $completionId);
        $task = $this->tasks->find((int) $completion['task_id']);
        $stars = (int) $task['stars'];

        $this->inTransaction(function () use ($completion, $stars) {
            $this->completions->approve((int) $completion['id'], $stars);
            $this->users->adjustStars((int) $completion['child_id'], $stars);
        });
    }

    public function rejectCompletion(int $parentId, int $completionId): void
    {
        $completion = $this->loadPendingCompletion($parentId, $completionId);
        $this->completions->reject((int) $completion['id']);
    }

    private function loadPendingCompletion(int $parentId, int $completionId): array
    {
        $completion = $this->completions->find($completionId);
        if (!$completion || $completion['status'] !== 'completed') {
            throw new ValidationException('That task submission is no longer available.');
        }
        $task = $this->tasks->find((int) $completion['task_id']);
        if (!$task || (int) $task['parent_id'] !== $parentId) {
            throw new ValidationException('That task submission is no longer available.');
        }

        return $completion;
    }

    private function isValidDate(string $date): bool
    {
        $d = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }

    private function inTransaction(callable $fn): void
    {
        $this->pdo->beginTransaction();
        try {
            $fn();
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
