<?php

namespace App\Repositories\Eloquent;

use App\DTOs\Admin\ReviewVerificationDTO;
use App\Enums\IdentityVerificationStatus;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Models\VerificationReview;
use App\Repositories\Contracts\AdminVerificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminVerificationRepository implements AdminVerificationRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = IdentityVerification::with(['user', 'latestReview']);

        if (! empty($filters['status'])) {
            $query->where('verification_status', $filters['status']);
        }

        // Oldest pending first — natural review queue order
        $query->orderBy('created_at', 'asc');

        return $query->paginate($perPage);
    }

    public function findById(string $id): ?IdentityVerification
    {
        return IdentityVerification::with(['user', 'reviews.reviewer'])->find($id);
    }

    public function updateStatus(
        IdentityVerification $verification,
        string               $status,
        ?string              $rejectionReason = null
    ): IdentityVerification {
        $verification->update([
            'verification_status' => $status,
            'rejection_reason'    => $rejectionReason,
        ]);

        return $verification->fresh(['user', 'reviews.reviewer']);
    }

    public function createReview(
        IdentityVerification  $verification,
        User                  $reviewer,
        ReviewVerificationDTO $dto
    ): VerificationReview {
        return VerificationReview::create([
            'verification_id'           => $verification->id,
            'reviewer_id'               => $reviewer->id,
            'review_action'             => $dto->reviewAction->value,
            'review_notes'              => $dto->reviewNotes,
            'rejection_reason_category' => $dto->rejectionReasonCategory?->value,
            'automated_checks_passed'   => $dto->automatedChecksPassed,
            'automated_checks_data'     => $dto->automatedChecksData,
            'reviewed_at'               => now(),
        ]);
    }

    public function claim(IdentityVerification $verification, User $moderator): IdentityVerification
    {
        $verification->update([
            'verification_status' => IdentityVerificationStatus::UnderReview->value,
        ]);

        // Claim is logged via AdminActionLogger in the service layer, not here.
        // The repository only manages persistence — no fake review records.

        return $verification->fresh(['user', 'reviews.reviewer']);
    }

    public function escalate(
        IdentityVerification $verification,
        User                 $moderator,
        ?string              $notes
    ): IdentityVerification {
        $verification->update([
            'verification_status' => IdentityVerificationStatus::Escalated->value,
            'rejection_reason'    => $notes, // reuse field to store escalation notes
        ]);

        return $verification->fresh(['user', 'reviews.reviewer']);
    }
}
