<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RewardClaimRepository;
use App\Repositories\TaskCompletionRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Support\PasswordPolicy;
use App\Support\ValidationException;

final class AccountService
{
    public function __construct(
        private UserRepository $users,
        private TransactionRepository $transactions,
        private TaskCompletionRepository $completions,
        private RewardClaimRepository $claims
    ) {
    }

    /**
     * Creates a child account under a parent with a preset (must-change) password.
     *
     * @throws ValidationException
     */
    public function addChild(
        int $parentId,
        string $displayName,
        string $username,
        string $password,
        string $avatarEmoji
    ): int {
        $errors = [];
        $displayName = trim($displayName);
        $username = trim(strtolower($username));
        $avatarEmoji = trim($avatarEmoji) ?: '🐷';

        if ($displayName === '') {
            $errors[] = 'Please enter the child\'s name.';
        }
        if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
            $errors[] = 'Username must be 3–30 characters: letters, numbers, or underscores.';
        } elseif ($this->users->usernameExists($username)) {
            $errors[] = 'That username is already taken.';
        }
        foreach (PasswordPolicy::validate($password) as $rule) {
            $errors[] = 'Preset password must ' . $rule . '.';
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        return $this->users->createChild(
            $parentId,
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $displayName,
            $avatarEmoji
        );
    }

    /**
     * Assembles the parent dashboard: each child plus the parent's pending
     * action counts (withdrawals, task approvals, reward claims).
     */
    public function dashboard(int $parentId): array
    {
        return [
            'children' => $this->users->childrenOf($parentId),
            'pending' => [
                'withdrawals' => $this->transactions->countPendingWithdrawalsForParent($parentId),
                'tasks' => $this->completions->countPendingForParent($parentId),
                'claims' => $this->claims->countPendingForParent($parentId),
            ],
        ];
    }
}
