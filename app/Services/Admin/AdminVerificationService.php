<?php

namespace App\Services\Admin;

use App\DTOs\Admin\ReviewVerificationDTO;
use App\Enums\IdentityVerificationLevel;
use App\Enums\IdentityVerificationStatus;
use App\Enums\ReviewAction;
use App\Exceptions\Admin\VerificationAlreadyReviewedException;
use App\Exceptions\Admin\VerificationNotClaimableException;
use App\Exceptions\Admin\VerificationNotEscalatableException;
use App\Exceptions\Admin\VerificationNotFoundException;
use App\Exceptions\Verification\DuplicateIdentityCardException;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Repositories\Contracts\AdminVerificationRepositoryInterface;
use App\Repositories\Contracts\IdentityVerificationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class AdminVerificationService
{
    public function __construct(
        private AdminVerificationRepositoryInterface    $verificationRepo,
        private UserRepositoryInterface                 $userRepo,
        private AdminActionLogger                       $logger,
        private IdentityVerificationRepositoryInterface $identityVerificationRepo,
    ) {}

    // =========================================================================
    // List
    // =========================================================================

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->verificationRepo->paginate($filters, $perPage);
    }

    // =========================================================================
    // Show
    // =========================================================================

    public function show(string $id): IdentityVerification
    {
        $verification = $this->verificationRepo->findById($id);

        if (! $verification) {
            throw new VerificationNotFoundException();
        }

        return $verification;
    }

    // =========================================================================
    // Review
    // =========================================================================

    /**
     * Submit a review decision on an identity verification submission.
     *
     * Business rules:
     * - Cannot review a verification in a terminal state (verified or rejected).
     * - On approval: user.identity_verified → true, level → advanced,
     *   verification_status → verified.
     * - On rejection: verification_status → rejected, rejection_reason set.
     * - On request_resubmission: verification_status → pending (back to queue).
     * - Every action is logged to admin_actions for audit trail.
     */
    public function review(
        IdentityVerification $verification,
        User                 $reviewer,
        ReviewVerificationDTO $dto,
        string               $ip,
    ): IdentityVerification {
        // Guard — terminal states cannot be re-reviewed
        if ($verification->isVerified() || $verification->isRejected()) {
            throw new VerificationAlreadyReviewedException();
        }

        // ── Safety net: check id_card_number uniqueness before approving ──────
        // Even if the submission check passed, a race condition could allow two
        // users to submit the same card number before either is approved.
        // We check again here — at the point of approval — as the authoritative guard.
        if ($dto->reviewAction === ReviewAction::Approved
            && $verification->id_card_number_hash
        ) {
            if ($this->identityVerificationRepo->hashAlreadyVerified(
                $verification->id_card_number_hash,
                $verification->user_id
            )) {
                throw new DuplicateIdentityCardException();
            }
        }

        // Create the review record — use enum ->value for write safety
        $this->verificationRepo->createReview($verification, $reviewer, $dto);

        // Determine new verification status
        [$newStatus, $rejectionReason] = $this->resolveOutcome($dto);

        $updated = $this->verificationRepo->updateStatus(
            $verification,
            $newStatus->value,
            $rejectionReason,
        );

        // Sync user identity_verified flag
        $this->syncUserVerificationStatus($verification->user, $dto->reviewAction);

        // Audit log
        $this->logger->log(
            admin:      $reviewer,
            actionType: 'verification_review',
            targetType: 'identity_verification',
            targetId:   $verification->id,
            details:    [
                'review_action'             => $dto->reviewAction->value,
                'new_status'                => $newStatus->value,
                'rejection_reason_category' => $dto->rejectionReasonCategory?->value,
                'user_id'                   => $verification->user_id,
            ],
            ip: $ip,
        );

        return $updated;
    }

    // =========================================================================
    // Claim (mark under_review)
    // =========================================================================

    /**
     * Claim a verification for review.
     *
     * Sets status to under_review so two moderators don't work the same case.
     * Only pending or escalated verifications can be claimed.
     * Logged to admin_actions.
     */
    public function claim(
        IdentityVerification $verification,
        User                 $moderator,
        string               $ip,
    ): IdentityVerification {
        if (! in_array($verification->verification_status, [
            IdentityVerificationStatus::Pending,
            IdentityVerificationStatus::Escalated,
        ])) {
            throw new VerificationNotClaimableException();
        }

        $updated = $this->verificationRepo->claim($verification, $moderator);

        $this->logger->log(
            admin:      $moderator,
            actionType: 'verification_claimed',
            targetType: 'identity_verification',
            targetId:   $verification->id,
            details:    ['user_id' => $verification->user_id],
            ip:         $ip,
        );

        return $updated;
    }

    // =========================================================================
    // Escalate
    // =========================================================================

    /**
     * Escalate a verification to administrator level.
     *
     * Only under_review verifications can be escalated.
     * Logged to admin_actions.
     */
    public function escalate(
        IdentityVerification $verification,
        User                 $moderator,
        ?string              $notes,
        string               $ip,
    ): IdentityVerification {
        if (! $verification->isUnderReview()) {
            throw new VerificationNotEscalatableException();
        }

        $updated = $this->verificationRepo->escalate($verification, $moderator, $notes);

        $this->logger->log(
            admin:      $moderator,
            actionType: 'verification_escalated',
            targetType: 'identity_verification',
            targetId:   $verification->id,
            details:    [
                'user_id' => $verification->user_id,
                'notes'   => $notes,
            ],
            ip: $ip,
        );

        return $updated;
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * @return array{0: IdentityVerificationStatus, 1: string|null}
     */
    private function resolveOutcome(ReviewVerificationDTO $dto): array
    {
        return match($dto->reviewAction) {
            ReviewAction::Approved => [
                IdentityVerificationStatus::Verified,
                null,
            ],
            ReviewAction::Rejected => [
                IdentityVerificationStatus::Rejected,
                $dto->reviewNotes,
            ],
            ReviewAction::RequestResubmission => [
                IdentityVerificationStatus::Pending,
                null,
            ],
        };
    }

    private function syncUserVerificationStatus(User $user, ReviewAction $action): void
    {
        if ($action->approvesUser()) {
            $this->userRepo->update($user, [
                'identity_verified'           => true,
                'identity_verification_level' => IdentityVerificationLevel::Advanced->value,
            ]);
        } else {
            $this->userRepo->update($user, [
                'identity_verified'           => false,
                'identity_verification_level' => IdentityVerificationLevel::None->value,
            ]);
        }
    }
}
