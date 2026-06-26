<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Timezone helpers. All datetimes are stored in the database as UTC; this
 * converts to/from a user's IANA timezone (e.g. "America/New_York") for
 * "what day is it for this child" logic and for display.
 */
final class Tz
{
    public const DEFAULT = 'UTC';

    /** Normalise a possibly-null/invalid timezone to a usable IANA name. */
    public static function normalize(?string $tz): string
    {
        $tz = trim((string) $tz);
        return ($tz !== '' && self::isValid($tz)) ? $tz : self::DEFAULT;
    }

    public static function isValid(string $tz): bool
    {
        try {
            new DateTimeZone($tz);
            return in_array($tz, DateTimeZone::listIdentifiers(), true);
        } catch (Throwable) {
            return false;
        }
    }

    /** @return string[] All IANA timezone identifiers, for a dropdown. */
    public static function identifiers(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    /** Current moment expressed in the given timezone. */
    public static function now(?string $tz): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone(self::normalize($tz)));
    }

    /** Today's calendar date (Y-m-d) in the given timezone. */
    public static function today(?string $tz): string
    {
        return self::now($tz)->format('Y-m-d');
    }

    /**
     * Format a UTC datetime string (as stored in the DB) for display in the
     * viewer's timezone.
     */
    public static function display(?string $utcDateTime, ?string $tz, string $format = 'M j, Y · g:i A'): string
    {
        if ($utcDateTime === null || $utcDateTime === '') {
            return '';
        }

        try {
            $dt = new DateTimeImmutable($utcDateTime, new DateTimeZone('UTC'));
            return $dt->setTimezone(new DateTimeZone(self::normalize($tz)))->format($format);
        } catch (Throwable) {
            return (string) $utcDateTime;
        }
    }
}
