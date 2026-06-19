<?php

declare(strict_types=1);

if (!function_exists('e')) {
    /** HTML-escape a value for safe output in templates. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
