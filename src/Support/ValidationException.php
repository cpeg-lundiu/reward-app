<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Thrown by services when user input fails validation or a business rule.
 * Controllers catch it and surface the messages as flash errors.
 */
final class ValidationException extends RuntimeException
{
    /** @var string[] */
    private array $errors;

    /** @param string|string[] $errors */
    public function __construct(string|array $errors)
    {
        $this->errors = is_array($errors) ? array_values($errors) : [$errors];
        parent::__construct(implode(' ', $this->errors));
    }

    /** @return string[] */
    public function errors(): array
    {
        return $this->errors;
    }
}
