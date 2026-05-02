<?php

namespace App\DTOs\Verification;

use Illuminate\Http\UploadedFile;

final readonly class SubmitVerificationDTO
{
    public function __construct(
        public UploadedFile $frontImage,
        public UploadedFile $backImage,
        public ?string      $idCardNumber,
        public string       $fullNameOnCard,
        public string       $dateOfBirth,
        public ?string      $nationality,
        public ?string      $expiryDate,
        public string       $submissionMethod,
        public ?array       $livenessCheckData,
        public ?string      $ipAddress,
    ) {}
}
