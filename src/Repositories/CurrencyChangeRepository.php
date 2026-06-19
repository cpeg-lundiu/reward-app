<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CurrencyChangeRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function record(int $parentId, ?string $from, string $to, float $rate): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO currency_changes (parent_id, from_currency, to_currency, exchange_rate)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$parentId, $from, $to, $rate]);

        return (int) $this->pdo->lastInsertId();
    }
}
