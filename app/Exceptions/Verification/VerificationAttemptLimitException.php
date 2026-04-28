<?php

namespace App\Exceptions\Verification;

use RuntimeException;

class VerificationAttemptLimitException extends RuntimeException
{
    public function __construct(public readonly int $maxAttempts = 3)
    {
        parent::__construct(
            "You have reached the maximum of {$maxAttempts} verification submission attempts. " .
            "Please contact support for assistance."
        );
    }
}
