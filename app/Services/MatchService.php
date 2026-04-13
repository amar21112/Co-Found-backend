<?php

namespace App\Services;

use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class MatchService
{
    /**
     * List all matches for the user.
     *
     * Filters  : match_type (collaborator | project),
     *            viewed (true | false),
     *            saved (true | false),
     *            min_score (e.g. 0.7 — minimum compatibility_score)
     * Sort     : sort_by (compatibility_score | created_at | expires_at),
     *            sort_dir (asc | desc)
     * Paginate : per_page (default 15, max 50)
     */
    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = MatchModel::where('user_id', $user->id)
            ->with(['matchedUser', 'matchedProject']);

        // Filter by match type
        if (! empty($filters['match_type'])) {
            $query->where('match_type', $filters['match_type']);
        }

        // Filter by viewed status
        if (isset($filters['viewed']) && $filters['viewed'] !== '') {
            $query->where('viewed', filter_var($filters['viewed'], FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by saved status
        if (isset($filters['saved']) && $filters['saved'] !== '') {
            $query->where('saved', filter_var($filters['saved'], FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by minimum compatibility score
        if (isset($filters['min_score']) && $filters['min_score'] !== '') {
            $query->where('compatibility_score', '>=', (float) $filters['min_score']);
        }

        // Sorting
        $allowed = ['compatibility_score', 'created_at', 'expires_at'];
        $sortBy  = in_array($filters['sort_by'] ?? '', $allowed) ? $filters['sort_by'] : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->paginate($perPage);
    }

    /**
     * Mark a match as viewed (idempotent).
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

        $match->update(['action_taken' => true]);

        return $feedback;
    }

    private function authorizeOwnership(User $user, MatchModel $match): void
    {
        if ($match->user_id !== $user->id) {
            abort(403, 'This match does not belong to you.');
        }
    }
}
