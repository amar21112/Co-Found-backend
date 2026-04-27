<?php

namespace App\Repositories\Eloquent;

use App\DTOs\Match\SubmitFeedbackDTO;
use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\User;
use App\Repositories\Contracts\MatchRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MatchRepository implements MatchRepositoryInterface
{
    public function paginate(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = MatchModel::where('user_id', $user->id)
            ->with(['matchedUser', 'matchedProject']);

        if (! empty($filters['match_type'])) {
            $query->where('match_type', $filters['match_type']);
        }

        if (isset($filters['viewed']) && $filters['viewed'] !== '') {
            $query->where('viewed', filter_var($filters['viewed'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['saved']) && $filters['saved'] !== '') {
            $query->where('saved', filter_var($filters['saved'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['min_score']) && $filters['min_score'] !== '') {
            $query->where('compatibility_score', '>=', (float) $filters['min_score']);
        }

        $allowed = ['compatibility_score', 'created_at', 'expires_at'];
        $sortBy  = in_array($filters['sort_by'] ?? '', $allowed)
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    public function findById(string $id): ?MatchModel
    {
        return MatchModel::with(['matchedUser', 'matchedProject', 'feedback'])->find($id);
    }

    public function markViewed(MatchModel $match): MatchModel
    {
        if (! $match->viewed) {
            $match->update([
                'viewed'    => true,
                'viewed_at' => now(),
            ]);
        }

        return $match->load(['matchedUser', 'matchedProject']);
    }

    public function toggleSave(MatchModel $match): MatchModel
    {
        $match->update(['saved' => ! $match->saved]);
        return $match->load(['matchedUser', 'matchedProject']);
    }

    public function hasFeedback(MatchModel $match, string $userId): bool
    {
        return MatchFeedback::where('match_id', $match->id)
            ->where('user_id', $userId)
            ->exists();
    }

    public function createFeedback(
        MatchModel       $match,
        User             $user,
        SubmitFeedbackDTO $dto
    ): MatchFeedback {
        return MatchFeedback::create([
            'match_id'      => $match->id,
            'user_id'       => $user->id,
            'feedback_type' => $dto->feedbackType->value,
        ]);
    }

    public function markActionTaken(MatchModel $match): MatchModel
    {
        $match->update(['action_taken' => true]);
        return $match;
    }
}
