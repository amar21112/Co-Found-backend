<?php

namespace App\Services;

use App\DTOs\Match\SubmitFeedbackDTO;
use App\Exceptions\Match\FeedbackAlreadySubmittedException;
use App\Exceptions\Match\MatchNotFoundException;
use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\User;
use App\Repositories\Contracts\MatchRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MatchService
{
    public function __construct(
        private readonly MatchRepositoryInterface $matchRepo,
    ) {}

    // =========================================================================
    // List
    // =========================================================================

    /**
     * List matches for the user.
     *
     * Filters: match_type, viewed, saved, min_score
     * Sorting: sort_by (compatibility_score|created_at|expires_at), sort_dir
     * Pagination: per_page (default 15, max 50)
     */
    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $this->matchRepo->paginate($user, $filters, $perPage);
    }

    // =========================================================================
    // Show
    // =========================================================================

    public function show(string $id, User $user): MatchModel
    {
        $match = $this->matchRepo->findById($id);

        if (! $match || $match->user_id !== $user->id) {
            throw new MatchNotFoundException();
        }

        return $match;
    }

    // =========================================================================
    // Mark Viewed
    // =========================================================================

    /**
     * Mark a match as viewed — idempotent.
     */
    public function markViewed(User $user, MatchModel $match): MatchModel
    {
        $this->authorizeOwnership($user, $match);

        return $this->matchRepo->markViewed($match);
    }

    // =========================================================================
    // Toggle Save
    // =========================================================================

    public function toggleSave(User $user, MatchModel $match): MatchModel
    {
        $this->authorizeOwnership($user, $match);

        return $this->matchRepo->toggleSave($match);
    }

    // =========================================================================
    // Submit Feedback
    // =========================================================================

    /**
     * Submit feedback for a match.
     *
     * Business rules:
     * - One feedback per user per match (unique constraint in DB).
     * - Submitting feedback marks the match as action_taken.
     * - Returns 409 if feedback already exists.
     */
    public function submitFeedback(
        User             $user,
        MatchModel       $match,
        SubmitFeedbackDTO $dto
    ): MatchFeedback {
        $this->authorizeOwnership($user, $match);

        if ($this->matchRepo->hasFeedback($match, $user->id)) {
            throw new FeedbackAlreadySubmittedException();
        }

        $feedback = $this->matchRepo->createFeedback($match, $user, $dto);

        $this->matchRepo->markActionTaken($match);

        return $feedback;
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function authorizeOwnership(User $user, MatchModel $match): void
    {
        if ($match->user_id !== $user->id) {
            abort(403, 'This match does not belong to you.');
        }
    }
}
