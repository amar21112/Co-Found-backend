<?php

namespace App\Repositories\Eloquent;

use App\DTOs\Match\ExportDatasetDTO;
use App\DTOs\Match\IngestMatchDTO;
use App\DTOs\Match\SubmitFeedbackDTO;
use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\User;
use App\Repositories\Contracts\MatchRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MatchRepository implements MatchRepositoryInterface
{
    public function paginate(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = MatchModel::where('user_id', $user->id)
            ->with(['matchedUser', 'matchedProject'])
            ->where(function ($q) {
                $q->where('match_type', \App\Enums\MatchType::Collaborator->value)
                    ->orWhere(function ($q2) {
                        $q2->where('match_type', \App\Enums\MatchType::Project->value)
                            ->whereHas('matchedProject', function ($q3) {
                                $q3->where('visibility', 'public');
                            });
                    });
            });

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

    // =========================================================================
    // ML methods
    // =========================================================================

    public function findActiveByPair(IngestMatchDTO $dto): ?MatchModel
    {
        $query = MatchModel::where('user_id', $dto->userId)
            ->where('match_type', $dto->matchType)
            ->where('expires_at', '>', now());

        if ($dto->matchType === 'collaborator') {
            $query->where('matched_user_id', $dto->matchedUserId);
        } else {
            $query->where('matched_project_id', $dto->matchedProjectId);
        }

        return $query->first();
    }

    public function ingestBatch(array $dtos): array
    {
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($dtos, &$created, &$updated) {
            foreach ($dtos as $dto) {
                $existing = $this->findActiveByPair($dto);

                if ($existing) {
                    $existing->update([
                        'compatibility_score' => $dto->compatibilityScore,
                        'match_reasons'       => $dto->matchReasons,
                        'expires_at'          => $dto->expiresAt,
                    ]);
                    $updated++;
                } else {
                    MatchModel::create([
                        'id'                  => (string) Str::uuid(),
                        'user_id'             => $dto->userId,
                        'match_type'          => $dto->matchType,
                        'matched_user_id'     => $dto->matchedUserId,
                        'matched_project_id'  => $dto->matchedProjectId,
                        'compatibility_score' => $dto->compatibilityScore,
                        'match_reasons'       => $dto->matchReasons,
                        'expires_at'          => $dto->expiresAt,
                        'viewed'              => false,
                        'saved'               => false,
                        'action_taken'        => false,
                    ]);
                    $created++;
                }
            }
        });

        return compact('created', 'updated');
    }

    public function exportTrainingData(ExportDatasetDTO $dto): Collection
    {
        $query = DB::table('matches AS m')
            ->leftJoin('match_feedback AS mf', function ($join) {
                $join->on('mf.match_id', '=', 'm.id')
                    ->whereRaw('mf.user_id = m.user_id');
            })
            ->leftJoin('users AS u',   'u.id',  '=', 'm.user_id')
            ->leftJoin('users AS mu',  'mu.id', '=', 'm.matched_user_id')
            ->leftJoin('projects AS mp', 'mp.id', '=', 'm.matched_project_id')
            ->select([
                'm.id', 'm.match_type', 'm.compatibility_score', 'm.match_reasons',
                'm.viewed', 'm.saved', 'm.action_taken',
                'mf.feedback_type',
                'u.identity_verified AS user_identity_verified',
                'u.location AS user_location',
                'mu.identity_verified AS matched_user_identity_verified',
                'mu.location AS matched_user_location',
                'mp.is_accepting_applications AS project_accepting',
                'mp.current_team_size', 'mp.team_size_max',
                'm.created_at',
            ])
            ->where('m.compatibility_score', '>=', $dto->minScore)
            ->orderBy('m.created_at');

        if ($dto->type !== null) {
            $query->where('m.match_type', $dto->type);
        }

        if ($dto->withFeedbackOnly) {
            $query->whereNotNull('mf.feedback_type');
        }

        return $query->get()->map(fn($row) => $this->flattenTrainingRow($row));
    }

    public function datasetStats(): array
    {
        $total = DB::table('matches')->count();

        $byType = DB::table('matches')
            ->selectRaw('match_type, count(*) as count')
            ->groupBy('match_type')
            ->pluck('count', 'match_type');

        $feedbackCount = DB::table('match_feedback')->count();

        $feedbackDist = DB::table('match_feedback')
            ->selectRaw('feedback_type, count(*) as count')
            ->groupBy('feedback_type')
            ->pluck('count', 'feedback_type');

        $scoreStats = DB::table('matches')
            ->selectRaw('
                round(avg(compatibility_score), 4) as avg_score,
                round(min(compatibility_score), 4) as min_score,
                round(max(compatibility_score), 4) as max_score
            ')
            ->first();

        return [
            'total_matches'         => $total,
            'by_type'               => $byType,
            'total_feedback'        => $feedbackCount,
            'feedback_rate'         => $total > 0 ? round($feedbackCount / $total, 4) : 0,
            'feedback_distribution' => $feedbackDist,
            'score_stats'           => $scoreStats,
        ];
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function flattenTrainingRow(object $row): array
    {
        $reasons = json_decode($row->match_reasons ?? '{}', true) ?: [];

        $reasons['overlapping_skills_count'] = count($reasons['overlapping_skills'] ?? []);
        $reasons['covered_skills_count']     = count($reasons['covered_skills']     ?? []);
        unset($reasons['overlapping_skills'], $reasons['covered_skills']);

        return array_merge([
            'id'                     => $row->id,
            'match_type'             => $row->match_type,
            'compatibility_score'    => $row->compatibility_score,
            'viewed'                 => (int) $row->viewed,
            'saved'                  => (int) $row->saved,
            'action_taken'           => (int) $row->action_taken,
            'feedback_type'          => $row->feedback_type ?? '',
            'label_relevant'         => (int) ($row->feedback_type === 'relevant'),
            'label_not_relevant'     => (int) ($row->feedback_type === 'not_relevant'),
            'user_identity_verified' => (int) $row->user_identity_verified,
            'same_location'          => $row->user_location && $row->matched_user_location
                ? (int) ($row->user_location === $row->matched_user_location)
                : ($reasons['location_match'] ?? 0),
            'project_accepting'      => $row->project_accepting ?? 0,
            'team_openness'          => ($row->team_size_max ?? 0) > 0
                ? round(($row->team_size_max - $row->current_team_size) / $row->team_size_max, 3)
                : ($reasons['team_openness'] ?? 0),
        ], $reasons);
    }
}
