<?php

namespace App\Services;

use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class MatchService
{
    /**
     * List all matches for the user, eager-loading the matched entity.
     */
    public function list(User $user): Collection
    {
        return MatchModel::where('user_id', $user->id)
            ->with(['matchedUser', 'matchedProject'])
            ->latest()
            ->get();
    }

    /**
     * Mark a match as viewed (idempotent — safe to call multiple times).
     */
    public function markViewed(User $user, MatchModel $match): MatchModel
    {
        $this->authorizeOwnership($user, $match);

        if (! $match->viewed) {
            $match->update([
                'viewed'    => true,
                'viewed_at' => now(),
            ]);
        }

        return $match->load(['matchedUser', 'matchedProject']);
    }

    /**
     * Toggle the saved state of a match.
     */
    public function toggleSave(User $user, MatchModel $match): MatchModel
    {
        $this->authorizeOwnership($user, $match);

        $match->update(['saved' => ! $match->saved]);

        return $match->load(['matchedUser', 'matchedProject']);
    }

    /**
     * Submit feedback for a match.
     * A user can only submit one feedback entry per match.
     *
     * @throws ValidationException
     */
    public function submitFeedback(User $user, MatchModel $match, array $data): MatchFeedback
    {
        $this->authorizeOwnership($user, $match);

        $exists = MatchFeedback::where('match_id', $match->id)
                               ->where('user_id', $user->id)
                               ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'match' => ['You have already submitted feedback for this match.'],
            ]);
        }

        $feedback = MatchFeedback::create([
            'match_id'      => $match->id,
            'user_id'       => $user->id,
            'feedback_type' => $data['feedback_type'],
        ]);

        // Mark action_taken on the match
        $match->update(['action_taken' => true]);

        return $feedback;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function authorizeOwnership(User $user, MatchModel $match): void
    {
        if ($match->user_id !== $user->id) {
            abort(403, 'This match does not belong to you.');
        }
    }
}
