<?php

namespace App\Services;

use App\Models\CollaborationRating;
use App\Models\User;
use App\Traits\SendsNotifications;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class RatingService
{
    use SendsNotifications;
    /**
     * List ratings received by a user.
     *
     * Non-owners only see public ratings.
     *
     * Filters  : min_overall (minimum overall_rating, e.g. 3.5),
     *            project_id (filter by a specific project)
     * Sort     : sort_by (overall_rating | created_at), sort_dir (asc | desc)
     * Paginate : per_page (default 15, max 50)
     */
    public function list(User $viewer, User $target, array $filters = []): LengthAwarePaginator
    {
        $query = CollaborationRating::where('rated_user_id', $target->id)
            ->with(['rater', 'ratedUser', 'project']);

        // Non-owners only see public ratings
        if ($viewer->id !== $target->id) {
            $query->where('visibility', 'public');
        }

        // Filter by minimum overall rating
        if (isset($filters['min_overall']) && $filters['min_overall'] !== '') {
            $query->where('overall_rating', '>=', (float) $filters['min_overall']);
        }

        // Filter by project
        if (! empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        // Sorting
        $allowed = ['overall_rating', 'created_at'];
        $sortBy  = in_array($filters['sort_by'] ?? '', $allowed) ? $filters['sort_by'] : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->paginate($perPage);
    }

    /**
     * Rate a collaborator.
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

        $rating = CollaborationRating::create($data);

        // Notify the rated user
        $this->notify(
            userId:   $data['rated_user_id'],
            type:     'new_rating',
            title:    'You received a new rating',
            body:     "{$rater->full_name} rated your collaboration.",
            data:     ['rating_id' => $rating->id, 'rater_id' => $rater->id],
            priority: 'normal',
        );

        return $rating;
    }

    /**
     * Update a rating owned by the rater.
     *
     * @throws ValidationException
     */
    public function update(User $rater, CollaborationRating $rating, array $data): CollaborationRating
    {
        $this->authorizeOwnership($rater, $rating);

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

        return $values->isEmpty() ? null : round($values->avg(), 2);
    }

    private function authorizeOwnership(User $rater, CollaborationRating $rating): void
    {
        if ($rating->rater_id !== $rater->id) {
            abort(403, 'You do not own this rating.');
        }
    }
}
