<?php

namespace App\DTOs\Verification;

use Illuminate\Http\UploadedFile;

/**
 * Produced by OcrEnricher after the OCR service has run.
 *
 * Contains everything from the original submission plus all card fields
 * extracted (or attempted) by the OCR pipeline. Card fields are nullable
 * because OCR is best-effort — a failed or partial extraction must not
 * block the submission.
 *
 * This DTO is internal to the service layer and never built from
 * HTTP request data directly.
 */
final readonly class EnrichedVerificationDTO
{
    public function __construct(
        public UploadedFile $frontImage,
        public UploadedFile $backImage,
        public string       $submissionMethod,
        public ?string      $ipAddress,
        public ?string      $idCardNumber      = null,
        public ?string      $fullNameOnCard    = null,
        public ?string      $dateOfBirth       = null,
        public ?string      $nationality       = null,
        public ?string      $expiryDate        = null,
        public ?array       $livenessCheckData = null,
    ) {}
}
