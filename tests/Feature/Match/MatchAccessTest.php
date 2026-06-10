<?php

namespace Tests\Feature\Match;

use App\Models\MatchModel;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Fills the gaps not covered by MatchTest:
 *
 *   1. Guest and unverified users are blocked by middleware
 *   2. Response payload contains matched_user / matched_project shapes
 *   3. Expired matches are excluded from GET /matches
 *   4. match_type values are the correct enum strings
 *   5. Matches are ordered by compatibility_score desc
 */
class MatchAccessTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeRegularUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'password'       => Hash::make('Secret123'),
            'role'           => 'regular_user',
            'account_status' => 'active',
            'email_verified' => true,
        ], $overrides));
    }

    private function makeGuestUser(): User
    {
        return User::factory()->guest()->create();
    }

    private function makeUnverifiedUser(): User
    {
        return User::factory()->unverified()->create([
            'password' => Hash::make('Secret123'),
            'role'     => 'regular_user',
        ]);
    }

    // =========================================================================
    // Guest / unverified middleware — every match endpoint
    // =========================================================================

    /** @test */
    public function guest_user_cannot_list_matches(): void
    {
        Sanctum::actingAs($this->makeGuestUser());

        $this->getJson('/api/v1/matches')->assertStatus(403);
    }

    /** @test */
    public function unverified_email_user_cannot_list_matches(): void
    {
        Sanctum::actingAs($this->makeUnverifiedUser());

        $this->getJson('/api/v1/matches')->assertStatus(403);
    }

    /** @test */
    public function guest_user_cannot_view_a_match(): void
    {
        $owner = $this->makeRegularUser();
        $match = MatchModel::factory()->collaborator()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($this->makeGuestUser());

        $this->patchJson("/api/v1/matches/{$match->id}/view")->assertStatus(403);
    }

    /** @test */
    public function guest_user_cannot_save_a_match(): void
    {
        $owner = $this->makeRegularUser();
        $match = MatchModel::factory()->collaborator()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($this->makeGuestUser());

        $this->patchJson("/api/v1/matches/{$match->id}/save")->assertStatus(403);
    }

    /** @test */
    public function guest_user_cannot_submit_match_feedback(): void
    {
        $owner = $this->makeRegularUser();
        $match = MatchModel::factory()->collaborator()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($this->makeGuestUser());

        $this->postJson("/api/v1/matches/{$match->id}/feedback", [
            'feedback_type' => 'relevant',
        ])->assertStatus(403);
    }

    // =========================================================================
    // Response payload shape — matched_user
    // =========================================================================

    /** @test */
    public function collaborator_match_response_includes_matched_user_shape(): void
    {
        $user      = $this->makeRegularUser();
        $candidate = $this->makeRegularUser([
            'username'          => 'jane_doe',
            'full_name'         => 'Jane Doe',
            'identity_verified' => true,
        ]);
        Sanctum::actingAs($user);

        MatchModel::factory()->collaborator()->create([
            'user_id'         => $user->id,
            'matched_user_id' => $candidate->id,
        ]);

        $this->getJson('/api/v1/matches')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'match_type',
                    'compatibility_score',
                    'viewed',
                    'saved',
                    'matched_user' => [
                        'id', 'username', 'full_name',
                        'bio', 'location', 'identity_verified',
                    ],
                ]],
            ])
            ->assertJsonPath('data.0.matched_user.username',          'jane_doe')
            ->assertJsonPath('data.0.matched_user.full_name',         'Jane Doe')
            ->assertJsonPath('data.0.matched_user.identity_verified', true)
            ->assertJsonPath('data.0.matched_project',                null);
    }

    // =========================================================================
    // Response payload shape — matched_project
    // =========================================================================

    /** @test */
    public function project_match_response_includes_matched_project_shape(): void
    {
        $user    = $this->makeRegularUser();
        $project = Project::factory()->create([
            'title'                     => 'Co-Found Platform',
            'visibility'                => 'public',
            'is_accepting_applications' => true,
        ]);
        Sanctum::actingAs($user);

        MatchModel::factory()->project()->create([
            'user_id'            => $user->id,
            'matched_project_id' => $project->id,
        ]);

        $this->getJson('/api/v1/matches')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
                    'matched_project' => [
                        'id', 'title', 'slug',
                        'short_description', 'is_accepting_applications',
                    ],
                ]],
            ])
            ->assertJsonPath('data.0.matched_project.title',                     'Co-Found Platform')
            ->assertJsonPath('data.0.matched_project.is_accepting_applications', true)
            ->assertJsonPath('data.0.matched_user',                              null);
    }

    // =========================================================================
    // Expired matches
    // =========================================================================

    /** @test */
    public function expired_matches_are_excluded_from_list(): void
    {
        $user = $this->makeRegularUser();
        Sanctum::actingAs($user);

        MatchModel::factory()->collaborator()->create(['user_id' => $user->id]);                  // active
        MatchModel::factory()->collaborator()->expired()->create(['user_id' => $user->id]);       // expired

        $this->getJson('/api/v1/matches')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function only_active_matches_count_in_total_meta(): void
    {
        $user = $this->makeRegularUser();
        Sanctum::actingAs($user);

        MatchModel::factory()->collaborator()->count(3)->create(['user_id' => $user->id]);
        MatchModel::factory()->collaborator()->expired()->count(2)->create(['user_id' => $user->id]);

        $this->getJson('/api/v1/matches')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 3);
    }

    // =========================================================================
    // match_type enum values
    // =========================================================================

    /** @test */
    public function match_type_value_for_collaborator_is_correct_string(): void
    {
        $user = $this->makeRegularUser();
        Sanctum::actingAs($user);

        MatchModel::factory()->collaborator()->create(['user_id' => $user->id]);

        $this->getJson('/api/v1/matches')
            ->assertStatus(200)
            ->assertJsonPath('data.0.match_type', 'collaborator');
    }

    /** @test */
    public function match_type_value_for_project_is_correct_string(): void
    {
        $user = $this->makeRegularUser();
        Sanctum::actingAs($user);

        // Override the nested Project factory to force public visibility,
        // otherwise the query's whereHas('matchedProject', visibility=public) excludes the row.
        MatchModel::factory()->project()->create([
            'user_id'            => $user->id,
            'matched_project_id' => Project::factory()->create(['visibility' => 'public'])->id,
        ]);

        $this->getJson('/api/v1/matches')
            ->assertStatus(200)
            ->assertJsonPath('data.0.match_type', 'project');
    }

    // =========================================================================
    // Ordering
    // =========================================================================

    /** @test */
    public function matches_are_returned_ordered_by_compatibility_score_descending(): void
    {
        $user = $this->makeRegularUser();
        Sanctum::actingAs($user);

        MatchModel::factory()->collaborator()->create(['user_id' => $user->id, 'compatibility_score' => 0.55]);
        MatchModel::factory()->collaborator()->create(['user_id' => $user->id, 'compatibility_score' => 0.95]);
        MatchModel::factory()->collaborator()->create(['user_id' => $user->id, 'compatibility_score' => 0.75]);

        $scores = $this->getJson('/api/v1/matches')
            ->assertStatus(200)
            ->json('data.*.compatibility_score');

        $this->assertEquals([0.95, 0.75, 0.55], $scores);
    }

    // =========================================================================
    // MatchResource — field completeness
    // =========================================================================

    /** @test */
    public function match_resource_returns_all_required_fields(): void
    {
        $user = $this->makeRegularUser();
        Sanctum::actingAs($user);

        MatchModel::factory()->collaborator()->viewed()->saved()->create([
            'user_id'       => $user->id,
            'action_taken'  => true,
        ]);

        $this->getJson('/api/v1/matches')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'match_type',
                    'compatibility_score',
                    'match_reasons',
                    'viewed',
                    'viewed_at',
                    'saved',
                    'action_taken',
                    'expires_at',
                    'created_at',
                ]],
            ]);
    }
}
