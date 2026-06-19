<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CurrencyChangeRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Support\Money;
use App\Support\ValidationException;
use PDO;
use Throwable;

/**
 * All money movement: parent top-ups, child withdrawal requests + approvals,
 * and family currency changes. Multi-statement operations run inside a single
 * DB transaction so balances and the ledger never drift apart.
 */
final class MoneyService
{
    public function __construct(
        private PDO $pdo,
        private UserRepository $users,
        private TransactionRepository $transactions,
        private CurrencyChangeRepository $currencyChanges
    ) {
    }

    /** The currency a child's money is denominated in (their parent's currency). */
    private function currencyFor(array $child): string
    {
        $parent = $this->users->find((int) $child['parent_id']);
        return $parent['currency'] ?? 'USD';
    }

    /** Parent adds money to a child. */
    public function addBalance(int $parentId, int $childId, string $amount, ?string $note): void
    {
        $child = $this->users->childOfParent($parentId, $childId);
        if (!$child) {
            throw new ValidationException('That child was not found.');
        }

        $currency = $this->currencyFor($child);
        $cents = Money::toCents($amount, $currency);
        if ($cents <= 0) {
            throw new ValidationException('Please enter an amount greater than zero.');
        }

        $this->inTransaction(function () use ($childId, $cents, $currency, $note) {
            $this->transactions->create($childId, $cents, 'reward', 'approved', $currency, $note ?: 'Reward from parent');
            $this->users->adjustBalance($childId, $cents);
        });
    }

    /**
     * Parent converts some of a child's stars into spending balance. Both the
     * number of stars to deduct and the amount of money to add are custom
     * inputs (no fixed rate).
     */
    public function convertStarsToBalance(int $parentId, int $childId, int $stars, string $amount): void
    {
        $child = $this->users->childOfParent($parentId, $childId);
        if (!$child) {
            throw new ValidationException('That child was not found.');
        }
        if ($stars <= 0) {
            throw new ValidationException('Please enter how many stars to convert (more than zero).');
        }
        if ($stars > (int) $child['stars']) {
            throw new ValidationException($child['display_name'] . ' only has ' . (int) $child['stars'] . ' stars.');
        }

        $currency = $this->currencyFor($child);
        $cents = Money::toCents($amount, $currency);
        if ($cents <= 0) {
            throw new ValidationException('Please enter an amount greater than zero.');
        }

        $note = sprintf('Converted %d⭐ to balance', $stars);
        $this->inTransaction(function () use ($childId, $stars, $cents, $currency, $note) {
            $this->users->adjustStars($childId, -$stars);
            $this->users->adjustBalance($childId, $cents);
            $this->transactions->create($childId, $cents, 'adjustment', 'approved', $currency, $note);
        });
    }

    /** Child requests a withdrawal (held pending until a parent approves). */
    public function requestWithdraw(array $child, string $amount, ?string $note): void
    {
        $currency = $this->currencyFor($child);
        $cents = Money::toCents($amount, $currency);
        if ($cents <= 0) {
            throw new ValidationException('Please enter an amount greater than zero.');
        }

        $available = (int) $child['balance_cents'] - $this->transactions->pendingWithdrawCents((int) $child['id']);
        if ($cents > $available) {
            throw new ValidationException('You can withdraw at most ' . Money::format(max(0, $available), $currency) . '.');
        }

        $this->transactions->create((int) $child['id'], -$cents, 'withdraw', 'pending', $currency, $note ?: null);
    }

    /** Parent approves a pending withdrawal: deduct the balance. */
    public function approveWithdraw(int $parentId, int $transactionId): void
    {
        $tx = $this->loadPendingWithdrawal($parentId, $transactionId);
        $amount = abs((int) $tx['amount_cents']);

        $child = $this->users->find((int) $tx['child_id']);
        if ((int) $child['balance_cents'] < $amount) {
            throw new ValidationException('The child no longer has enough balance for this withdrawal.');
        }

        $this->inTransaction(function () use ($tx, $amount) {
            $this->users->adjustBalance((int) $tx['child_id'], -$amount);
            $this->transactions->updateStatus((int) $tx['id'], 'approved');
        });
    }

    /** Parent rejects a pending withdrawal: no balance change. */
    public function rejectWithdraw(int $parentId, int $transactionId): void
    {
        $tx = $this->loadPendingWithdrawal($parentId, $transactionId);
        $this->transactions->updateStatus((int) $tx['id'], 'rejected');
    }

    private function loadPendingWithdrawal(int $parentId, int $transactionId): array
    {
        $tx = $this->transactions->find($transactionId);
        if (!$tx || $tx['type'] !== 'withdraw' || $tx['status'] !== 'pending') {
            throw new ValidationException('That withdrawal request is no longer available.');
        }
        if (!$this->users->childOfParent($parentId, (int) $tx['child_id'])) {
            throw new ValidationException('That withdrawal request is no longer available.');
        }

        return $tx;
    }

    /**
     * Change the family currency, converting every child's balance and any
     * pending withdrawals by the supplied exchange rate (new = old × rate).
     */
    public function changeCurrency(int $parentId, string $newCurrency, string $rateInput): void
    {
        $newCurrency = strtoupper(trim($newCurrency));
        if (!Money::isSupported($newCurrency)) {
            throw new ValidationException('Please choose a supported currency.');
        }
        if (!is_numeric(trim($rateInput)) || (float) $rateInput <= 0) {
            throw new ValidationException('Please enter an exchange rate greater than zero.');
        }
        $rate = (float) $rateInput;

        $parent = $this->users->find($parentId);
        $oldCurrency = $parent['currency'] ?? null;
        if ($oldCurrency === $newCurrency) {
            throw new ValidationException('That is already your current currency.');
        }

        $oldDec = $oldCurrency ? Money::decimals($oldCurrency) : 2;
        $newDec = Money::decimals($newCurrency);

        $convert = static function (int $cents) use ($oldDec, $newDec, $rate): int {
            $major = $cents / (10 ** $oldDec);
            return (int) round($major * $rate * (10 ** $newDec));
        };

        $this->inTransaction(function () use ($parentId, $oldCurrency, $newCurrency, $rate, $convert) {
            foreach ($this->users->childrenOf($parentId) as $child) {
                $oldCents = (int) $child['balance_cents'];
                $newCents = $convert($oldCents);
                $this->users->setBalance((int) $child['id'], $newCents);

                $note = sprintf(
                    'Currency changed %s → %s at rate %s (%s → %s)',
                    $oldCurrency ?? '—',
                    $newCurrency,
                    rtrim(rtrim(number_format($rate, 8, '.', ''), '0'), '.'),
                    $oldCurrency ? Money::format($oldCents, $oldCurrency) : (string) $oldCents,
                    Money::format($newCents, $newCurrency)
                );
                $this->transactions->create((int) $child['id'], 0, 'conversion', 'approved', $newCurrency, $note);
            }

            // Restate pending withdrawals into the new currency.
            foreach ($this->transactions->pendingWithdrawalsForParent($parentId) as $wd) {
                $newAbs = $convert(abs((int) $wd['amount_cents']));
                $this->transactions->updateAmountAndCurrency((int) $wd['id'], -$newAbs, $newCurrency);
            }

            $this->users->updateCurrency($parentId, $newCurrency);
            $this->currencyChanges->record($parentId, $oldCurrency, $newCurrency, $rate);
        });
    }

    /** Preview converted balances without persisting (for the settings page). */
    public function previewConversion(int $parentId, string $newCurrency, string $rateInput): array
    {
        $parent = $this->users->find($parentId);
        $oldCurrency = $parent['currency'] ?? 'USD';
        $rate = (float) $rateInput;
        $oldDec = Money::decimals($oldCurrency);
        $newDec = Money::decimals($newCurrency);

        $rows = [];
        foreach ($this->users->childrenOf($parentId) as $child) {
            $oldCents = (int) $child['balance_cents'];
            $newCents = (int) round(($oldCents / (10 ** $oldDec)) * $rate * (10 ** $newDec));
            $rows[] = [
                'name' => $child['display_name'],
                'before' => Money::format($oldCents, $oldCurrency),
                'after' => Money::format($newCents, $newCurrency),
            ];
        }

        return $rows;
    }

    /** Runs a closure inside a DB transaction, rolling back on any error. */
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
