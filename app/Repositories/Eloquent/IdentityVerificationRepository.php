<?php

namespace App\Repositories\Eloquent;

use App\DTOs\Verification\StoredVerificationDTO;
use App\Enums\IdentityVerificationStatus;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Models\VerificationAttempt;
use App\Repositories\Contracts\IdentityVerificationRepositoryInterface;

class IdentityVerificationRepository implements IdentityVerificationRepositoryInterface
{
    public function findByUser(string $userId): ?IdentityVerification
    {
        return IdentityVerification::where('user_id', $userId)
            ->with(['reviews' => fn($q) => $q->orderByDesc('reviewed_at')->limit(1)])
            ->first();
    }

    /**
     * Check whether an id_card_number hash is already used by a VERIFIED account
     * other than the given user. Used for duplicate detection at both submission
     * and admin approval time.
     */
    public function hashAlreadyVerified(string $hash, string $excludeUserId): bool
    {
        return IdentityVerification::where('id_card_number_hash', $hash)
            ->where('user_id', '!=', $excludeUserId)
            ->where('verification_status', IdentityVerificationStatus::Verified->value)
            ->exists();
    }

    public function create(User $user, StoredVerificationDTO $dto): IdentityVerification
    {
        $hash = $dto->idCardNumber
            ? $this->hashCardNumber($dto->idCardNumber)
            : null;

        return IdentityVerification::create([
            'user_id'              => $user->id,
            'id_card_image_front'  => $dto->idCardImageFrontPath,
            'id_card_image_back'   => $dto->idCardImageBackPath,
            'id_card_number'       => $dto->idCardNumber
                ? encrypt($dto->idCardNumber)
                : null,
            'id_card_number_hash'  => $hash,
            'full_name_on_card'    => $dto->fullNameOnCard,
            'date_of_birth'        => $dto->dateOfBirth,
            'nationality'          => $dto->nationality,
            'expiry_date'          => $dto->expiryDate,
            'submission_method'    => $dto->submissionMethod,
            'liveness_check_data'  => $dto->livenessCheckData,
            'ip_address'           => $dto->ipAddress,
            'liveness_check_passed'=> false,
            'verification_status'  => IdentityVerificationStatus::Pending->value,
        ]);
    }

    public function updateForResubmission(
        IdentityVerification  $verification,
        StoredVerificationDTO $dto
    ): IdentityVerification {
        $hash = $dto->idCardNumber
            ? $this->hashCardNumber($dto->idCardNumber)
            : null;

        $verification->update([
            'id_card_image_front'  => $dto->idCardImageFrontPath,
            'id_card_image_back'   => $dto->idCardImageBackPath,
            'id_card_number'       => $dto->idCardNumber
                ? encrypt($dto->idCardNumber)
                : null,
            'id_card_number_hash'  => $hash,
            'full_name_on_card'    => $dto->fullNameOnCard,
            'date_of_birth'        => $dto->dateOfBirth,
            'nationality'          => $dto->nationality,
            'expiry_date'          => $dto->expiryDate,
            'submission_method'    => $dto->submissionMethod,
            'liveness_check_data'  => $dto->livenessCheckData,
            'ip_address'           => $dto->ipAddress,
            'liveness_check_passed'=> false,
            'face_match_score'     => null,
            'rejection_reason'     => null,
            'verification_status'  => IdentityVerificationStatus::Pending->value,
        ]);

        return $verification->fresh();
    }

    public function countAttempts(string $userId): int
    {
        return VerificationAttempt::where('user_id', $userId)->count();
    }

    public function logAttempt(
        string  $userId,
        string  $result,
        ?string $failureReason,
        ?string $ip,
        array   $submissionData
    ): VerificationAttempt {
        $currentCount = $this->countAttempts($userId);

        return VerificationAttempt::create([
            'user_id'         => $userId,
            'attempt_number'  => $currentCount + 1,
            'result'          => $result,
            'failure_reason'  => $failureReason,
            'ip_address'      => $ip,
            'submission_data' => $submissionData,
        ]);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Generate a deterministic HMAC-SHA256 hash of the card number.
     * Keyed with APP_KEY so the hash is application-specific and cannot be
     * cross-referenced with external breached datasets.
     *
     * This hash is stored alongside the encrypted card number and is used
     * ONLY for duplicate detection — it is never returned to the client.
     */
    public function hashCardNumber(string $cardNumber): string
    {
        return hash_hmac(
            'sha256',
            mb_strtoupper(trim($cardNumber)),  // normalise before hashing
            config('app.key')
        );
    }
}
