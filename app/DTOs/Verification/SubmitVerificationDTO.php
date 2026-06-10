<?php

namespace App\DTOs\Verification;

use Illuminate\Http\UploadedFile;

/**
 * Carries exactly what the frontend sends — nothing more.
 *
 * Card fields (NID, name, date of birth, etc.) are not present here.
 * They are extracted server-side by OcrEnricher and live in
 * EnrichedVerificationDTO once OCR has run.
 */
final readonly class SubmitVerificationDTO
{
    public function __construct(
        public UploadedFile $frontImage,
        public UploadedFile $backImage,
        public string       $submissionMethod,
        public ?string      $ipAddress,
    ) {}
}
