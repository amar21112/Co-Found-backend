<?php

namespace App\Repositories\Contracts;

use App\DTOs\Match\ExportDatasetDTO;
use App\DTOs\Match\IngestMatchDTO;
use App\DTOs\Match\SubmitFeedbackDTO;
use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface MatchRepositoryInterface
{
    public function paginate(User $user, array $filters, int $perPage): LengthAwarePaginator;

    public function findById(string $id): ?MatchModel;

    public function markViewed(MatchModel $match): MatchModel;

    public function toggleSave(MatchModel $match): MatchModel;

    public function hasFeedback(MatchModel $match, string $userId): bool;

    public function createFeedback(MatchModel $match, User $user, SubmitFeedbackDTO $dto): MatchFeedback;

    public function markActionTaken(MatchModel $match): MatchModel;

    // ── ML ───────────────────────────────────────────────────────────────────

    /**
     * Upsert a batch of ML-scored match records.
     * Existing non-expired matches for the same user+target pair are re-scored.
     * New pairs are created.
     *
     * @param  IngestMatchDTO[]  $dtos
     * @return array{created: int, updated: int}
     */
    public function ingestBatch(array $dtos): array;

    /**
     * Find a non-expired match for the same user + target pair.
     * Used during ingestion to decide create vs update.
     */
    public function findActiveByPair(IngestMatchDTO $dto): ?MatchModel;

    /**
     * Export flattened training rows.
     * Joins match_feedback, users, and projects for a fully denormalised view.
     *
     * @return Collection<int, array>
     */
    public function exportTrainingData(ExportDatasetDTO $dto): Collection;

    /**
     * Aggregate statistics about the current dataset.
     * Used by the ML team to decide if a new generation run is needed.
     */
    public function datasetStats(): array;
}
