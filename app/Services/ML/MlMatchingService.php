<?php

namespace App\Services\ML;

use App\DTOs\ML\MatchPairDTO;
use App\Exceptions\ML\MlServiceException;
use App\Models\Project;
use App\Models\User;
use App\Services\MatchService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the full ML match generation cycle for one user or all users.
 *
 * Responsibilities (SRP — one per method):
 *   - resolveUsers()      → who to score
 *   - buildPairs()        → feature engineering → Collection<MatchPairDTO>
 *   - scoreAndIngest()    → MlServiceClient → MatchService::ingestBatch()
 *
 * Callers:
 *   - AdminVerificationService::review() → generateForUser($user)     [on approval]
 *   - Console\Kernel schedule            → generateForAllUsers()       [nightly batch]
 *
 * No queuing concerns live here. The caller decides whether to run inline or
 * wrap this in a queued closure / command.
 */
class MlMatchingService
{
    private const MATCH_TTL_DAYS = 30;

    public function __construct(
        private readonly MlServiceClient $mlClient,
        private readonly MatchService    $matchService,
    ) {}

    // =========================================================================
    // Public entry points
    // =========================================================================

    /**
     * Generate and ingest ML matches for a single newly-approved user.
     *
     * @return array{created: int, updated: int}
     */
    public function generateForUser(User $user): array
    {
        $user->loadMissing('skills');
        $users = collect([$user]);

        return $this->scoreAndIngest($users);
    }

    /**
     * Generate and ingest ML matches for all active regular users.
     * Intended for the nightly scheduled run.
     *
     * @return array{created: int, updated: int}
     */
    public function generateForAllUsers(): array
    {
        $users = User::where('role', 'regular_user')
            ->where('account_status', 'active')
            ->with('skills')
            ->get();

        if ($users->isEmpty()) {
            Log::info('MlMatchingService: no eligible users for batch scoring.');
            return ['created' => 0, 'updated' => 0];
        }

        return $this->scoreAndIngest($users);
    }

    // =========================================================================
    // Private — pipeline
    // =========================================================================

    /**
     * Build pairs → score → ingest.
     *
     * @param  Collection<int, User>  $users
     * @return array{created: int, updated: int}
     */
    private function scoreAndIngest(Collection $users): array
    {
        $pairs = $this->buildPairs($users);

        if ($pairs->isEmpty()) {
            Log::info('MlMatchingService: no pairs to score.', ['user_count' => $users->count()]);
            return ['created' => 0, 'updated' => 0];
        }

        Log::info('MlMatchingService: scoring pairs', [
            'users' => $users->count(),
            'pairs' => $pairs->count(),
        ]);

        try {
            $results = $this->mlClient->predictBatch($pairs);
        } catch (MlServiceException $e) {
            Log::error('MlMatchingService: ML scoring failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        if ($results->isEmpty()) {
            Log::info('MlMatchingService: ML returned no relevant matches.');
            return ['created' => 0, 'updated' => 0];
        }

        $expiresAt = Carbon::now()->addDays(self::MATCH_TTL_DAYS)->toIso8601String();
        $dtos      = $results->map(fn ($r) => $r->toIngestDto($expiresAt))->all();
        $result    = $this->matchService->ingestBatch($dtos);

        Log::info('MlMatchingService: ingested matches', $result);

        return $result;
    }

    // =========================================================================
    // Private — feature engineering
    // =========================================================================

    /**
     * Build all collaborator + project MatchPairDTOs for the given users.
     * Pre-loads candidates and projects once to avoid N+1 queries.
     *
     * @param  Collection<int, User>  $users
     * @return Collection<int, MatchPairDTO>
     */
    private function buildPairs(Collection $users): Collection
    {
        $candidates = User::where('role', 'regular_user')
            ->where('account_status', 'active')
            ->with('skills')
            ->get()
            ->keyBy('id');

        $projects = Project::where('is_accepting_applications', true)
            ->with('skills')
            ->get();

        $pairs = collect();

        foreach ($users as $user) {
            $userSkills = $this->skillNames($user);

            $pairs = $pairs
                ->concat($this->collaboratorPairs($user, $userSkills, $candidates))
                ->concat($this->projectPairs($user, $userSkills, $projects));
        }

        return $pairs;
    }

    /**
     * @param  Collection<string, User>  $candidates  keyed by id
     * @return array<int, MatchPairDTO>
     */
    private function collaboratorPairs(User $user, array $userSkills, Collection $candidates): array
    {
        return $candidates
            ->reject(fn (User $c) => $c->id === $user->id)
            ->map(function (User $candidate) use ($user, $userSkills): MatchPairDTO {
                $candidateSkills = $this->skillNames($candidate);
                [$overlap, $jaccard] = $this->jaccardOverlap($userSkills, $candidateSkills);

                return MatchPairDTO::collaborator(
                    userId:                $user->id,
                    matchedUserId:         $candidate->id,
                    skillOverlap:          $jaccard,
                    overlappingSkillsCount:$overlap,
                    skillsCountA:          count($userSkills),
                    skillsCountB:          count($candidateSkills),
                    userAVerified:         (int) $user->identity_verified,
                    userBVerified:         (int) $candidate->identity_verified,
                    locationMatch:         $this->locationMatch($user->location, $candidate->location),
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, MatchPairDTO>
     */
    private function projectPairs(User $user, array $userSkills, Collection $projects): array
    {
        return $projects
            ->map(function (Project $project) use ($user, $userSkills): MatchPairDTO {
                $projectSkills = $this->skillNames($project);
                $covered       = count(array_intersect($userSkills, $projectSkills));
                $maxTeam       = $project->team_size_max ?? 0;
                $currentTeam   = $project->current_team_size ?? 0;
                $teamOpenness  = $maxTeam > 0
                    ? round(($maxTeam - $currentTeam) / $maxTeam, 4)
                    : 0.0;

                return MatchPairDTO::project(
                    userId:               $user->id,
                    matchedProjectId:     $project->id,
                    skillsCountA:         count($userSkills),
                    coveredSkillsCount:   $covered,
                    projectAccepting:     (int) $project->is_accepting_applications,
                    teamOpenness:         $teamOpenness,
                    userIdentityVerified: (int) $user->identity_verified,
                    locationMatch:        $this->locationMatch($user->location, $project->location ?? null),
                );
            })
            ->values()
            ->all();
    }

    // =========================================================================
    // Private — helpers
    // =========================================================================

    /**
     * skill_name strings for a User or Project (same relationship name on both).
     *
     * @return string[]
     */
    private function skillNames(User|Project $model): array
    {
        return $model->skills->pluck('skill_name')->map('strtolower')->toArray();
    }

    /**
     * Returns [overlap_count, jaccard_similarity].
     *
     * @param  string[]  $a
     * @param  string[]  $b
     * @return array{int, float}
     */
    private function jaccardOverlap(array $a, array $b): array
    {
        $overlap = count(array_intersect($a, $b));
        $union   = count(array_unique(array_merge($a, $b)));
        $jaccard = $union > 0 ? round($overlap / $union, 4) : 0.0;
        return [$overlap, $jaccard];
    }

    private function locationMatch(?string $a, ?string $b): int
    {
        return ($a && $b && strtolower($a) === strtolower($b)) ? 1 : 0;
    }
}
