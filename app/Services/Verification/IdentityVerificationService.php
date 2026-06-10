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
use App\Services\Ocr\OcrEnricher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class IdentityVerificationService
{
    private const MAX_ATTEMPTS = 3;
    private const STORAGE_DISK = 'local';
    private const STORAGE_DIR  = 'verifications';

    public function __construct(
        private readonly IdentityVerificationRepositoryInterface $verificationRepo,
        private readonly OcrEnricher $ocrEnricher,
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
     *  Business rules:
     *   1. Rate limit      — max MAX_ATTEMPTS total submissions per user.
     *   2. Status guard    — resubmission allowed only after a rejected status.
     *   3. OCR enrichment  — backend calls OCR to extract the NID from the front
     *                        image when the user has not supplied it manually.
     *                        Failure is non-fatal (handled inside OcrEnricher).
     *   4. NID uniqueness  — one ID card per verified account.
     *   5. Private storage — document images stored on the local disk, never public.
     *   6. Attempt log     — every attempt (success or failure) is recorded.
     */
    public function submit(User $user, SubmitVerificationDTO $dto): IdentityVerification
    {
        // ── 1. Rate limit ──────────────────────────────────────────────────────
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

        // ── 2. Status guard ─────────────────────────────────────────────────────
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

        // ── 3. OCR enrichment (best-effort, non-fatal) ──────────────────────────
        $enriched = $this->ocrEnricher->enrich($dto);

        // ── 4. NID uniqueness ───────────────────────────────────────────────────
        if ($enriched->idCardNumber) {
            $hash = $this->verificationRepo->hashCardNumber($enriched->idCardNumber);

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

        // ── 5. Store images ─────────────────────────────────────────────────────
        $frontPath = $this->storeImage($enriched->frontImage, $user->id, 'front');
        $backPath  = $this->storeImage($enriched->backImage,  $user->id, 'back');

        // ── Persist ─────────────────────────────────────────────────────────────
        $storageDto = new StoredVerificationDTO(
            idCardImageFrontPath: $frontPath,
            idCardImageBackPath:  $backPath,
            idCardNumber:         $enriched->idCardNumber,
            fullNameOnCard:       $enriched->fullNameOnCard ?? '',
            dateOfBirth:          $enriched->dateOfBirth,
            nationality:          $enriched->nationality,
            expiryDate:           $enriched->expiryDate,
            submissionMethod:     $enriched->submissionMethod,
            livenessCheckData:    $enriched->livenessCheckData,
            ipAddress:            $enriched->ipAddress,
        );

        $verification = $existing
            ? $this->verificationRepo->updateForResubmission($existing, $storageDto)
            : $this->verificationRepo->create($user, $storageDto);

        // ── 6. Log success ───────────────────────────────────────────────────────
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

    private function storeImage(
        UploadedFile $file,
        string       $userId,
        string       $side,
    ): string {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename  = Str::uuid() . "_$side.$extension";
        $directory = self::STORAGE_DIR . '/' . $userId;

        return $file->storeAs($directory, $filename, self::STORAGE_DISK);
    }
}
