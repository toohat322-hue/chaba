<?php

namespace App\Exceptions;

use Exception;

/**
 * Structured exception for API business-rule/domain errors (PRD §13 error envelope).
 * Feature code in later phases throws this (or a subclass) to get a consistent
 * { success:false, error:{ code, message, field_errors? } } response without
 * each controller re-building the envelope by hand.
 */
class ApiException extends Exception
{
    /**
     * @param  array<string, array<int, string>>|null  $fieldErrors
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 400,
        public readonly ?array $fieldErrors = null,
    ) {
        parent::__construct($message);
    }

    public static function conflict(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, 409);
    }

    public static function businessRule(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, 422);
    }

    public static function notFound(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, 404);
    }
}
