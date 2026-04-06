<?php

namespace App\Services;

use App\Models\CollaborationRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class RatingService
{
    /**
     * List all ratings received by a user.
     * Non-owners only see public ratings.
     */
    public function list(User $viewer, User $target): Collection
    {
        $query = CollaborationRating::where('rated_user_id', $target->id)
            ->with(['rater', 'ratedUser', 'project']);

        if ($viewer->id !== $target->id) {
            $query->where('visibility', 'public');
        }

        return $query->latest()->get();
    }

    /**
     * Rate a collaborator.
     * A user cannot rate themselves.
     * A user can only rate the same person once per project.
     *
     * @throws ValidationException
     */
    public function store(User $rater, array $data): CollaborationRating
    {
        if ($rater->id === $data['rated_user_id']) {
            throw ValidationException::withMessages([
                'rated_user_id' => ['You cannot rate yourself.'],
            ]);
        }

        $duplicate = CollaborationRating::where('rater_id', $rater->id)
            ->where('rated_user_id', $data['rated_user_id'])
            ->where('project_id', $data['project_id'] ?? null)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'rated_user_id' => ['You have already rated this user for this project.'],
            ]);
        }

        $data['rater_id']       = $rater->id;
        $data['overall_rating'] = $this->computeOverall($data);

        return CollaborationRating::create($data);
    }

    /**
     * Update a rating owned by the rater.
     *
     * @throws ValidationException
     */
    public function update(User $rater, CollaborationRating $rating, array $data): CollaborationRating
    {
        $this->authorizeOwnership($rater, $rating);

        // Recompute overall with merged values
        $merged = array_merge($rating->toArray(), $data);
        $data['overall_rating'] = $this->computeOverall($merged);

        $rating->update($data);

        return $rating->fresh()->load(['rater', 'ratedUser', 'project']);
    }

    /**
     * Delete a rating owned by the rater.
     */
    public function delete(User $rater, CollaborationRating $rating): void
    {
        $this->authorizeOwnership($rater, $rating);
        $rating->delete();
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Compute the overall rating as the average of provided sub-ratings.
     */
    private function computeOverall(array $data): ?float
    {
        $fields = [
            'communication_rating',
            'reliability_rating',
            'skill_rating',
            'problem_solving_rating',
            'teamwork_rating',
        ];

        $values = collect($fields)
            ->map(fn($f) => $data[$f] ?? null)
            ->filter(fn($v) => ! is_null($v))
            ->values();

        if ($values->isEmpty()) {
            return null;
        }

        return round($values->avg(), 2);
    }

    private function authorizeOwnership(User $rater, CollaborationRating $rating): void
    {
        if ($rating->rater_id !== $rater->id) {
            abort(403, 'You do not own this rating.');
        }
    }
}
