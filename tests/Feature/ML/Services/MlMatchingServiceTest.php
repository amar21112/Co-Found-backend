<?php

namespace Tests\Feature\ML\Services;

use App\DTOs\ML\MatchPairDTO;
use App\DTOs\ML\MlMatchResultDTO;
use App\Exceptions\ML\MlServiceException;
use App\Models\MatchModel;
use App\Models\Project;
use App\Models\User;
use App\Services\MatchService;
use App\Services\ML\MlMatchingService;
use App\Services\ML\MlServiceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for MlMatchingService.
 *
 * MlServiceClient is mocked — we test the orchestration logic:
 *   - correct pairs are built and passed to the client
 *   - results are mapped to IngestMatchDTOs and handed to MatchService
 *   - empty/no-candidate edge cases are handled gracefully
 *   - ML failures surface correctly without crashing the system
 */
class MlMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    private MlServiceClient $mlClient;
    private MlMatchingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mlClient = Mockery::mock(MlServiceClient::class);

        $this->service = new MlMatchingService(
            $this->mlClient,
            app(MatchService::class),
        );
    }

    // =========================================================================
    // generateForUser
    // =========================================================================

    /** @test */
    public function generate_for_user_builds_collaborator_pairs_for_all_other_active_users(): void
    {
        $user       = $this->makeRegularUser();
        $candidateA = $this->makeRegularUser();
        $candidateB = $this->makeRegularUser();

        // Guest — must NOT be included as a candidate
        User::factory()->guest()->create();

        $capturedPairs = null;

        $this->mlClient
            ->shouldReceive('predictBatch')
            ->once()
            ->andReturnUsing(function (Collection $pairs) use (&$capturedPairs) {
                $capturedPairs = $pairs;
                return collect();
            });

        $this->service->generateForUser($user);

        $collaboratorPairs = $capturedPairs->filter(
            fn (MatchPairDTO $p) => $p->matchType === 'collaborator'
        );

        $candidateIds = $collaboratorPairs->map(fn ($p) => $p->matchedUserId)->values()->all();

        $this->assertContains($candidateA->id, $candidateIds);
        $this->assertContains($candidateB->id, $candidateIds);
        $this->assertNotContains($user->id, $candidateIds, 'User must not be paired with themselves');
    }

    /** @test */
    public function generate_for_user_builds_project_pairs_for_all_accepting_projects(): void
    {
        $user = $this->makeRegularUser();

        $open   = Project::factory()->create(['is_accepting_applications' => true]);
        $closed = Project::factory()->create(['is_accepting_applications' => false]);

        $capturedPairs = null;

        $this->mlClient
            ->shouldReceive('predictBatch')
            ->once()
            ->andReturnUsing(function (Collection $pairs) use (&$capturedPairs) {
                $capturedPairs = $pairs;
                return collect();
            });

        $this->service->generateForUser($user);

        $projectPairs = $capturedPairs->filter(fn (MatchPairDTO $p) => $p->matchType === 'project');
        $projectIds   = $projectPairs->map(fn ($p) => $p->matchedProjectId)->values()->all();

        $this->assertContains($open->id, $projectIds);
        $this->assertNotContains($closed->id, $projectIds, 'Closed project must not be included');
    }

    /** @test */
    public function generate_for_user_returns_zero_counts_when_no_candidates_exist(): void
    {
        $user = $this->makeRegularUser();
        // No other users, no projects

        $this->mlClient->shouldNotReceive('predictBatch');

        $result = $this->service->generateForUser($user);

        $this->assertEquals(['created' => 0, 'updated' => 0], $result);
    }

    /** @test */
    public function generate_for_user_returns_zero_counts_when_ml_returns_no_relevant_pairs(): void
    {
        $user = $this->makeRegularUser();
        $this->makeRegularUser(); // candidate

        $this->mlClient
            ->shouldReceive('predictBatch')
            ->once()
            ->andReturn(collect()); // no relevant results

        $result = $this->service->generateForUser($user);

        $this->assertEquals(['created' => 0, 'updated' => 0], $result);
        $this->assertDatabaseCount('matches', 0);
    }

    /** @test */
    public function generate_for_user_ingests_relevant_collaborator_match(): void
    {
        $user      = $this->makeRegularUser();
        $candidate = $this->makeRegularUser();

        $this->mlClient
            ->shouldReceive('predictBatch')
            ->once()
            ->andReturnUsing(function (Collection $pairs) use ($user, $candidate) {
                $pair = $pairs->first(
                    fn (MatchPairDTO $p) =>
                        $p->matchType === 'collaborator' && $p->matchedUserId === $candidate->id
                );

                return collect([
                    MlMatchResultDTO::fromPairAndResponse($pair, [
                        'is_relevant'         => true,
                        'compatibility_score' => 0.88,
                        'match_reasons'       => ['skill_overlap' => 0.7],
                    ]),
                ]);
            });

        $result = $this->service->generateForUser($user);

        $this->assertEquals(1, $result['created']);
        $this->assertDatabaseHas('matches', [
            'user_id'             => $user->id,
            'matched_user_id'     => $candidate->id,
            'match_type'          => 'collaborator',
            'compatibility_score' => 0.88,
        ]);
    }

    /** @test */
    public function generate_for_user_ingests_relevant_project_match(): void
    {
        $user    = $this->makeRegularUser();
        $project = Project::factory()->create(['is_accepting_applications' => true]);

        $this->mlClient
            ->shouldReceive('predictBatch')
            ->once()
            ->andReturnUsing(function (Collection $pairs) use ($project) {
                $pair = $pairs->first(
                    fn (MatchPairDTO $p) =>
                        $p->matchType === 'project' && $p->matchedProjectId === $project->id
                );

                return collect([
                    MlMatchResultDTO::fromPairAndResponse($pair, [
                        'is_relevant'         => true,
                        'compatibility_score' => 0.75,
                        'match_reasons'       => ['skill_coverage' => 0.8],
                    ]),
                ]);
            });

        $result = $this->service->generateForUser($user);

        $this->assertEquals(1, $result['created']);
        $this->assertDatabaseHas('matches', [
            'user_id'            => $user->id,
            'matched_project_id' => $project->id,
            'match_type'         => 'project',
        ]);
    }

    /** @test */
    public function generate_for_user_computes_skill_overlap_correctly(): void
    {
        $user      = $this->makeRegularUser();
        $candidate = $this->makeRegularUser();

        // user has PHP, Laravel, React
        $this->attachSkills($user, ['PHP', 'Laravel', 'React']);
        // candidate has PHP, Laravel, Vue.js — overlap: PHP + Laravel (2/4 union)
        $this->attachSkills($candidate, ['PHP', 'Laravel', 'Vue.js']);

        $capturedPair = null;

        $this->mlClient
            ->shouldReceive('predictBatch')
            ->once()
            ->andReturnUsing(function (Collection $pairs) use (&$capturedPair) {
                $capturedPair = $pairs->first(fn (MatchPairDTO $p) => $p->matchType === 'collaborator');
                return collect();
            });

        $this->service->generateForUser($user);

        // union = {PHP, Laravel, React, Vue.js} = 4, overlap = 2 → jaccard = 0.5
        $this->assertEquals(0.5,  $capturedPair->skillOverlap);
        $this->assertEquals(0.5,  $capturedPair->complementarity);
        $this->assertEquals(2,    $capturedPair->overlappingSkillsCount);
    }

    /** @test */
    public function generate_for_user_sets_location_match_when_locations_match(): void
    {
        $user      = $this->makeRegularUser(['location' => 'Cairo']);
        $this->makeRegularUser(['location' => 'Cairo']);

        $capturedPair = null;

        $this->mlClient
            ->shouldReceive('predictBatch')
            ->once()
            ->andReturnUsing(function (Collection $pairs) use (&$capturedPair) {
                $capturedPair = $pairs->first(fn (MatchPairDTO $p) => $p->matchType === 'collaborator');
                return collect();
            });

        $this->service->generateForUser($user);

        $this->assertEquals(1, $capturedPair->locationMatch);
        $this->assertEquals(1, $capturedPair->sameLocation);
    }

    /** @test */
    public function generate_for_user_clears_location_match_when_locations_differ(): void
    {
        $user      = $this->makeRegularUser(['location' => 'Cairo']);
        $this->makeRegularUser(['location' => 'Riyadh']);

        $capturedPair = null;

        $this->mlClient
            ->shouldReceive('predictBatch')
            ->once()
            ->andReturnUsing(function (Collection $pairs) use (&$capturedPair) {
                $capturedPair = $pairs->first(fn (MatchPairDTO $p) => $p->matchType === 'collaborator');
                return collect();
            });

        $this->service->generateForUser($user);

        $this->assertEquals(0, $capturedPair->locationMatch);
    }

    /** @test */
    public function generate_for_user_sets_identity_verified_flags_correctly(): void
    {
        $user      = $this->makeRegularUser(['identity_verified' => true]);
        $this->makeRegularUser(['identity_verified' => false]);

        $capturedPair = null;

        $this->mlClient
            ->shouldReceive('predictBatch')
            ->once()
            ->andReturnUsing(function (Collection $pairs) use (&$capturedPair) {
                $capturedPair = $pairs->first(fn (MatchPairDTO $p) => $p->matchType === 'collaborator');
                return collect();
            });

        $this->service->generateForUser($user);

        $this->assertEquals(1, $capturedPair->userAVerified);
        $this->assertEquals(0, $capturedPair->userBVerified);
        $this->assertEquals(0, $capturedPair->bothIdentityVerified);
    }

    /** @test */
    public function generate_for_user_rethrows_retryable_ml_exceptions(): void
    {
        $user = $this->makeRegularUser();
        $this->makeRegularUser();

        $this->mlClient
            ->shouldReceive('predictBatch')
            ->once()
            ->andThrow(new MlServiceException('ML timeout', 0));

        $this->expectException(MlServiceException::class);

        $this->service->generateForUser($user);
    }

    // =========================================================================
    // generateForAllUsers
    // =========================================================================

    /** @test */
    public function generate_for_all_users_scores_every_active_regular_user(): void
    {
        $userA = $this->makeRegularUser();
        $userB = $this->makeRegularUser();

        // These must NOT be scored
        User::factory()->guest()->create();
        User::factory()->admin()->create();

        $capturedPairs = collect();

        $this->mlClient
            ->shouldReceive('predictBatch')
            ->once()
            ->andReturnUsing(function (Collection $pairs) use (&$capturedPairs) {
                $capturedPairs = $pairs;
                return collect();
            });

        $this->service->generateForAllUsers();

        $scoredUserIds = $capturedPairs->map(fn (MatchPairDTO $p) => $p->userId)->unique()->values()->all();

        $this->assertContains($userA->id, $scoredUserIds);
        $this->assertContains($userB->id, $scoredUserIds);
    }

    /** @test */
    public function generate_for_all_users_returns_zero_when_no_eligible_users(): void
    {
        User::factory()->guest()->create();

        $this->mlClient->shouldNotReceive('predictBatch');

        $result = $this->service->generateForAllUsers();

        $this->assertEquals(['created' => 0, 'updated' => 0], $result);
    }

    /** @test */
    public function generate_for_all_users_updates_existing_match_for_same_pair(): void
    {
        $user      = $this->makeRegularUser();
        $candidate = $this->makeRegularUser();

        MatchModel::factory()->collaborator()->create([
            'user_id'             => $user->id,
            'matched_user_id'     => $candidate->id,
            'compatibility_score' => 0.60,
            'expires_at'          => now()->addDays(5),
        ]);

        $this->mlClient
            ->shouldReceive('predictBatch')
            ->once()
            ->andReturnUsing(function (Collection $pairs) use ($user, $candidate) {
                $pair = $pairs->first(
                    fn (MatchPairDTO $p) =>
                        $p->userId === $user->id && $p->matchedUserId === $candidate->id
                );

                return collect([
                    MlMatchResultDTO::fromPairAndResponse($pair, [
                        'is_relevant'         => true,
                        'compatibility_score' => 0.95,
                        'match_reasons'       => ['skill_overlap' => 0.9],
                    ]),
                ]);
            });

        $result = $this->service->generateForAllUsers();

        $this->assertEquals(0, $result['created']);
        $this->assertEquals(1, $result['updated']);
        $this->assertDatabaseCount('matches', 1);
        $this->assertDatabaseHas('matches', ['compatibility_score' => 0.95]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeRegularUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'           => 'regular_user',
            'account_status' => 'active',
        ], $overrides));
    }

    private function attachSkills(User $user, array $skillNames): void
    {
        foreach ($skillNames as $name) {
            $user->skills()->create(['skill_name' => $name, 'proficiency_level' => 3]);
        }
    }
}
