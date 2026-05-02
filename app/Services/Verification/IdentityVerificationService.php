<?php

namespace App\Services\Verification;

use App\DTOs\Verification\StoredVerificationDTO;
use App\DTOs\Verification\SubmitVerificationDTO;
use App\Enums\IdentityVerificationStatus;
use App\Exceptions\Verification\DuplicateIdentityCardException;
use App\Exceptions\Verification\NoVerificationSubmittedException;
use App\Exceptions\Verification\VerificationAlreadyExistsException;
use App\Exceptions\Verification\VerificationAttemptLimitException;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Repositories\Contracts\IdentityVerificationRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class IdentityVerificationService
{
    private const MAX_ATTEMPTS  = 3;
    private const STORAGE_DISK  = 'local';
    private const STORAGE_DIR   = 'verifications';

    public function __construct(
        private readonly IdentityVerificationRepositoryInterface $verificationRepo,
    ) {}

    // =========================================================================
    // Show
    // =========================================================================

    public function show(User $user): IdentityVerification
    {
        $verification = $this->verificationRepo->findByUser($user->id);

        if (! $verification) {
            throw new NoVerificationSubmittedException();
        }

        return $verification;
    }

    // =========================================================================
    // Submit
    // =========================================================================

    /**
     * Submit a new identity verification.
     *
     * Accepts a fully-typed SubmitVerificationDTO from the request layer.
     * Files are stored inside this method — the DTO carries UploadedFile
     * instances; the repository only receives resolved storage paths.
     *
     * Business rules:
     * 1. Rate limit — max MAX_ATTEMPTS total submissions per user.
     * 2. Block resubmission if status is pending/under_review/escalated/verified.
     * 3. Allow resubmission only if status is rejected.
     * 4. id_card_number uniqueness — one ID card per verified account.
     * 5. Document images stored privately on local disk.
     * 6. Every attempt logged to verification_attempts.
     */
    public function submit(User $user, SubmitVerificationDTO $dto): IdentityVerification
    {
        // ── 1. Rate limit ─────────────────────────────────────────────────────
        $attemptCount = $this->verificationRepo->countAttempts($user->id);

        if ($attemptCount >= self::MAX_ATTEMPTS) {
            $this->verificationRepo->logAttempt(
                userId:         $user->id,
                result:         'failure',
                failureReason:  'attempt_limit_reached',
                ip:             $dto->ipAddress,
                submissionData: ['attempt_number' => $attemptCount + 1],
            );
            throw new VerificationAttemptLimitException(self::MAX_ATTEMPTS);
        }

        // ── 2. Existing verification check ────────────────────────────────────
        $existing = $this->verificationRepo->findByUser($user->id);

        if ($existing && $existing->verification_status !== IdentityVerificationStatus::Rejected) {
            $this->verificationRepo->logAttempt(
                userId:         $user->id,
                result:         'failure',
                failureReason:  'already_exists_' . $existing->verification_status->value,
                ip:             $dto->ipAddress,
                submissionData: [],
            );
            throw new VerificationAlreadyExistsException($existing->verification_status);
        }

        // ── 3. id_card_number uniqueness check ────────────────────────────────
        if ($dto->idCardNumber) {
            $hash = $this->verificationRepo->hashCardNumber($dto->idCardNumber);

            if ($this->verificationRepo->hashAlreadyVerified($hash, $user->id)) {
                $this->verificationRepo->logAttempt(
                    userId:         $user->id,
                    result:         'failure',
                    failureReason:  'duplicate_id_card_number',
                    ip:             $dto->ipAddress,
                    submissionData: [],
                );
                throw new DuplicateIdentityCardException();
            }
        }

        // ── 4. Store document images privately ────────────────────────────────
        // Files are stored here — the repo receives resolved paths, not raw files.
        $frontPath = $this->storeDocumentImage($dto->frontImage, $user->id, 'front');
        $backPath  = $this->storeDocumentImage($dto->backImage,  $user->id, 'back');

        // ── 5. Build a storage DTO for the repository ─────────────────────────
        // Replace UploadedFile instances with resolved paths before persisting.
        $storageDto = new StoredVerificationDTO(
            idCardImageFrontPath: $frontPath,
            idCardImageBackPath:  $backPath,
            idCardNumber:         $dto->idCardNumber,
            fullNameOnCard:       $dto->fullNameOnCard,
            dateOfBirth:          $dto->dateOfBirth,
            nationality:          $dto->nationality,
            expiryDate:           $dto->expiryDate,
            submissionMethod:     $dto->submissionMethod,
            livenessCheckData:    $dto->livenessCheckData,
            ipAddress:            $dto->ipAddress,
        );

        // ── 6. Persist ────────────────────────────────────────────────────────
        $verification = $existing
            ? $this->verificationRepo->updateForResubmission($existing, $storageDto)
            : $this->verificationRepo->create($user, $storageDto);

        // ── 7. Log success ────────────────────────────────────────────────────
        $this->verificationRepo->logAttempt(
            userId:         $user->id,
            result:         'success',
            failureReason:  null,
            ip:             $dto->ipAddress,
            submissionData: [
                'verification_id'   => $verification->id,
                'submission_method' => $dto->submissionMethod,
                'is_resubmission'   => $existing !== null,
            ],
        );

        return $verification;
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function storeDocumentImage(
        UploadedFile $file,
        string                        $userId,
        string                        $side,
    ): string {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename  = Str::uuid() . "_$side.$extension";
        $directory = self::STORAGE_DIR . '/' . $userId;

        return $file->storeAs($directory, $filename, self::STORAGE_DISK);
    }
}
