<?php

namespace App\Repositories\Contracts;

use App\DTOs\Match\SubmitFeedbackDTO;
use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MatchRepositoryInterface
{
    public function paginate(User $user, array $filters, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?MatchModel;

    public function markViewed(MatchModel $match): MatchModel;

    public function toggleSave(MatchModel $match): MatchModel;

    public function hasFeedback(MatchModel $match, string $userId): bool;

    public function createFeedback(MatchModel $match, User $user, SubmitFeedbackDTO $dto): MatchFeedback;

    public function markActionTaken(MatchModel $match): MatchModel;
}
