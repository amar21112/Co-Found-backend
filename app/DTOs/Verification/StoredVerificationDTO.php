<?php

namespace App\DTOs\Verification;

/**
 * Carries resolved storage paths (not raw UploadedFile instances) to the repository.
 *
 * The service layer stores the files and builds this DTO before calling the repo.
 * This keeps file I/O out of the repository and makes the repo unit-testable
 * without needing real uploaded files.
 */
final readonly class StoredVerificationDTO
{
    public function __construct(
        public string  $idCardImageFrontPath,
        public string  $idCardImageBackPath,
        public ?string $idCardNumber,
        public string  $fullNameOnCard,
        public ?string  $dateOfBirth,
        public ?string $nationality,
        public ?string $expiryDate,
        public string  $submissionMethod,
        public ?array  $livenessCheckData,
        public ?string $ipAddress,
    ) {}
}
