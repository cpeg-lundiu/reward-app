<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RewardClaimRepository;
use App\Repositories\RewardRepository;
use App\Repositories\UserRepository;
use App\Support\ValidationException;
use PDO;
use Throwable;

/**
 * Reward catalog + claims. Stars are spent the moment a child claims; a parent
 * either completes the claim (fulfils it) or rejects it (refunds the stars).
 */
final class RewardService
{
    public function __construct(
        private PDO $pdo,
        private RewardRepository $rewards,
        private RewardClaimRepository $claims,
        private UserRepository $users
    ) {
    }

    /** Parent adds a reward to the catalog (optionally for one specific child). */
    public function createReward(int $parentId, array $input): int
    {
        $errors = [];
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? '')) ?: null;
        $starCost = (int) ($input['star_cost'] ?? 0);
        $emoji = trim((string) ($input['emoji'] ?? '')) ?: '🎁';
        $childId = ($input['child_id'] ?? '') !== '' ? (int) $input['child_id'] : null;

        if ($title === '') {
            $errors[] = 'Please enter a reward title.';
        }
        if ($starCost < 1) {
            $errors[] = 'Star cost must be at least 1.';
        }
        if ($childId !== null && !$this->users->childOfParent($parentId, $childId)) {
            $errors[] = 'Please choose one of your children (or leave it for everyone).';
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        return $this->rewards->create([
            'parent_id' => $parentId,
            'child_id' => $childId,
            'title' => $title,
            'description' => $description,
            'star_cost' => $starCost,
            'emoji' => $emoji,
        ]);
    }

    /** Child claims a reward, spending stars immediately. */
    public function claimReward(array $child, int $rewardId): void
    {
        $reward = $this->rewards->find($rewardId);
        $childId = (int) $child['id'];

        $isAvailable = $reward
            && (int) $reward['active'] === 1
            && (int) $reward['parent_id'] === (int) $child['parent_id']
            && ($reward['child_id'] === null || (int) $reward['child_id'] === $childId);

        if (!$isAvailable) {
            throw new ValidationException('That reward is not available.');
        }
        if ((int) $child['stars'] < (int) $reward['star_cost']) {
            throw new ValidationException('You need more stars to claim this reward.');
        }

        $this->inTransaction(function () use ($reward, $childId) {
            $this->users->adjustStars($childId, -(int) $reward['star_cost']);
            $this->claims->create((int) $reward['id'], $childId, (int) $reward['star_cost']);
        });
    }

    /** Parent marks a claim fulfilled. */
    public function completeClaim(int $parentId, int $claimId): void
    {
        $claim = $this->loadPendingClaim($parentId, $claimId);
        $this->claims->updateStatus((int) $claim['id'], 'completed');
    }

    /** Parent rejects a claim and refunds the stars. */
    public function rejectClaim(int $parentId, int $claimId): void
    {
        $claim = $this->loadPendingClaim($parentId, $claimId);

        $this->inTransaction(function () use ($claim) {
            $this->claims->updateStatus((int) $claim['id'], 'rejected');
            $this->users->adjustStars((int) $claim['child_id'], (int) $claim['star_cost']);
        });
    }

    private function loadPendingClaim(int $parentId, int $claimId): array
    {
        $claim = $this->claims->find($claimId);
        if (!$claim || $claim['status'] !== 'pending') {
            throw new ValidationException('That reward request is no longer available.');
        }
        $reward = $this->rewards->find((int) $claim['reward_id']);
        if (!$reward || (int) $reward['parent_id'] !== $parentId) {
            throw new ValidationException('That reward request is no longer available.');
        }

        return $claim;
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
