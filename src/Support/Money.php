<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Currency helpers. Balances are stored as integer minor units ("cents")
 * to avoid floating-point drift. This maps ISO codes to symbol/decimals
 * and converts between the stored cents and human-friendly strings.
 */
final class Money
{
    /** Supported currencies: code => [symbol, name, decimals]. */
    private const CURRENCIES = [
        'USD' => ['$', 'US Dollar', 2],
        'EUR' => ['€', 'Euro', 2],
        'GBP' => ['£', 'British Pound', 2],
        'CNY' => ['¥', 'Chinese Yuan', 2],
        'JPY' => ['¥', 'Japanese Yen', 0],
        'HKD' => ['HK$', 'Hong Kong Dollar', 2],
        'TWD' => ['NT$', 'Taiwan Dollar', 2],
        'SGD' => ['S$', 'Singapore Dollar', 2],
        'AUD' => ['A$', 'Australian Dollar', 2],
        'CAD' => ['C$', 'Canadian Dollar', 2],
        'KRW' => ['₩', 'Korean Won', 0],
        'INR' => ['₹', 'Indian Rupee', 2],
    ];

    /** @return array<string, array{0:string,1:string,2:int}> */
    public static function currencies(): array
    {
        return self::CURRENCIES;
    }

    public static function isSupported(string $code): bool
    {
        return isset(self::CURRENCIES[strtoupper($code)]);
    }

    public static function symbol(string $code): string
    {
        return self::CURRENCIES[strtoupper($code)][0] ?? $code;
    }

    public static function name(string $code): string
    {
        return self::CURRENCIES[strtoupper($code)][1] ?? $code;
    }

    public static function decimals(string $code): int
    {
        return self::CURRENCIES[strtoupper($code)][2] ?? 2;
    }

    /** Format stored cents as a display string, e.g. 1234 -> "$12.34". */
    public static function format(int $cents, string $code): string
    {
        $decimals = self::decimals($code);
        $divisor = 10 ** $decimals;
        $amount = $cents / $divisor;

        return self::symbol($code) . number_format($amount, $decimals);
    }

    /** Format stored cents as a bare number string (no symbol), for inputs. */
    public static function amount(int $cents, string $code): string
    {
        $decimals = self::decimals($code);
        return number_format($cents / (10 ** $decimals), $decimals, '.', '');
    }

    /**
     * Parse a user-entered amount (major units, e.g. "12.34") into cents.
     * Throws on invalid / negative input.
     */
    public static function toCents(string $input, string $code): int
    {
        $clean = str_replace([',', ' '], '', trim($input));
        if ($clean === '' || !is_numeric($clean) || (float) $clean < 0) {
            throw new InvalidArgumentException('Please enter a valid amount.');
        }

        $decimals = self::decimals($code);
        return (int) round((float) $clean * (10 ** $decimals));
    }
}
