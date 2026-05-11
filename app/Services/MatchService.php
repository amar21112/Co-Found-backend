<?php

namespace App\Services;

use App\DTOs\Match\ExportDatasetDTO;
use App\DTOs\Match\GenerateDatasetDTO;
use App\DTOs\Match\IngestMatchDTO;
use App\DTOs\Match\SubmitFeedbackDTO;
use App\Exceptions\Match\FeedbackAlreadySubmittedException;
use App\Exceptions\Match\MatchNotFoundException;
use App\Generators\MatchDatasetGenerator;
use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\User;
use App\Repositories\Contracts\MatchRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

readonly class MatchService
{
    public function __construct(
        private MatchRepositoryInterface $matchRepo,
        private MatchDatasetGenerator    $generator,
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
    // ML methods
    // =========================================================================

    /**
     * Generate a synthetic training dataset.
     * Delegates to MatchDatasetGenerator — no dependency on the seeder.
     *
     * @return array{users: int, projects: int, collaborator_matches: int, project_matches: int}
     */
    public function generateDataset(GenerateDatasetDTO $dto): array
    {
        return $this->generator->generate(
            users:             $dto->users,
            projects:          $dto->projects,
            collaboratorPairs: $dto->collaboratorPairs,
            projectPairs:      $dto->projectPairs,
            fresh:             $dto->fresh,
        );
    }

    /**
     * Export flattened training data.
     * Single source of truth in the repository.
     *
     * @return Collection<int, array>
     */
    public function exportTrainingData(ExportDatasetDTO $dto): Collection
    {
        return $this->matchRepo->exportTrainingData($dto);
    }

    /**
     * Dataset statistics for the ML team's health check.
     */
    public function datasetStats(): array
    {
        return $this->matchRepo->datasetStats();
    }

    /**
     * Upsert ML-scored match records into the matches table.
     *
     * @param  IngestMatchDTO[]  $dtos
     * @return array{created: int, updated: int}
     */
    public function ingestBatch(array $dtos): array
    {
        return $this->matchRepo->ingestBatch($dtos);
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
