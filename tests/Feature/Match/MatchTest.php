<?php

namespace Tests\Feature\Match;

use App\Enums\FeedbackType;
use App\Models\MatchFeedback;
use App\Models\MatchModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MatchTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create(['password' => Hash::make('Secret123')]);
    }

    private function makeMatch(User $user, array $overrides = []): MatchModel
    {
        return MatchModel::factory()->collaborator()->create(array_merge(
            ['user_id' => $user->id],
            $overrides
        ));
    }

    private function makeProjectMatch(User $user, array $overrides = []): MatchModel
    {
        return MatchModel::factory()->project()->create(array_merge(
            ['user_id' => $user->id],
            $overrides
        ));
    }

    // =========================================================================
    // GET /api/v1/matches
    // =========================================================================

    /** @test */
    public function user_can_list_their_matches(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makeMatch($user);
        $this->makeMatch($user);
        $this->makeProjectMatch($user);

        // Matches for another user — must not appear
        $other = $this->makeUser();
        $this->makeMatch($other);

        $this->getJson('/api/v1/matches')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'status',
                'data' => [['id', 'match_type', 'compatibility_score', 'viewed', 'saved']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    /** @test */
    public function matches_can_be_filtered_by_match_type(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makeMatch($user);
        $this->makeProjectMatch($user);

        $this->getJson('/api/v1/matches?match_type=collaborator')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.match_type', 'collaborator');
    }

    /** @test */
    public function matches_can_be_filtered_by_viewed_status(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        MatchModel::factory()->collaborator()->viewed()->create(['user_id' => $user->id]);
        $this->makeMatch($user);

        $this->getJson('/api/v1/matches?viewed=false')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function matches_can_be_filtered_by_saved_status(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        MatchModel::factory()->collaborator()->saved()->create(['user_id' => $user->id]);
        $this->makeMatch($user);

        $this->getJson('/api/v1/matches?saved=true')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function matches_can_be_filtered_by_minimum_score(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        MatchModel::factory()->collaborator()->create(['user_id' => $user->id, 'compatibility_score' => 0.95]);
        MatchModel::factory()->collaborator()->create(['user_id' => $user->id, 'compatibility_score' => 0.60]);

        $this->getJson('/api/v1/matches?min_score=0.90')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function unauthenticated_user_cannot_list_matches(): void
    {
        $this->getJson('/api/v1/matches')->assertStatus(401);
    }

    // =========================================================================
    // PATCH /api/v1/matches/{id}/view
    // =========================================================================

    /** @test */
    public function user_can_mark_a_match_as_viewed(): void
    {
        $user  = $this->makeUser();
        $match = $this->makeMatch($user, ['viewed' => false]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/matches/$match->id/view")
            ->assertStatus(200)
            ->assertJsonPath('data.viewed', true);

        $this->assertDatabaseHas('matches', [
            'id'     => $match->id,
            'viewed' => true,
        ]);
    }

    /** @test */
    public function marking_as_viewed_is_idempotent(): void
    {
        $user  = $this->makeUser();
        $match = $this->makeMatch($user, ['viewed' => true, 'viewed_at' => now()]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/matches/$match->id/view")
            ->assertStatus(200)
            ->assertJsonPath('data.viewed', true);
    }

    /** @test */
    public function user_cannot_view_another_users_match(): void
    {
        $owner = $this->makeUser();
        $match = $this->makeMatch($owner);
        Sanctum::actingAs($this->makeUser());

        $this->patchJson("/api/v1/matches/$match->id/view")
            ->assertStatus(404);
    }

    /** @test */
    public function view_returns_404_for_unknown_match(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->patchJson('/api/v1/matches/00000000-0000-0000-0000-000000000001/view')
            ->assertStatus(404);
    }

    // =========================================================================
    // PATCH /api/v1/matches/{id}/save
    // =========================================================================

    /** @test */
    public function user_can_save_a_match(): void
    {
        $user  = $this->makeUser();
        $match = $this->makeMatch($user, ['saved' => false]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/matches/$match->id/save")
            ->assertStatus(200)
            ->assertJsonPath('data.saved', true)
            ->assertJsonPath('message', 'Match saved.');
    }

    /** @test */
    public function saving_a_saved_match_unsaves_it(): void
    {
        $user  = $this->makeUser();
        $match = $this->makeMatch($user, ['saved' => true]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/matches/$match->id/save")
            ->assertStatus(200)
            ->assertJsonPath('data.saved', false)
            ->assertJsonPath('message', 'Match unsaved.');
    }

    /** @test */
    public function user_cannot_save_another_users_match(): void
    {
        $owner = $this->makeUser();
        $match = $this->makeMatch($owner);
        Sanctum::actingAs($this->makeUser());

        $this->patchJson("/api/v1/matches/$match->id/save")
            ->assertStatus(404);
    }

    // =========================================================================
    // POST /api/v1/matches/{id}/feedback
    // =========================================================================

    /** @test */
    public function user_can_submit_feedback_for_a_match(): void
    {
        $user  = $this->makeUser();
        $match = $this->makeMatch($user);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/matches/$match->id/feedback", [
            'feedback_type' => 'relevant',
        ])->assertStatus(201)
            ->assertJsonPath('data.feedback_type', 'relevant');

        $this->assertDatabaseHas('match_feedback', [
            'match_id'      => $match->id,
            'user_id'       => $user->id,
            'feedback_type' => 'relevant',
        ]);

        // action_taken must be set
        $this->assertDatabaseHas('matches', [
            'id'           => $match->id,
            'action_taken' => true,
        ]);
    }

    /** @test */
    public function all_feedback_types_are_accepted(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        foreach (FeedbackType::cases() as $type) {
            $match = $this->makeMatch($user);

            $this->postJson("/api/v1/matches/$match->id/feedback", [
                'feedback_type' => $type->value,
            ])->assertStatus(201);
        }
    }

    /** @test */
    public function cannot_submit_feedback_twice_for_the_same_match(): void
    {
        $user  = $this->makeUser();
        $match = $this->makeMatch($user);
        Sanctum::actingAs($user);

        MatchFeedback::create([
            'match_id'      => $match->id,
            'user_id'       => $user->id,
            'feedback_type' => FeedbackType::Relevant->value,
        ]);

        $this->postJson("/api/v1/matches/$match->id/feedback", [
            'feedback_type' => 'not_relevant',
        ])->assertStatus(409);
    }

    /** @test */
    public function invalid_feedback_type_is_rejected(): void
    {
        $user  = $this->makeUser();
        $match = $this->makeMatch($user);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/matches/$match->id/feedback", [
            'feedback_type' => 'love_it',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['feedback_type']);
    }

    /** @test */
    public function user_cannot_submit_feedback_for_another_users_match(): void
    {
        $owner = $this->makeUser();
        $match = $this->makeMatch($owner);
        Sanctum::actingAs($this->makeUser());

        $this->postJson("/api/v1/matches/$match->id/feedback", [
            'feedback_type' => 'relevant',
        ])->assertStatus(404);
    }
}
