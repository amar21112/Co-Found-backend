<?php

namespace App\Exceptions\Ocr;

use Throwable;

/**
 * Thrown when the OCR service cannot be reached at all
 * (network timeout, DNS failure, connection refused, etc.).
 *
 * Callers should surface this as a 503 to the user rather than a 500,
 * since it is an external dependency failure, not a code bug.
 */
class OcrServiceUnavailableException extends OcrServiceException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            'The ID recognition service is temporarily unavailable. Please try again shortly.',
            503,
            $previous,
        );
    }
}
