<?php

namespace App\Exceptions\ML;

use RuntimeException;
use Throwable;

/**
 * Thrown when the ML service returns an error or is unreachable.
 *
 * isRetryable() distinguishes transient failures (network, 5xx) from
 * permanent ones (401, 422) so callers can decide whether to retry.
 */
class MlServiceException extends RuntimeException
{
    public function __construct(
        string               $message,
        private readonly int $httpStatus = 0,
        ?Throwable           $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function isRetryable(): bool
    {
        return $this->httpStatus === 0 || $this->httpStatus >= 500;
    }
}
