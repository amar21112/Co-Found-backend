<?php

namespace App\Repositories\Contracts;

use App\DTOs\Admin\ReviewVerificationDTO;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Models\VerificationReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminVerificationRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?IdentityVerification;

    public function updateStatus(
        IdentityVerification $verification,
        string               $status,
        ?string              $rejectionReason = null
    ): IdentityVerification;

    public function createReview(
        IdentityVerification  $verification,
        User                  $reviewer,
        ReviewVerificationDTO $dto
    ): VerificationReview;

    /**
     * Claim a verification for review — sets status to under_review.
     * Only one moderator should work a case at a time.
     */
    public function claim(IdentityVerification $verification, User $moderator): IdentityVerification;

    /**
     * Escalate a verification to admin review — sets status to escalated.
     */
    public function escalate(IdentityVerification $verification, User $moderator, ?string $notes): IdentityVerification;
}
