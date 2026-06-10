<?php

namespace App\Exceptions\Ocr;

use RuntimeException;
use Throwable;

/**
 * Thrown when the OCR service returns an error response (4xx / 5xx)
 * or is misconfigured.
 */
class OcrServiceException extends RuntimeException
{
    public function __construct(
        string     $message  = 'OCR service error.',
        int        $code     = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
