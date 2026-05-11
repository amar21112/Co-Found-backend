<?php

namespace App\Repositories\Contracts;

use App\DTOs\Verification\StoredVerificationDTO;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Models\VerificationAttempt;

interface IdentityVerificationRepositoryInterface
{
    /**
     * Find the user's current verification record (one per user via unique constraint).
     */
    public function findByUser(string $userId): ?IdentityVerification;

    /**
     * Check whether an id_card_number hash is already used by a VERIFIED account
     * other than the given user. Used at submission time and at admin approval time.
     */
    public function hashAlreadyVerified(string $hash, string $excludeUserId): bool;

    /**
     * Generate the canonical HMAC-SHA256 hash of a raw card number.
     * Exposed on the interface so AdminVerificationService can use it
     * without depending on the concrete repository implementation.
     */
    public function hashCardNumber(string $cardNumber): string;

    /**
     * Create a new verification submission.
     */
    public function create(User $user, StoredVerificationDTO $dto): IdentityVerification;

    /**
     * Update an existing rejected verification with fresh submission data.
     * Used when a user resubmits after rejection.
     */
    public function updateForResubmission(
        IdentityVerification  $verification,
        StoredVerificationDTO $dto
    ): IdentityVerification;

    /**
     * Count total verification attempts for a user (for rate limiting).
     */
    public function countAttempts(string $userId): int;

    /**
     * Log a verification attempt for audit and rate limiting.
     */
    public function logAttempt(
        string  $userId,
        string  $result,
        ?string $failureReason,
        ?string $ip,
        array   $submissionData
    ): VerificationAttempt;
}
